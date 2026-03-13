@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="surface-1 p-6">
        <h1 class="text-2xl font-bold text-white mb-2">Estimate Approval</h1>
        <p class="text-sm text-gray-400 mb-6">{{ $companyName }} has sent you an estimate for review.</p>

        {{-- Estimate Summary --}}
        <div class="mb-6 p-4 rounded-lg bg-white/5 space-y-2">
            <p class="text-sm text-gray-300">
                <span class="font-semibold">Estimate:</span> {{ $estimate->displayNumber() }}
            </p>
            @if($estimate->serviceRequest?->customer)
                <p class="text-sm text-gray-300">
                    <span class="font-semibold">Customer:</span>
                    {{ $estimate->serviceRequest->customer->first_name }} {{ $estimate->serviceRequest->customer->last_name }}
                </p>
            @endif
        </div>

        {{-- Line Items --}}
        <div class="mb-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-white/5 border-b border-white/10">
                    <tr>
                        <th class="text-left px-3 py-2 text-xs font-semibold text-gray-400 uppercase">Item</th>
                        <th class="text-right px-3 py-2 text-xs font-semibold text-gray-400 uppercase w-20">Price</th>
                        <th class="text-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase w-16">Qty</th>
                        <th class="text-right px-3 py-2 text-xs font-semibold text-gray-400 uppercase w-24">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($estimate->items as $item)
                        <tr>
                            <td class="px-3 py-2">
                                <span class="font-medium text-white">{{ $item->name }}</span>
                                @if($item->description)
                                    <p class="text-xs text-gray-400">{{ $item->description }}</p>
                                @endif
                            </td>
                            <td class="text-right px-3 py-2 font-mono text-gray-300">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-center px-3 py-2 text-gray-300">{{ $item->quantity + 0 }}</td>
                            <td class="text-right px-3 py-2 font-mono font-semibold text-white">${{ number_format($item->lineTotal(), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="mb-6 p-4 rounded-lg bg-cyan-500/10 flex flex-col items-end space-y-1 text-sm">
            <div class="flex justify-between w-48">
                <span class="text-gray-500">Subtotal</span>
                <span class="font-mono font-medium">${{ number_format($estimate->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between w-48">
                <span class="text-gray-500">Tax</span>
                <span class="font-mono font-medium">${{ number_format($estimate->tax_amount, 2) }}</span>
            </div>
            <div class="flex justify-between w-48 border-t border-cyan-500/30 pt-1.5 mt-1">
                <span class="font-bold text-white">Total</span>
                <span class="text-lg font-bold font-mono text-cyan-400">${{ number_format($estimate->total, 2) }}</span>
            </div>
        </div>

        @if($estimate->notes)
            <div class="mb-6 p-4 rounded-lg bg-white/5">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Notes</p>
                <p class="text-sm text-gray-300">{{ $estimate->notes }}</p>
            </div>
        @endif

        {{-- Approval Form --}}
        <form method="POST" action="{{ route('estimate-approval.store', $estimate->approval_token) }}" class="space-y-4">
            @csrf

            @if($errors->any())
                <div class="bg-red-50 border border-red-500/30 text-red-700 px-4 py-3 rounded text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label for="signer_name" class="block text-sm font-medium text-white">Your Name</label>
                <input id="signer_name" name="signer_name" type="text" value="{{ old('signer_name') }}"
                       class="mt-1 w-full border border-white/10 rounded-md px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-white mb-1">Signature</label>
                <div class="border border-white/10 rounded-md bg-white/90" style="touch-action: none;">
                    <canvas id="signature-pad" width="500" height="150" class="w-full cursor-crosshair"></canvas>
                </div>
                <input type="hidden" name="signature_data" id="signature_data">
                <button type="button" onclick="clearSignature()" class="text-xs text-gray-500 hover:text-gray-300 mt-1 underline">Clear signature</button>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" name="decision" value="accepted"
                        class="bg-green-600 text-white px-5 py-2.5 rounded-md text-sm font-medium hover:bg-green-700 transition">
                    Approve Estimate
                </button>
                <button type="submit" name="decision" value="declined"
                        class="bg-red-600 text-white px-5 py-2.5 rounded-md text-sm font-medium hover:bg-red-700 transition">
                    Decline
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    let drawing = false;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height)
        };
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
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000';
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function stopDraw() {
        drawing = false;
        document.getElementById('signature_data').value = canvas.toDataURL();
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', startDraw);
    canvas.addEventListener('touchmove', draw);
    canvas.addEventListener('touchend', stopDraw);

    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signature_data').value = '';
    }
</script>
@endsection
