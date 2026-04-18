{{--
    PUBLIC-FACING LOCATE (SHARE LOCATION) PAGE
    Preserved features:
      - Standalone HTML (no layout extend); branded shell (max-width: 32rem; do NOT widen)
      - Brand header: $companyName, $companyTagline, optional $companyLogoUrl with initials fallback
      - Expired branch when $expired is true (static message, no form)
      - Active branch:
          * "Share My Location" button -> navigator.geolocation.getCurrentPosition with high accuracy
          * Map preview iframe (Google Maps Embed when $mapsApiKey present, else OpenStreetMap)
          * Confirm/Send via fetch POST to route('locate.store', ['token' => $token])
          * Manual Entry fallback: street/city/state inputs -> fetch POST to route('locate.store.manual', ['token' => $token])
          * Status region with success/error states; hidden $mapsApiKey input
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Share Your Location | {{ $companyName }}</title>
    <style>
        :root {
            --bg: #081326;
            --panel: rgba(15, 27, 50, 0.92);
            --panel-border: rgba(255, 255, 255, 0.14);
            --text-main: #f3f8ff;
            --text-muted: #c5d3ea;
            --accent: #2ad5ff;
            --accent-strong: #15b9df;
        }

        * { box-sizing: border-box; }

        html, body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(circle at top, rgba(42, 213, 255, 0.16), transparent 34%),
                linear-gradient(180deg, #0a1427, var(--bg));
        }

        .page {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: calc(1rem + env(safe-area-inset-top)) 1rem calc(1rem + env(safe-area-inset-bottom));
        }

        .shell {
            width: 100%;
            max-width: 32rem;
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-align: left;
            margin-bottom: 0.75rem;
        }

        .brand-mark {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-fallback {
            font-size: 1rem;
            font-weight: 700;
            color: #a5f3fc;
            display: none;
        }

        .brand-name {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .brand-tagline {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.1rem;
        }

        h1 {
            margin: 0.4rem 0 0.5rem;
            font-size: clamp(1.6rem, 6vw, 2rem);
            line-height: 1.15;
        }

        .copy {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.4;
            margin-bottom: 0.9rem;
        }

        .btn {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 0.8rem;
            background: linear-gradient(180deg, var(--accent), var(--accent-strong));
            color: #032034;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:disabled {
            opacity: 0.75;
            cursor: not-allowed;
        }

        .btn-confirm {
            background: linear-gradient(180deg, #4ade80, #22c55e);
            color: #062212;
        }

        .btn-muted {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
        }

        .map-wrap {
            display: none;
            margin-top: 0.75rem;
            border: 1px solid var(--panel-border);
            border-radius: 0.75rem;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.04);
        }

        .map-wrap iframe {
            width: 100%;
            height: clamp(190px, 37dvh, 320px);
            border: 0;
        }

        .preview-actions {
            display: none;
            margin-top: 0.7rem;
            gap: 0.55rem;
            grid-template-columns: 1fr;
        }

        .manual-entry {
            display: none;
            margin-top: 0.7rem;
            gap: 0.55rem;
            text-align: left;
        }

        .manual-entry label {
            display: block;
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 0.2rem;
        }

        .manual-entry input {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 0.65rem;
            padding: 0.6rem 0.7rem;
            background: rgba(255, 255, 255, 0.07);
            color: var(--text-main);
            font-size: 0.92rem;
        }

        .manual-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .status {
            margin-top: 0.75rem;
            min-height: 1.25rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .status.success { color: #dcfce7; }
        .status.error { color: #fecdd3; }
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

    <div class="page">
        <div class="shell">
            <div class="brand" aria-label="{{ $companyName }} branding">
                <div class="brand-mark" aria-hidden="true">
                    @if(!empty($companyLogoUrl))
                        <img src="{{ $companyLogoUrl }}" alt="" loading="eager" decoding="async" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    @endif
                    <span class="brand-fallback" @if(!empty($companyLogoUrl)) style="display:none;" @endif>{{ $brandInitials }}</span>
                </div>
                <div>
                    <div class="brand-name">{{ $companyName }}</div>
                    <div class="brand-tagline">{{ $companyTagline }}</div>
                </div>
            </div>

            @if ($expired)
                <h1>Link Expired</h1>
                <p class="copy">This link has expired or was already used. Contact us if you need help.</p>
            @else
                <h1>Share Your Location</h1>
                <p class="copy" id="copy">Tap the button below and we will receive your current location.</p>

                <button id="shareBtn" class="btn" onclick="shareLocation()">Share My Location</button>
                <div id="mapWrap" class="map-wrap">
                    <iframe id="mapFrame" src="" allowfullscreen loading="lazy"></iframe>
                </div>

                <div id="previewActions" class="preview-actions">
                    <button id="sendBtn" class="btn btn-confirm" onclick="confirmSend()">Send This Location</button>
                    <button class="btn btn-muted" onclick="showManualEntry()">Manual Entry</button>
                </div>

                <div id="manualEntry" class="manual-entry">
                    <div>
                        <label for="manualStreet">Street</label>
                        <input id="manualStreet" type="text" autocomplete="address-line1" placeholder="123 Main St">
                    </div>
                    <div>
                        <label for="manualCity">City</label>
                        <input id="manualCity" type="text" autocomplete="address-level2" placeholder="City">
                    </div>
                    <div>
                        <label for="manualState">State</label>
                        <input id="manualState" type="text" autocomplete="address-level1" placeholder="State">
                    </div>
                    <div class="manual-actions">
                        <button id="manualSubmitBtn" class="btn" onclick="submitManualEntry()">Submit Address</button>
                        <button class="btn btn-muted" onclick="hideManualEntry()">Back To Map</button>
                    </div>
                </div>

                <div id="status" class="status"></div>
                <input type="hidden" id="mapsApiKey" value="{{ $mapsApiKey ?? '' }}">

                <script>
                    var pendingLocation = null;

                    function shareLocation() {
                        var btn = document.getElementById('shareBtn');
                        var status = document.getElementById('status');
                        var mapWrap = document.getElementById('mapWrap');
                        var previewActions = document.getElementById('previewActions');
                        var manualEntry = document.getElementById('manualEntry');

                        if (!navigator.geolocation) {
                            status.textContent = 'Geolocation is not supported by your browser.';
                            status.className = 'status error';
                            return;
                        }

                        btn.disabled = true;
                        btn.textContent = 'Getting location...';
                        status.textContent = 'Getting your location...';
                        status.className = 'status';
                        mapWrap.style.display = 'none';
                        previewActions.style.display = 'none';
                        manualEntry.style.display = 'none';

                        navigator.geolocation.getCurrentPosition(
                            function (position) {
                                pendingLocation = {
                                    latitude: position.coords.latitude,
                                    longitude: position.coords.longitude,
                                    accuracy: position.coords.accuracy,
                                };

                                showPreview(position.coords.latitude, position.coords.longitude);
                                btn.disabled = false;
                                btn.style.display = 'none';
                                status.textContent = 'Confirm the map preview or use Manual Entry.';
                                status.className = 'status';
                            },
                            function (err) {
                                var msg = 'Unable to get your location.';
                                if (err.code === 1) {
                                    msg = 'Permission denied. Allow location access, then try again.';
                                } else if (err.code === 2) {
                                    msg = 'Location unavailable. Make sure GPS is on.';
                                } else if (err.code === 3) {
                                    msg = 'Timed out. Please try again.';
                                }

                                status.textContent = msg;
                                status.className = 'status error';
                                btn.disabled = false;
                                btn.textContent = 'Share My Location';
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 15000,
                                maximumAge: 0,
                            }
                        );
                    }

                    function showPreview(lat, lng) {
                        var mapWrap = document.getElementById('mapWrap');
                        var previewActions = document.getElementById('previewActions');
                        var mapFrame = document.getElementById('mapFrame');
                        var mapsApiKey = document.getElementById('mapsApiKey').value;

                        if (mapsApiKey) {
                            mapFrame.src = 'https://www.google.com/maps/embed/v1/place?key=' + encodeURIComponent(mapsApiKey) + '&q=' + lat + ',' + lng + '&zoom=14';
                        } else {
                            mapFrame.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + (lng - 0.02) + ',' + (lat - 0.02) + ',' + (lng + 0.02) + ',' + (lat + 0.02) + '&layer=mapnik&marker=' + lat + ',' + lng;
                        }

                        mapWrap.style.display = 'block';
                        previewActions.style.display = 'grid';
                    }

                    function confirmSend() {
                        if (!pendingLocation) return;

                        var status = document.getElementById('status');
                        var sendBtn = document.getElementById('sendBtn');

                        sendBtn.disabled = true;
                        sendBtn.textContent = 'Sending...';
                        status.textContent = 'Sending location...';
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
                                status.textContent = 'Location sent. Help is on the way.';
                                status.className = 'status success';
                                document.getElementById('previewActions').style.display = 'none';
                                document.getElementById('manualEntry').style.display = 'none';
                                pendingLocation = null;
                                return;
                            }

                            status.textContent = data.error || 'Something went wrong. Please try again.';
                            status.className = 'status error';
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Send This Location';
                        })
                        .catch(function () {
                            status.textContent = 'Network error. Please check your connection and try again.';
                            status.className = 'status error';
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Send This Location';
                        });
                    }

                    function showManualEntry() {
                        document.getElementById('manualEntry').style.display = 'grid';
                        document.getElementById('status').textContent = 'Enter your address manually below.';
                        document.getElementById('status').className = 'status';
                    }

                    function hideManualEntry() {
                        document.getElementById('manualEntry').style.display = 'none';
                    }

                    function submitManualEntry() {
                        var status = document.getElementById('status');
                        var submitBtn = document.getElementById('manualSubmitBtn');
                        var street = document.getElementById('manualStreet').value.trim();
                        var city = document.getElementById('manualCity').value.trim();
                        var state = document.getElementById('manualState').value.trim();

                        if (!street || !city || !state) {
                            status.textContent = 'Please enter street, city, and state.';
                            status.className = 'status error';
                            return;
                        }

                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Sending...';
                        status.textContent = 'Sending address...';
                        status.className = 'status';

                        fetch(@json(route('locate.store.manual', ['token' => $token])), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                street: street,
                                city: city,
                                state: state,
                            }),
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.ok) {
                                status.textContent = 'Address sent. Help is on the way.';
                                status.className = 'status success';
                                document.getElementById('previewActions').style.display = 'none';
                                document.getElementById('manualEntry').style.display = 'none';
                                return;
                            }

                            status.textContent = data.error || 'Unable to send address. Please try again.';
                            status.className = 'status error';
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit Address';
                        })
                        .catch(function () {
                            status.textContent = 'Network error. Please check your connection and try again.';
                            status.className = 'status error';
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit Address';
                        });
                    }
                </script>
            @endif
        </div>
    </div>
</body>
</html>
