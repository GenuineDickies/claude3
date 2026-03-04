<?php

declare(strict_types=1);

/**
 * Standalone Geolocation Capture Page (Lite)
 *
 * Deployed alongside webhook.php on shared hosting.
 * No database required — logs GPS coordinates to a local file.
 *
 * Usage:
 *   GET  /webhook-proxy/locate.php?t=<token>  → shows GPS capture page
 *   POST /webhook-proxy/locate.php?t=<token>  → receives {lat, lng, accuracy} JSON
 */

$VERSION = '2026-02-22a';

/**
 * Load config.php if it exists (same pattern as webhook.php).
 */
function load_config(): array
{
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        return [];
    }
    $config = require $path;
    return is_array($config) ? $config : [];
}

// ── POST: receive GPS coordinates ────────────────────────────────────────────

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $token = trim($_GET['t'] ?? '');
    if ($token === '' || strlen($token) < 10) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Invalid token.']);
        exit;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Bad request.']);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['latitude'], $data['longitude'])) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Missing latitude/longitude.']);
        exit;
    }

    $lat = (float) $data['latitude'];
    $lng = (float) $data['longitude'];
    $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Invalid coordinates.']);
        exit;
    }

    // Log to file (audit trail)
    $line = sprintf(
        "[%s] token=%s lat=%s lng=%s accuracy=%s ip=%s\n",
        gmdate('c'),
        $token,
        $lat,
        $lng,
        $accuracy ?? '-',
        $_SERVER['REMOTE_ADDR'] ?? '-',
    );

    $logPath = __DIR__ . '/location-captures.log';
    @file_put_contents($logPath, $line, FILE_APPEND);

    // Also save structured data per-token for easy lookup
    $capturePath = __DIR__ . '/captures/' . preg_replace('/[^a-zA-Z0-9]/', '', $token) . '.json';
    $captureDir = dirname($capturePath);
    if (!is_dir($captureDir)) {
        @mkdir($captureDir, 0750, true);
    }
    @file_put_contents($capturePath, json_encode([
        'token'     => $token,
        'latitude'  => $lat,
        'longitude' => $lng,
        'accuracy'  => $accuracy,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
        'time'      => gmdate('c'),
        'maps_url'  => "https://maps.google.com/maps?q={$lat},{$lng}",
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // ── Update the database so the operator's dashboard reflects it ──
    $dbUpdated = false;
    $callbackOk = false;
    $config = load_config();

    // Strategy 1: POST back to the Laravel API (works across any deployment topology)
    $callbackUrl = $config['APP_CALLBACK_URL'] ?? '';
    if ($callbackUrl !== '') {
        $callbackUrl = rtrim($callbackUrl, '/') . '/api/locate/' . urlencode($token);
        $postBody = json_encode([
            'latitude'  => $lat,
            'longitude' => $lng,
            'accuracy'  => $accuracy,
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $postBody,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $cbResponse = @file_get_contents($callbackUrl, false, $ctx);
        if ($cbResponse !== false) {
            $cbData = json_decode($cbResponse, true);
            $callbackOk = is_array($cbData) && ($cbData['ok'] ?? false);
        }
        if (!$callbackOk) {
            @file_put_contents(
                __DIR__ . '/locate-callback-errors.log',
                sprintf("[%s] token=%s url=%s response=%s\n", gmdate('c'), $token, $callbackUrl, $cbResponse ?: 'false'),
                FILE_APPEND,
            );
        }
    }

    // Strategy 2: Direct DB update via PDO (same-server / shared-hosting deployments)
    $dbHost = $config['DB_HOST'] ?? '';
    if ($dbHost !== '' && !$callbackOk) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $dbHost,
                $config['DB_PORT'] ?? '3306',
                $config['DB_DATABASE'] ?? '',
            );
            $pdo = new PDO($dsn, $config['DB_USERNAME'] ?? '', $config['DB_PASSWORD'] ?? '', [
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT    => 5,
            ]);

            $stmt = $pdo->prepare(
                'UPDATE service_requests
                    SET latitude          = :lat,
                        longitude         = :lng,
                        location_shared_at = NOW()
                  WHERE location_token = :token
                    AND location_shared_at IS NULL'
            );
            $stmt->execute([
                ':lat'   => $lat,
                ':lng'   => $lng,
                ':token' => $token,
            ]);

            $dbUpdated = $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            // Log but don't fail — flat-file capture is the fallback
            @file_put_contents(
                __DIR__ . '/locate-db-errors.log',
                sprintf("[%s] token=%s error=%s\n", gmdate('c'), $token, $e->getMessage()),
                FILE_APPEND,
            );
        }
    }

    echo json_encode([
        'ok'      => true,
        'message' => 'Location received. Thank you!',
        'db'      => $dbUpdated || $callbackOk,
    ]);
    exit;
}

// ── OPTIONS: CORS preflight ──────────────────────────────────────────────────

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// ── GET: serve GPS capture page ──────────────────────────────────────────────

$token = trim($_GET['t'] ?? '');
$expired = ($token === '' || strlen($token) < 10);

// Load config for Google Maps API key
$config = load_config();
$mapsApiKey = $config['GOOGLE_MAPS_API_KEY'] ?? '';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Share Your Location</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #fff;
            color: #1a202c;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        .card {
            background: #fff;
            width: 100%;
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            text-align: center;
        }
        @media (min-width: 600px) {
            body {
                align-items: center;
                justify-content: center;
                padding: 2rem;
                background: #f0f4f8;
            }
            .card {
                max-width: 500px;
                width: auto;
                border-radius: 1.5rem;
                box-shadow: 0 8px 40px rgba(0,0,0,0.12);
                flex: none;
                padding: 2rem 1.5rem;
            }
        }
        .card-inner {
            width: 100%;
            max-width: 500px;
        }
        .icon { font-size: 4rem; margin-bottom: 1.25rem; }
        h1 { font-size: 1.6rem; margin-bottom: 0.75rem; }
        p { color: #4a5568; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 1.1rem 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            max-width: 400px;
            transition: background 0.2s;
        }
        .btn:hover { background: #1d4ed8; }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .spinner {
            display: none;
            margin: 1rem auto;
            width: 40px; height: 40px;
            border: 4px solid #e2e8f0;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .status { margin-top: 1rem; font-size: 0.9rem; }
        .status.success { color: #16a34a; }
        .status.error { color: #dc2626; }
        #map-container {
            display: none;
            margin-top: 1.25rem;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        #map-container iframe {
            width: 100%;
            height: 45vh;
            min-height: 250px;
            max-height: 500px;
            border: 0;
        }
        .map-label {
            font-size: 0.85rem;
            color: #4a5568;
            margin-top: 0.75rem;
        }
        .confirm-row {
            display: none;
            margin-top: 1rem;
            gap: 0.5rem;
            flex-direction: column;
        }
        .btn-confirm {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            max-width: 400px;
        }
        .btn-retry {
            display: inline-block;
            background: #e2e8f0;
            color: #1a202c;
            border: none;
            border-radius: 0.75rem;
            padding: 0.85rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            max-width: 400px;
        }
        .btn-confirm:hover { background: #15803d; }
        .btn-retry:hover { background: #cbd5e1; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-inner">
<?php if ($expired): ?>
        <div class="icon">⏰</div>
        <h1>Link Expired</h1>
        <p>This location-sharing link is invalid or has expired. Please contact us if you still need assistance.</p>
<?php else: ?>
        <div class="icon">📍</div>
        <h1>Share Your Location</h1>
        <p>
            <?= htmlspecialchars($config['COMPANY_NAME'] ?? 'Your service') ?> team needs your location to reach you.
            Tap the button below to share your current GPS position.
        </p>

        <button id="shareBtn" class="btn" onclick="getLocation()">Share My Location</button>
        <div id="spinner" class="spinner"></div>
        <div id="status" class="status"></div>

        <div id="map-container">
            <iframe id="map-frame" src="" allowfullscreen loading="lazy"></iframe>
        </div>
        <p id="map-label" class="map-label" style="display:none;">Does this look right? If not, try again from a different spot.</p>

        <div id="confirm-row" class="confirm-row">
            <button class="btn-confirm" onclick="confirmDone()">Yes, that&rsquo;s correct!</button>
            <button class="btn-retry" onclick="retryLocation()">No, try again</button>
        </div>

        <script>
            var pendingLocation = null;
            var mapsApiKey = <?= json_encode($mapsApiKey) ?>;

            function getLocation() {
                var btn = document.getElementById('shareBtn');
                var spinner = document.getElementById('spinner');
                var status = document.getElementById('status');

                if (!navigator.geolocation) {
                    status.textContent = 'Geolocation is not supported by your browser.';
                    status.className = 'status error';
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Getting location\u2026';
                spinner.style.display = 'block';
                status.textContent = '';

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        spinner.style.display = 'none';

                        pendingLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        };

                        showMap(pendingLocation.latitude, pendingLocation.longitude);
                    },
                    function (err) {
                        spinner.style.display = 'none';
                        btn.disabled = false;
                        btn.textContent = 'Share My Location';

                        var msg = 'Unable to get your location.';
                        if (err.code === 1) {
                            msg = 'Location permission denied. Please allow location access in your browser settings and try again.';
                        } else if (err.code === 2) {
                            msg = 'Location unavailable. Please make sure GPS is enabled.';
                        } else if (err.code === 3) {
                            msg = 'Location request timed out. Please try again.';
                        }

                        status.textContent = msg;
                        status.className = 'status error';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }

            function showMap(lat, lng) {
                var btn = document.getElementById('shareBtn');
                var status = document.getElementById('status');
                var mapContainer = document.getElementById('map-container');
                var mapFrame = document.getElementById('map-frame');
                var mapLabel = document.getElementById('map-label');
                var confirmRow = document.getElementById('confirm-row');

                status.textContent = 'Is this pin on your location? Please confirm before we send it.';
                status.className = 'status';
                btn.style.display = 'none';

                if (mapsApiKey) {
                    mapFrame.src = 'https://www.google.com/maps/embed/v1/place?key=' + encodeURIComponent(mapsApiKey) + '&q=' + lat + ',' + lng + '&zoom=16';
                } else {
                    mapFrame.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + (lng - 0.005) + ',' + (lat - 0.005) + ',' + (lng + 0.005) + ',' + (lat + 0.005) + '&layer=mapnik&marker=' + lat + ',' + lng;
                }
                mapContainer.style.display = 'block';
                mapLabel.style.display = 'block';
                confirmRow.style.display = 'flex';
            }

            function confirmDone() {
                if (!pendingLocation) return;

                var status = document.getElementById('status');
                var confirmRow = document.getElementById('confirm-row');
                var mapLabel = document.getElementById('map-label');
                var confirmBtn = confirmRow.querySelector('.btn-confirm');
                var retryBtn = confirmRow.querySelector('.btn-retry');

                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Sending\u2026';
                retryBtn.style.display = 'none';
                status.textContent = 'Sending your confirmed location\u2026';
                status.className = 'status';

                var url = window.location.pathname + '?t=<?= htmlspecialchars(urlencode($token), ENT_QUOTES) ?>';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(pendingLocation)
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        status.textContent = '\u2705 Location confirmed and sent! Help is on the way.';
                        status.className = 'status success';
                        confirmRow.style.display = 'none';
                        mapLabel.style.display = 'none';
                        pendingLocation = null;
                    } else {
                        status.textContent = data.error || 'Something went wrong. Please try again.';
                        status.className = 'status error';
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Yes, that\u2019s correct!';
                        retryBtn.style.display = 'block';
                    }
                })
                .catch(function () {
                    status.textContent = 'Network error. Please check your connection and try again.';
                    status.className = 'status error';
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Yes, that\u2019s correct!';
                    retryBtn.style.display = 'block';
                });
            }

            function retryLocation() {
                var btn = document.getElementById('shareBtn');
                var status = document.getElementById('status');
                var mapContainer = document.getElementById('map-container');
                var mapLabel = document.getElementById('map-label');
                var confirmRow = document.getElementById('confirm-row');

                pendingLocation = null;
                mapContainer.style.display = 'none';
                mapLabel.style.display = 'none';
                confirmRow.style.display = 'none';
                status.textContent = '';
                btn.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Share My Location';
            }
        </script>
<?php endif; ?>
        </div>
    </div>
</body>
</html>
