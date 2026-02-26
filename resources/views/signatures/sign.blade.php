<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign - {{ $companyName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #374151; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.1); max-width: 480px; width: 100%; padding: 1.5rem; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: .25rem; }
        .subtitle { font-size: .875rem; color: #6b7280; margin-bottom: 1rem; }
        .info { font-size: .8rem; color: #6b7280; background: #f9fafb; border-radius: 8px; padding: .75rem; margin-bottom: 1rem; }
        .info strong { color: #374151; }
        .canvas-wrap { border: 2px solid #d1d5db; border-radius: 8px; position: relative; touch-action: none; margin-bottom: .75rem; background: #fff; }
        canvas { display: block; width: 100%; border-radius: 6px; cursor: crosshair; }
        .label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .25rem; }
        input[type="text"] { width: 100%; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: .875rem; outline: none; }
        input[type="text"]:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.2); }
        .actions { display: flex; gap: .5rem; margin-top: 1rem; }
        .btn { flex: 1; padding: .625rem; font-size: .875rem; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; text-align: center; }
        .btn-primary { background: #16a34a; color: #fff; }
        .btn-primary:hover { background: #15803d; }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #e5e7eb; }
        .error { color: #dc2626; font-size: .8rem; margin-top: .25rem; }
        .hint { font-size: .75rem; color: #9ca3af; text-align: center; margin-bottom: .5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $companyName }}</h1>
        <p class="subtitle">Please sign below to confirm service completion.</p>

        <div class="info">
            @if ($serviceRequest->customer)
                <strong>Customer:</strong> {{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}<br>
            @endif
            @if ($serviceRequest->serviceType)
                <strong>Service:</strong> {{ $serviceRequest->serviceType->name }}<br>
            @endif
            @if ($serviceRequest->location)
                <strong>Location:</strong> {{ $serviceRequest->location }}<br>
            @endif
            <strong>Date:</strong> {{ now()->format('M j, Y') }}
        </div>

        <form method="POST" action="{{ route('signature.store', $signature->token) }}" id="signForm">
            @csrf
            <input type="hidden" name="signature_data" id="signatureData">

            <p class="hint">Draw your signature in the box below</p>
            <div class="canvas-wrap" id="canvasWrap">
                <canvas id="sigCanvas" height="200"></canvas>
            </div>

            <label class="label" for="signer_name">Your Name <span style="color:#dc2626">*</span></label>
            <input type="text" name="signer_name" id="signer_name" required maxlength="200"
                   value="{{ $serviceRequest->customer?->first_name }} {{ $serviceRequest->customer?->last_name }}">

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <p class="error">{{ $error }}</p>
                @endforeach
            @endif

            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="clearCanvas()">Clear</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Submit Signature</button>
            </div>
        </form>
    </div>

    <script>
        const canvas = document.getElementById('sigCanvas');
        const ctx = canvas.getContext('2d');
        const wrap = document.getElementById('canvasWrap');
        let drawing = false;
        let hasDrawn = false;

        function resizeCanvas() {
            const rect = wrap.getBoundingClientRect();
            canvas.width = rect.width - 4;
            ctx.strokeStyle = '#1f2937';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        }
        resizeCanvas();
        window.addEventListener('resize', () => {
            if (!hasDrawn) resizeCanvas();
        });

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches ? e.touches[0] : e;
            return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
        }

        function startDraw(e) {
            e.preventDefault();
            drawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!drawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasDrawn = true;
            document.getElementById('submitBtn').disabled = false;
        }

        function endDraw(e) {
            if (drawing) { e.preventDefault(); drawing = false; }
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', endDraw, { passive: false });

        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
            document.getElementById('submitBtn').disabled = true;
        }

        document.getElementById('signForm').addEventListener('submit', function(e) {
            if (!hasDrawn) { e.preventDefault(); return; }
            document.getElementById('signatureData').value = canvas.toDataURL('image/png');
        });
    </script>
</body>
</html>
