<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Share Your Location | {{ $companyName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; min-height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1a202c;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
            overflow-x: hidden;
        }
        .card {
            background: #fff;
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            padding: 1.5rem 1rem 2rem;
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
                width: min(100%, 720px);
                min-height: min(100dvh - 4rem, 880px);
                border-radius: 1.5rem;
                box-shadow: 0 8px 40px rgba(0,0,0,0.12);
                flex: none;
                padding: 2rem 1.75rem 2.25rem;
            }
        }
        .icon { font-size: 3.5rem; margin-bottom: 1rem; }
        h1 { font-size: clamp(1.8rem, 4vw, 2.35rem); margin-bottom: 0.75rem; }
        p { color: #4a5568; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.25rem; }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.875rem;
            margin-bottom: 1rem;
            text-align: left;
        }
        .brand-mark {
            width: 3.5rem;
            height: 3.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .brand-mark img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 8px 20px rgba(15, 23, 42, 0.18));
        }
        .brand-copy {
            min-width: 0;
        }
        .brand-name {
            display: block;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .brand-tagline {
            display: block;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.35;
            margin-top: 0.15rem;
        }
        .eyebrow {
            display: inline-flex;
            align-self: center;
            padding: 0.3rem 0.7rem;
            border-radius: 9999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
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
        .expired { color: #dc2626; }
        .card-inner {
            width: 100%;
            max-width: none;
            padding: 0;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            justify-content: center;
            gap: 0.75rem;
            margin: 0 auto;
        }
        #map-container {
            display: none;
            margin-top: 1.25rem;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #e2e8f0;
        }
        #map-container iframe {
            width: 100%;
            height: clamp(320px, 52dvh, 640px);
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
        }
        .btn-confirm:hover { background: #15803d; }
        .btn-retry:hover { background: #cbd5e1; }

        @media (max-width: 599px) {
            .card {
                padding-top: calc(1rem + env(safe-area-inset-top));
                padding-right: 0;
                padding-bottom: calc(1.25rem + env(safe-area-inset-bottom));
                padding-left: 0;
            }

            .card-inner {
                justify-content: flex-start;
                gap: 0.875rem;
                padding: 0.5rem 1rem 0;
            }

            .brand {
                justify-content: flex-start;
                margin-bottom: 0.25rem;
            }

            .brand-mark {
                width: 3rem;
                height: 3rem;
            }

            .brand-name {
                font-size: 0.95rem;
            }

            .brand-tagline {
                font-size: 0.78rem;
            }

            .icon {
                font-size: 3rem;
                margin-bottom: 0.5rem;
            }

            p {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            #map-container {
                margin-top: 1rem;
                margin-right: -1rem;
                margin-left: -1rem;
                border-right: 0;
                border-left: 0;
                border-radius: 0;
            }

            #map-container iframe {
                height: 56dvh;
                min-height: 320px;
            }

            .confirm-row {
                position: sticky;
                bottom: 0;
                margin-right: -1rem;
                margin-left: -1rem;
                padding: 0.875rem 1rem calc(0.875rem + env(safe-area-inset-bottom));
                background: linear-gradient(to top, rgba(248, 250, 252, 0.98), rgba(248, 250, 252, 0.88));
                backdrop-filter: blur(10px);
            }

            .btn,
            .btn-confirm,
            .btn-retry {
                max-width: none;
                border-radius: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-inner">
            <div class="brand" aria-label="{{ $companyName }} branding">
                <div class="brand-mark" aria-hidden="true">
                    <img src="{{ asset('images/company-logo.jpg') }}" alt="" loading="eager" decoding="async">
                </div>
                <div class="brand-copy">
                    <span class="brand-name">{{ $companyName }}</span>
                    <span class="brand-tagline">{{ $companyTagline }}</span>
                </div>
            </div>
        @if ($expired)
            <div class="icon">⏰</div>
            <h1>Link Expired</h1>
            <p>This location-sharing link has expired or has already been used. Please contact us if you still need assistance.</p>
        @else
            <div class="eyebrow">Secure Location Check-In</div>
            <div class="icon">📍</div>
            <h1>Share Your Location</h1>
            <p>
                Your {{ $companyName }} team needs your location to reach you.
                Tap the button below to share your current GPS position.
            </p>

            <input type="hidden" id="maps-api-key" value="{{ $mapsApiKey ?? '' }}">
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

                    status.textContent = 'Is this pin on your location? Please confirm before we send it.';
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
                    status.textContent = 'Sending your confirmed location…';
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
                            status.textContent = '✅ Location confirmed and sent! Help is on the way.';
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
