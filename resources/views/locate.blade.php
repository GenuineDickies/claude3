<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Share Your Location | {{ $companyName }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-deep: #040811;
            --bg-mid: #0a1221;
            --panel: rgba(15, 24, 45, 0.9);
            --panel-strong: rgba(21, 33, 61, 0.96);
            --panel-border: rgba(255, 255, 255, 0.1);
            --text-main: #f3f7ff;
            --text-body: #c2cde1;
            --text-muted: #8fa0ba;
            --accent: #1ed6f2;
            --accent-strong: #11b7d6;
            --success: #22c55e;
            --danger: #fb7185;
            --shadow-lg: 0 24px 60px rgba(0, 0, 0, 0.45);
            --shadow-glow: 0 0 0 1px rgba(255,255,255,0.05), 0 18px 50px rgba(0,0,0,0.35), 0 0 40px rgba(30, 214, 242, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; min-height: 100%; }
        body {
            position: relative;
            font-family: Inter, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background:
                radial-gradient(circle at top center, rgba(30, 214, 242, 0.18), transparent 30%),
                radial-gradient(circle at bottom left, rgba(124, 58, 237, 0.16), transparent 26%),
                linear-gradient(180deg, var(--bg-mid), var(--bg-deep));
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
            overflow-x: hidden;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: 0.18;
            background-image:
                linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 24px 24px;
            mask-image: radial-gradient(circle at center, black, transparent 78%);
        }
        .card {
            position: relative;
            isolation: isolate;
            background:
                linear-gradient(180deg, rgba(24, 36, 67, 0.92), rgba(10, 16, 31, 0.98));
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            padding: 1.25rem 1rem 1.75rem;
            text-align: center;
            overflow: hidden;
        }
        .card::before,
        .card::after {
            content: "";
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
            z-index: -1;
        }
        .card::before {
            top: -12rem;
            right: -7rem;
            width: 20rem;
            height: 20rem;
            background: radial-gradient(circle, rgba(30, 214, 242, 0.24), transparent 66%);
            filter: blur(14px);
        }
        .card::after {
            bottom: -9rem;
            left: -6rem;
            width: 16rem;
            height: 16rem;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.22), transparent 68%);
            filter: blur(18px);
        }
        @media (min-width: 720px) {
            body {
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .card {
                width: min(100%, 680px);
                min-height: min(100dvh - 4rem, 920px);
                border-radius: 2rem;
                border: 1px solid var(--panel-border);
                box-shadow: var(--shadow-glow);
                flex: none;
                padding: 1.5rem 1.5rem 2rem;
            }
        }
        .icon {
            font-size: clamp(4rem, 10vw, 5.5rem);
            margin: 0.4rem 0 0.75rem;
            filter: drop-shadow(0 12px 22px rgba(30, 214, 242, 0.12));
        }
        h1 {
            font-size: clamp(3rem, 11vw, 4.5rem);
            line-height: 1.04;
            letter-spacing: -0.04em;
            margin-bottom: 0.9rem;
            color: var(--text-main);
            text-wrap: balance;
        }
        p {
            color: var(--text-body);
            font-size: 2.5rem;
            line-height: 1.35;
            margin-bottom: 1.15rem;
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.95rem;
            margin-bottom: 1.1rem;
            text-align: left;
        }
        .brand-mark {
            position: relative;
            width: 5.5rem;
            height: 4.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: linear-gradient(145deg, rgba(26, 42, 72, 0.95), rgba(14, 22, 39, 0.95));
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 12px 24px rgba(0, 0, 0, 0.28);
            overflow: hidden;
        }
        .brand-mark img {
            display: block;
            max-width: 84%;
            max-height: 84%;
            width: auto;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 10px 18px rgba(15, 23, 42, 0.32));
        }
        .brand-fallback {
            display: none;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #a5f3fc;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 0.06em;
        }
        .brand-copy {
            min-width: 0;
        }
        .brand-name {
            display: block;
            color: var(--text-main);
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.2;
        }
        .brand-tagline {
            display: block;
            color: var(--text-muted);
            font-size: 1.6rem;
            line-height: 1.35;
            margin-top: 0.2rem;
        }
        .eyebrow {
            display: inline-flex;
            align-self: center;
            padding: 0.6rem 1.2rem;
            border-radius: 9999px;
            background: rgba(232, 248, 255, 0.92);
            color: #24568e;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
        }
        .hero-copy {
            max-width: 32rem;
            margin: 0 auto 1rem;
            color: var(--text-body);
        }
        .helper-note {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            margin: 0 auto 1.2rem;
            padding: 0.85rem 1.2rem;
            border-radius: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-muted);
            font-size: 2rem;
            line-height: 1.35;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(180deg, #20d8f4, #14b8d8);
            color: #03131d;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 1.2rem;
            padding: 1.4rem 1.5rem;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 20px 34px rgba(20, 184, 216, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .btn:hover,
        .btn:focus-visible {
            background: linear-gradient(180deg, #48e2fb, #1bb8dd);
            transform: translateY(-1px);
            box-shadow: 0 22px 40px rgba(20, 184, 216, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.35);
            outline: none;
        }
        .btn:disabled {
            background: linear-gradient(180deg, rgba(128, 145, 170, 0.8), rgba(110, 122, 146, 0.8));
            color: rgba(255,255,255,0.85);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        .spinner {
            display: none;
            margin: 1rem auto;
            width: 60px; height: 60px;
            border: 5px solid rgba(255,255,255,0.18);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .status {
            margin-top: 1rem;
            padding: 1.1rem 1.2rem;
            border-radius: 1.2rem;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: var(--text-body);
            font-size: 2.5rem;
            line-height: 1.35;
        }
        .status.success {
            color: #d8ffe5;
            border-color: rgba(34, 197, 94, 0.3);
            background: rgba(34, 197, 94, 0.14);
        }
        .status.error,
        .expired {
            color: #ffe0e6;
            border-color: rgba(251, 113, 133, 0.28);
            background: rgba(244, 63, 94, 0.14);
        }
        .card-inner {
            width: 100%;
            max-width: 34rem;
            padding: 0.25rem 0.1rem 0;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            justify-content: center;
            gap: 0.85rem;
            margin: 0 auto;
        }
        #map-container {
            display: none;
            margin-top: 1.25rem;
            border-radius: 1.2rem;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
            box-shadow: var(--shadow-lg);
        }
        #map-container iframe {
            width: 100%;
            height: clamp(320px, 52dvh, 640px);
            border: 0;
        }
        .map-label {
            font-size: 2rem;
            color: var(--text-muted);
            margin-top: 0.75rem;
        }
        .confirm-row {
            display: none;
            margin-top: 1rem;
            gap: 0.7rem;
            flex-direction: column;
        }
        .btn-confirm {
            display: inline-block;
            background: linear-gradient(180deg, #33d17a, #16a34a);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.2rem;
            padding: 1.3rem 1.5rem;
            font-size: 2.5rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 18px 30px rgba(22, 163, 74, 0.22);
        }
        .btn-retry {
            display: inline-block;
            background: rgba(255,255,255,0.06);
            color: var(--text-main);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1.2rem;
            padding: 1.1rem 1.5rem;
            font-size: 2.2rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .btn-confirm:hover,
        .btn-confirm:focus-visible {
            background: linear-gradient(180deg, #4ce288, #1aa14f);
            outline: none;
        }
        .btn-retry:hover,
        .btn-retry:focus-visible {
            background: rgba(255,255,255,0.1);
            outline: none;
        }

        @media (max-width: 719px) {
            .card {
                padding-top: calc(1rem + env(safe-area-inset-top));
                padding-bottom: calc(1.2rem + env(safe-area-inset-bottom));
            }

            .card-inner {
                justify-content: flex-start;
                gap: 0.95rem;
                padding: 0.2rem 0.35rem 0;
            }

            .brand {
                margin-bottom: 0.35rem;
            }

            .brand-mark {
                width: 5rem;
                height: 4.2rem;
            }

            .brand-name {
                font-size: 1.6rem;
            }

            .brand-tagline {
                font-size: 1.4rem;
            }

            .icon {
                font-size: 4.5rem;
                margin-bottom: 0.25rem;
            }

            p {
                font-size: 2.2rem;
                margin-bottom: 0.9rem;
            }

            h1 {
                font-size: clamp(3rem, 11vw, 3.8rem);
            }

            #map-container {
                margin-top: 1rem;
                margin-right: 0;
                margin-left: 0;
            }

            #map-container iframe {
                height: 56dvh;
                min-height: 320px;
            }

            .confirm-row {
                position: sticky;
                bottom: 0;
                padding: 0.95rem 0 calc(0.95rem + env(safe-area-inset-bottom));
                background: linear-gradient(to top, rgba(6, 10, 19, 0.98), rgba(6, 10, 19, 0.7));
                backdrop-filter: blur(10px);
            }

            .btn,
            .btn-confirm,
            .btn-retry {
                max-width: none;
                min-height: 3.5rem;
            }
        }

        @media (min-width: 720px) {
            .helper-note {
                margin-bottom: 1.35rem;
            }
        }
    </style>
</head>
<body>
    @php
        $brandInitials = collect(preg_split('/\s+/', trim($companyName)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp
    <div class="card">
        <div class="card-inner">
            <div class="brand" aria-label="{{ $companyName }} branding">
                <div class="brand-mark" aria-hidden="true">
                    @if(!empty($companyLogoUrl))
                        <img src="{{ $companyLogoUrl }}" alt="" loading="eager" decoding="async" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    @endif
                    <span class="brand-fallback" @if(!empty($companyLogoUrl)) style="display:none;" @endif>{{ $brandInitials }}</span>
                </div>
                <div class="brand-copy">
                    <span class="brand-name">{{ $companyName }}</span>
                    <span class="brand-tagline">{{ $companyTagline }}</span>
                </div>
            </div>
        @if ($expired)
            <div class="icon">⏰</div>
            <h1>Link Expired</h1>
            <p>This link has expired or was already used. Contact us if you need help.</p>
        @else
            <div class="eyebrow">Secure Location Check-In</div>
            <div class="icon">📍</div>
            <h1>Share Your Location</h1>
            <p class="hero-copy">
                Tap below so your {{ $companyName }} team can find you.
            </p>
            <div class="helper-note">Your location is only shared once for this request.</div>

            <input type="hidden" id="maps-api-key" value="{{ $mapsApiKey ?? '' }}">
            <button id="shareBtn" class="btn" onclick="getLocation()">Share My Location</button>
            <div id="spinner" class="spinner"></div>
            <div id="status" class="status"></div>

            <div id="map-container">
                <iframe id="map-frame" src="" allowfullscreen loading="lazy"></iframe>
            </div>
            <p id="map-label" class="map-label" style="display:none;">Not right? Tap "Try again" below.</p>

            <div id="confirm-row" class="confirm-row">
                <button class="btn-confirm" onclick="confirmDone()">Yes, that&rsquo;s correct!</button>
                <button class="btn-retry" onclick="retryLocation()">No, try again</button>
            </div>

            <script>
                var pendingLocation = null;

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
                    btn.textContent = 'Getting location…';
                    spinner.style.display = 'block';
                    status.textContent = '';

                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            spinner.style.display = 'none';

                            pendingLocation = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy,
                            };

                            showMap(pendingLocation.latitude, pendingLocation.longitude);
                        },
                        function (err) {
                            spinner.style.display = 'none';
                            btn.disabled = false;
                            btn.textContent = 'Share My Location';

                            var msg = 'Unable to get your location.';
                            if (err.code === 1) {
                                msg = 'Permission denied. Allow location access in your browser settings, then try again.';
                            } else if (err.code === 2) {
                                msg = 'Location unavailable. Make sure GPS is on.';
                            } else if (err.code === 3) {
                                msg = 'Timed out. Please try again.';
                            }

                            status.textContent = msg;
                            status.className = 'status error';
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
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

                    status.textContent = 'Is this pin correct?';
                    status.className = 'status';
                    btn.style.display = 'none';

                    var mapsApiKey = document.getElementById('maps-api-key').value;
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
                    confirmBtn.textContent = 'Sending…';
                    retryBtn.style.display = 'none';
                    status.textContent = 'Sending…';
                    status.className = 'status';

                    fetch(@json(route('locate.store', ['token' => $token])), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(pendingLocation),
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            status.textContent = '✅ Location sent! Help is on the way.';
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
        @endif
        </div>
    </div>
</body>
</html>
