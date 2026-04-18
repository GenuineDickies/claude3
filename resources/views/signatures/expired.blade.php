{{--
    PUBLIC-FACING SIGNATURE EXPIRED PAGE
    Preserved features:
      - Standalone HTML (no layout extend); branded card (max-width: 400px; do NOT widen)
      - Static "signing link expired" message
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0e17; color: #e5e7eb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: rgba(26, 32, 53, 0.95); border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.4); border: 1px solid rgba(255,255,255,0.08); max-width: 400px; width: 100%; padding: 2rem; text-align: center; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: .5rem; }
        p { font-size: .875rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Link Expired</h1>
        <p>This signing link has expired. Please contact the service provider to request a new one.</p>
    </div>
</body>
</html>
