{{-- Rapid Dispatch Page — rapid-dispatch.store | Feature preservation notes: Alpine component rapidDispatch() with shorthand lookup, phone formatter, customer lookup (api.customers.search), shorthand parser (rapid-dispatch.parse), catalog item price updater; Header with Quick Mode badge and Full Form link (service-requests.create); Shorthand quick-service lookup input with matched feedback; Main dispatch form (POST rapid-dispatch.store) with @csrf: phone (required, formatted), first_name/last_name (required), catalog_item_id select grouped by serviceCategories with old() + data-price, quoted_price (required), location, notes, send_location_request checkbox; Errors block; Submit button with submitting state. Layout: max-w-5xl compromise (rapid is narrow-focus quick form); All Alpine state, forms, routes, and PHP logic kept intact. --}}
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto" x-data="rapidDispatch()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-white">Rapid Dispatch</h1>
            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded">Quick Mode</span>
        </div>
        <a href="{{ route('service-requests.create') }}"
           class="text-sm text-gray-500 hover:text-gray-300 underline">Full Form &rarr;</a>
    </div>

    {{-- Shorthand Input --}}
    <div class="surface-1 p-5 mb-4">
        <label for="shorthand" class="block text-sm font-medium text-gray-300 mb-1">Quick Service Lookup</label>
        <div class="relative">
            <input type="text"
                   id="shorthand"
                   x-model="shorthand"
                   @input.debounce.300ms="parseShorthand()"
                   placeholder="Type: jump start, flat tire, lockout, tow, fuel, winch…"
                   class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-3 border pr-10"
                   autocomplete="off">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <template x-if="shorthandLoading">
                    <svg class="animate-spin h-4 w-4 text-gray-400" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <template x-if="!shorthandLoading && shorthandMatched">
                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </template>
            </div>
        </div>
        <p x-show="shorthandMatched" x-cloak class="mt-1 text-sm text-green-400">
            Matched: <strong x-text="matchedName"></strong> — $<span x-text="matchedPrice"></span>
        </p>
        <p x-show="shorthand.length > 2 && !shorthandLoading && !shorthandMatched" x-cloak class="mt-1 text-sm text-gray-400">
            No match — select service manually below.
        </p>
    </div>

    {{-- Form --}}
    <form action="{{ route('rapid-dispatch.store') }}" method="POST" @submit="submitting = true"
          class="surface-1 divide-y divide-gray-100">
        @csrf

        @if ($errors->any())
            <div class="p-4 bg-red-50 text-red-700 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Customer --}}
        <div class="p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Customer</h3>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-300">Phone <span class="text-red-500">*</span></label>
                <input type="tel"
                       name="phone"
                       id="phone"
                       x-model="phone"
                       @input="formatPhone(); lookupCustomer()"
                       required
                       value="{{ old('phone') }}"
                       placeholder="(555) 123-4567"
                       class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border">
            </div>

            <div x-show="customerFound" x-cloak class="flex items-center gap-2 text-sm text-green-700 bg-green-500/10 p-2 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/></svg>
                Existing customer: <strong x-text="firstName + ' ' + lastName"></strong>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-300">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" id="first_name" x-model="firstName" required
                           value="{{ old('first_name') }}"
                           class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-300">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" id="last_name" x-model="lastName" required
                           value="{{ old('last_name') }}"
                           class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border">
                </div>
            </div>
        </div>

        {{-- Service --}}
        <div class="p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Service</h3>

            <div>
                <label for="catalog_item_id" class="block text-sm font-medium text-gray-300">Service <span class="text-red-500">*</span></label>
                <select name="catalog_item_id" id="catalog_item_id"
                        x-model="catalogItemId"
                        @change="updatePrice()"
                        required
                        class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border">
                    <option value="">— Select —</option>
                    @foreach ($serviceCategories as $cat)
                        <optgroup label="{{ $cat->name }}">
                            @foreach ($cat->items as $item)
                                <option value="{{ $item->id }}"
                                        data-price="{{ $item->base_cost }}"
                                        {{ old('catalog_item_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }} — ${{ number_format($item->base_cost, 2) }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="quoted_price" class="block text-sm font-medium text-gray-300">Quoted Price <span class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">$</span>
                    <input type="number" step="0.01" min="0" name="quoted_price" id="quoted_price"
                           x-model="quotedPrice" required
                           value="{{ old('quoted_price') }}"
                           class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 pl-7 border">
                </div>
            </div>
        </div>

        {{-- Location & Notes --}}
        <div class="p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Details</h3>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-300">Location</label>
                <input type="text" name="location" id="location"
                       value="{{ old('location') }}"
                       placeholder="Address or cross-streets"
                       class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border">
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-300">Notes</label>
                <textarea name="notes" id="notes" rows="2"
                          placeholder="Vehicle info, special instructions…"
                          class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-5 flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                <input type="checkbox" name="send_location_request" value="1" checked
                       class="rounded border-white/10 text-cyan-400 shadow-sm focus:ring-cyan-500">
                Send location request SMS
            </label>

            <button type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center px-5 py-2.5 btn-crystal text-sm disabled:opacity-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                <span x-text="submitting ? 'Creating…' : 'Dispatch'"></span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function rapidDispatch() {
    return {
        shorthand: '',
        shorthandLoading: false,
        shorthandMatched: false,
        matchedName: '',
        matchedPrice: '',
        phone: '{{ old('phone', '') }}',
        firstName: '{{ old('first_name', '') }}',
        lastName: '{{ old('last_name', '') }}',
        catalogItemId: '{{ old('catalog_item_id', '') }}',
        quotedPrice: '{{ old('quoted_price', '') }}',
        customerFound: false,
        submitting: false,
        lookupTimer: null,

        formatPhone() {
            let digits = this.phone.replace(/\D/g, '');
            if (digits.length > 10) digits = digits.substring(0, 10);
            if (digits.length >= 7) {
                this.phone = '(' + digits.substring(0, 3) + ') ' + digits.substring(3, 6) + '-' + digits.substring(6);
            } else if (digits.length >= 4) {
                this.phone = '(' + digits.substring(0, 3) + ') ' + digits.substring(3);
            }
        },

        lookupCustomer() {
            clearTimeout(this.lookupTimer);
            const digits = this.phone.replace(/\D/g, '');
            if (digits.length < 10) {
                this.customerFound = false;
                return;
            }
            this.lookupTimer = setTimeout(() => {
                fetch('{{ route('api.customers.search') }}?phone=' + encodeURIComponent(digits), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.customer) {
                        this.firstName = data.customer.first_name;
                        this.lastName = data.customer.last_name;
                        this.customerFound = true;
                    } else {
                        this.customerFound = false;
                    }
                })
                .catch(() => { this.customerFound = false; });
            }, 300);
        },

        parseShorthand() {
            if (this.shorthand.length < 2) {
                this.shorthandMatched = false;
                return;
            }
            this.shorthandLoading = true;
            fetch('{{ route('rapid-dispatch.parse') }}?q=' + encodeURIComponent(this.shorthand), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.shorthandLoading = false;
                if (data.matched) {
                    this.shorthandMatched = true;
                    this.matchedName = data.name;
                    this.matchedPrice = parseFloat(data.unit_price).toFixed(2);
                    this.catalogItemId = String(data.catalog_item_id);
                    this.quotedPrice = data.unit_price;
                } else {
                    this.shorthandMatched = false;
                }
            })
            .catch(() => {
                this.shorthandLoading = false;
                this.shorthandMatched = false;
            });
        },

        updatePrice() {
            const select = document.getElementById('catalog_item_id');
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.price) {
                this.quotedPrice = opt.dataset.price;
            }
        },
    };
}
</script>
@endpush
@endsection
