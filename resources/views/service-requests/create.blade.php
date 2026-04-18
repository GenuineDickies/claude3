{{--
  New Service Request — service-requests.create
  Controller vars: $serviceCategories, $companyName, $lead (optional)
  Design notes:
    - 3-step wizard collapsed into single-page form for internal dispatcher speed.
    - Wizard-step DOM IDs preserved so the existing JS
      (resources/js/service-request-create.js) keeps working for:
        • phone formatting (#phone) → customer lookup → modal match →
          vehicle auto-fill from customer's prior vehicle record
        • "create_new" / "use_existing" customer_action hidden input
      The hidden next/back buttons are present but display:none so the JS
      event listeners attach without error; they simply aren't reachable by
      the user. All validation logic runs server-side on submit.
  Features preserved:
    - CSRF, lead_id hidden, customer_action hidden input
    - First/Last/Phone customer fields
    - Customer-status inline hint (for the JS to write into)
    - Customer match modal (#customer-modal + buttons)
    - Vehicle year/make/model/color (all required for the form per JS logic)
    - Catalog item select with optgroups + data-price (auto-fills quoted price)
    - Quoted price with $ prefix
    - Address (street/city/state) optional
    - Notes
    - Verbal SMS opt-in checkbox + toggleable script
    - Send-GPS-request checkbox (checked by default)
    - Rapid Mode link
    - Session success + validation errors
    - Pre-fill from request() params and lead $lead
--}}
@extends('layouts.app')

@section('content')

{{-- Hide wizard chrome (kept in DOM for JS compat, invisible to user) --}}
<style>
    .step-indicator, #step-line-1, #step-line-2,
    #btn-next-1, #btn-next-2, #btn-back-2, #btn-back-3 { display: none !important; }
    .wizard-step { display: block !important; }
</style>

<div class="max-w-7xl mx-auto">

    {{-- Toolbar --}}
    <div class="page-toolbar">
        <div>
            <div class="page-toolbar__title">New Service Request</div>
            <div class="page-toolbar__meta">Dispatch service request — internal use</div>
        </div>
        <div class="page-toolbar__actions">
            <a href="{{ route('rapid-dispatch.create') }}" class="btn-crystal-amber btn-crystal-sm inline-flex items-center">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/>
                </svg>
                Rapid Mode
            </a>
            <a href="{{ route('service-requests.index') }}" class="btn-crystal-secondary btn-crystal-sm">Cancel</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-green-200" role="alert">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-200" role="alert">
            <strong class="font-bold">Whoops!</strong>
            <span>There were some problems with your input.</span>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Hidden step indicator (kept for JS compat) --}}
    <div class="mb-2" style="display:none">
        <div class="flex items-center justify-between">
            <div class="flex items-center step-indicator" data-step="1">
                <div id="step-circle-1" class="flex items-center justify-center w-8 h-8 rounded-full border border-cyan-400/30 bg-cyan-500/15 text-cyan-200 text-sm font-bold">1</div>
                <span id="step-label-1" class="ml-2 text-sm font-medium text-cyan-400">Customer</span>
            </div>
            <div class="flex-1 h-0.5 mx-4 bg-white/10" id="step-line-1"></div>
            <div class="flex items-center step-indicator" data-step="2">
                <div id="step-circle-2" class="flex items-center justify-center w-8 h-8 rounded-full border border-white/10 bg-white/5 text-gray-400 text-sm font-bold">2</div>
                <span id="step-label-2" class="ml-2 text-sm font-medium text-gray-500">Vehicle &amp; Service</span>
            </div>
            <div class="flex-1 h-0.5 mx-4 bg-white/10" id="step-line-2"></div>
            <div class="flex items-center step-indicator" data-step="3">
                <div id="step-circle-3" class="flex items-center justify-center w-8 h-8 rounded-full border border-white/10 bg-white/5 text-gray-400 text-sm font-bold">3</div>
                <span id="step-label-3" class="ml-2 text-sm font-medium text-gray-500">Location &amp; Notes</span>
            </div>
        </div>
    </div>

    <form action="{{ route('service-requests.store') }}" method="POST" id="service-request-form"
          data-customer-search-url="{{ \App\Support\RequestPath::prefixed(request(), '/api/customers/search') }}">
        @csrf
        <input type="hidden" name="customer_action" id="customer_action" value="create_new">
        @if ($lead)
            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
        @endif

        {{-- ══ Customer (wizard step 1) ═══════════════════════════════════════ --}}
        <div id="wizard-step-1" class="wizard-step">
            <div class="form-section">
                <div class="form-section__head">
                    <div class="form-section__title">Customer</div>
                    <div class="form-section__hint">Phone number triggers customer lookup — match modal will appear if found.</div>
                </div>
                <div class="field-grid">
                    <div class="f-4">
                        <label for="phone" class="block text-xs font-medium text-gray-400 mb-1">Phone <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" id="phone" required maxlength="14"
                               value="{{ old('phone', request('phone')) }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="(555) 123-4567">
                        <div id="customer-status" class="mt-1 text-xs h-5"></div>
                    </div>
                    <div class="f-4">
                        <label for="first_name" class="block text-xs font-medium text-gray-400 mb-1">First name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="first_name" required
                               value="{{ old('first_name', request('first_name')) }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="John">
                    </div>
                    <div class="f-4">
                        <label for="last_name" class="block text-xs font-medium text-gray-400 mb-1">Last name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="last_name" required
                               value="{{ old('last_name', request('last_name')) }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="Doe">
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Vehicle + Service (wizard step 2) ══════════════════════════════ --}}
        <div id="wizard-step-2" class="wizard-step">
            <div class="form-section">
                <div class="form-section__head">
                    <div class="form-section__title">Vehicle</div>
                    <div class="form-section__hint">Auto-fills from the customer's last service request when a match is found.</div>
                </div>
                <div class="field-grid">
                    <div class="f-2">
                        <label for="vehicle_year" class="block text-xs font-medium text-gray-400 mb-1">Year <span class="text-red-500">*</span></label>
                        <input type="text" name="vehicle_year" id="vehicle_year" required maxlength="4"
                               value="{{ old('vehicle_year') }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="2024">
                    </div>
                    <div class="f-3">
                        <label for="vehicle_make" class="block text-xs font-medium text-gray-400 mb-1">Make <span class="text-red-500">*</span></label>
                        <input type="text" name="vehicle_make" id="vehicle_make" required
                               value="{{ old('vehicle_make') }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="Toyota">
                    </div>
                    <div class="f-4">
                        <label for="vehicle_model" class="block text-xs font-medium text-gray-400 mb-1">Model <span class="text-red-500">*</span></label>
                        <input type="text" name="vehicle_model" id="vehicle_model" required
                               value="{{ old('vehicle_model') }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="Camry">
                    </div>
                    <div class="f-3">
                        <label for="vehicle_color" class="block text-xs font-medium text-gray-400 mb-1">Color</label>
                        <input type="text" name="vehicle_color" id="vehicle_color"
                               value="{{ old('vehicle_color') }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="Silver">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section__head">
                    <div class="form-section__title">Service &amp; Pricing</div>
                    <div class="form-section__hint">Selecting a catalog service auto-fills the quoted price — edit if needed.</div>
                </div>
                <div class="field-grid">
                    <div class="f-7">
                        <label for="catalog_item_id" class="block text-xs font-medium text-gray-400 mb-1">Service <span class="text-red-500">*</span></label>
                        <select name="catalog_item_id" id="catalog_item_id" required
                                class="select-crystal block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border bg-transparent">
                            <option value="">Select a service…</option>
                            @foreach ($serviceCategories as $category)
                                <optgroup label="{{ $category->name }}">
                                    @foreach ($category->items as $item)
                                        <option value="{{ $item->id }}" data-price="{{ $item->base_cost }}" {{ old('catalog_item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="f-5">
                        <label for="quoted_price" class="block text-xs font-medium text-gray-400 mb-1">Quoted price <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">$</span>
                            </div>
                            <input type="number" name="quoted_price" id="quoted_price" required step="0.01" min="0"
                                   value="{{ old('quoted_price') }}"
                                   class="block w-full pl-7 rounded-md border-white/10 input-crystal text-sm p-2 border"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Location & Notes (wizard step 3) ═══════════════════════════════ --}}
        <div id="wizard-step-3" class="wizard-step">
            <div class="form-section">
                <div class="form-section__head">
                    <div class="form-section__title">Location &amp; Notes</div>
                    <div class="form-section__hint">Address is optional — can be filled later when the customer shares their GPS link.</div>
                </div>
                <div class="field-grid">
                    <div class="f-12">
                        <label for="street_address" class="block text-xs font-medium text-gray-400 mb-1">Street</label>
                        <input type="text" name="street_address" id="street_address" maxlength="255"
                               value="{{ old('street_address', request('street_address')) }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="e.g. 123 Main St">
                    </div>
                    <div class="f-8">
                        <label for="city" class="block text-xs font-medium text-gray-400 mb-1">City</label>
                        <input type="text" name="city" id="city" maxlength="100"
                               value="{{ old('city') }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="e.g. Tampa">
                    </div>
                    <div class="f-4">
                        <label for="state" class="block text-xs font-medium text-gray-400 mb-1">State</label>
                        <input type="text" name="state" id="state" maxlength="100"
                               value="{{ old('state') }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="e.g. FL">
                    </div>
                    <div class="f-12">
                        <label for="notes" class="block text-xs font-medium text-gray-400 mb-1">Notes</label>
                        <input type="text" name="notes" id="notes" maxlength="1000"
                               value="{{ old('notes', request('notes')) }}"
                               class="block w-full rounded-md border-white/10 shadow-sm input-crystal text-sm p-2 border"
                               placeholder="e.g. Flat tire, driver side rear">
                    </div>
                </div>
            </div>

            {{-- Verbal SMS opt-in --}}
            <div class="consent-callout mt-3" id="verbal-optin-section">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="verbal_opt_in" id="verbal_opt_in" value="1"
                           class="mt-1 rounded border-white/10 text-amber-600 focus:ring-amber-500">
                    <div class="w-full">
                        <div class="consent-callout__title">Customer verbally opts in to SMS</div>
                        <p class="text-xs text-gray-500 mt-0.5">Read the script below, then check the box to confirm the customer agreed.</p>
                        <div id="optin-script-toggle" class="mt-2">
                            <button type="button" onclick="toggleScript()" class="text-xs text-amber-400 underline font-medium focus:outline-none">Show opt-in script</button>
                        </div>
                        <div id="optin-script" class="hidden mt-3 surface-0 border border-amber-500/30 rounded-md p-3 text-xs text-gray-300 leading-relaxed">
                            <p class="font-semibold text-amber-200 mb-2">Read to customer:</p>
                            <blockquote class="italic border-l-4 border-amber-300 pl-3">
                                &ldquo;By providing your phone number, you consent to receive SMS messages from {{ $companyName }} regarding your service, including status updates and location requests. Message frequency varies. Message and data rates may apply. You can reply STOP at any time to opt out, or HELP for assistance. Consent is not a condition of purchase or service. Do you agree?&rdquo;
                            </blockquote>
                            <p class="mt-2 text-[11px] text-gray-500"><strong>If the customer says yes</strong>, check the box above to record their verbal consent.</p>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function toggleScript() {
                    var script = document.getElementById('optin-script');
                    var toggle = document.getElementById('optin-script-toggle').querySelector('button');
                    if (script.classList.contains('hidden')) {
                        script.classList.remove('hidden');
                        toggle.textContent = 'Hide opt-in script';
                    } else {
                        script.classList.add('hidden');
                        toggle.textContent = 'Show opt-in script';
                    }
                }
            </script>

            {{-- Send GPS location request --}}
            <div class="consent-callout consent-callout--info mt-3">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="send_location_request" id="send_location_request" value="1" checked
                           class="mt-1 rounded border-white/10 text-cyan-400 focus:ring-cyan-500">
                    <div>
                        <div class="consent-callout__title">Send GPS location request via SMS</div>
                        <p class="text-xs text-gray-500 mt-0.5">Texts the customer a link to share their exact GPS location. If they haven't opted in yet, an opt-in message will be sent first.</p>
                    </div>
                </div>
            </div>

            {{-- Hidden wizard buttons (kept for JS compat) --}}
            <button type="button" id="btn-next-1" disabled aria-hidden="true" tabindex="-1">Next</button>
            <button type="button" id="btn-next-2" aria-hidden="true" tabindex="-1">Next</button>
            <button type="button" id="btn-back-2" aria-hidden="true" tabindex="-1">Back</button>
            <button type="button" id="btn-back-3" aria-hidden="true" tabindex="-1">Back</button>
        </div>

        {{-- Sticky action bar --}}
        <div class="sticky-actions">
            <div class="sticky-actions__meta">
                <span class="sticky-actions__meta-dot"></span>
                Single-page form — all sections submit together
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('service-requests.index') }}" class="btn-crystal-secondary btn-crystal-sm">Cancel</a>
                <button type="submit" class="btn-crystal-success btn-crystal-sm inline-flex items-center">
                    <svg class="mr-1.5 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Create Service Request
                </button>
            </div>
        </div>

    </form>
</div>

{{-- Customer Match Modal (unchanged — triggered from JS) --}}
<div id="customer-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-[rgba(5,8,16,0.82)] backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom surface-2 rounded-lg text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="surface-2 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full border border-cyan-400/30 bg-cyan-500/10 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Existing Customer Found</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">We found an active customer with this phone number:</p>
                            <p class="text-xl font-bold text-white mt-2" id="modal-customer-name"></p>
                            <p class="text-sm text-gray-500 mt-2">Is this the same customer calling?</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white/5 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="btn-same-customer" class="w-full inline-flex justify-center btn-crystal px-4 py-2 text-base font-medium rounded-md sm:ml-3 sm:w-auto sm:text-sm">
                    Yes, Same Customer
                </button>
                <button type="button" id="btn-new-customer" class="mt-3 w-full inline-flex justify-center btn-crystal-secondary px-4 py-2 text-base font-medium rounded-md sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    No, New Customer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
