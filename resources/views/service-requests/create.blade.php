@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto surface-2 p-4 sm:p-8 lg:p-10 overflow-hidden relative">
    <div class="absolute inset-0 opacity-70 pointer-events-none" style="background: radial-gradient(circle at top right, rgba(0,229,255,0.12), transparent 34%), radial-gradient(circle at bottom left, rgba(124,58,237,0.14), transparent 42%);"></div>
    <div class="relative flex justify-between items-center mb-6 border-b border-white/10 pb-4">
        <h2 class="text-xl sm:text-2xl font-bold text-white">New Service Request (Dispatch)</h2>
        <div class="flex items-center gap-2">
            <a href="{{ route('rapid-dispatch.create') }}"
               class="inline-flex items-center px-3 py-1.5 btn-crystal-amber text-xs font-medium rounded-md">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                Rapid Mode
            </a>
            <span class="hidden sm:inline-flex items-center rounded-full border border-cyan-400/30 bg-cyan-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-300">Internal Use Only</span>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-green-200" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-200" role="alert">
            <strong class="font-bold">Whoops!</strong>
            <span class="block sm:inline">There were some problems with your input.</span>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Step Indicator -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center step-indicator" data-step="1">
                <div id="step-circle-1" class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border border-cyan-400/30 bg-cyan-500/15 text-cyan-200 text-sm font-bold shadow-sm">1</div>
                <span id="step-label-1" class="hidden sm:inline ml-2 text-sm font-medium text-cyan-400">Customer</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 sm:mx-4 bg-white/10" id="step-line-1"></div>
            <div class="flex items-center step-indicator" data-step="2">
                <div id="step-circle-2" class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border border-white/10 bg-white/5 text-gray-400 text-sm font-bold">2</div>
                <span id="step-label-2" class="hidden sm:inline ml-2 text-sm font-medium text-gray-500">Vehicle & Service</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 sm:mx-4 bg-white/10" id="step-line-2"></div>
            <div class="flex items-center step-indicator" data-step="3">
                <div id="step-circle-3" class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border border-white/10 bg-white/5 text-gray-400 text-sm font-bold">3</div>
                <span id="step-label-3" class="hidden sm:inline ml-2 text-sm font-medium text-gray-500">Location & Notes</span>
            </div>
        </div>
    </div>

    <form action="{{ route('service-requests.store') }}" method="POST" id="service-request-form" class="space-y-6" data-customer-search-url="{{ \App\Support\RequestPath::prefixed(request(), '/api/customers/search') }}">
        @csrf
        <input type="hidden" name="customer_action" id="customer_action" value="create_new">

        <!-- Step 1: Customer Information -->
        <div id="wizard-step-1" class="wizard-step">
            <div class="surface-1 p-5 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-300 mb-4">Customer Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-300">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="first_name" required value="{{ old('first_name') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="John">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-300">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="last_name" required value="{{ old('last_name') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="Doe">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-300">Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" id="phone" required maxlength="14" value="{{ old('phone') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="(555) 123-4567">
                        <div id="customer-status" class="mt-2 text-sm h-5"></div>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <a href="/" class="inline-flex items-center btn-crystal-secondary py-2 px-4 text-sm font-medium rounded-md">
                    Cancel
                </a>
                <button type="button" id="btn-next-1" disabled class="flex justify-center py-2 px-4 btn-crystal text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none">
                    Next
                    <svg class="ml-2 w-4 h-4 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- Step 2: Vehicle & Service -->
        <div id="wizard-step-2" class="wizard-step hidden">
            <div class="surface-1 p-5 sm:p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-300 mb-4">Vehicle Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="vehicle_year" class="block text-sm font-medium text-gray-300">Year <span class="text-red-500">*</span></label>
                        <input type="text" name="vehicle_year" id="vehicle_year" required maxlength="4" value="{{ old('vehicle_year') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="2024">
                    </div>
                    <div>
                        <label for="vehicle_make" class="block text-sm font-medium text-gray-300">Make <span class="text-red-500">*</span></label>
                        <input type="text" name="vehicle_make" id="vehicle_make" required value="{{ old('vehicle_make') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="Toyota">
                    </div>
                    <div>
                        <label for="vehicle_model" class="block text-sm font-medium text-gray-300">Model <span class="text-red-500">*</span></label>
                        <input type="text" name="vehicle_model" id="vehicle_model" required value="{{ old('vehicle_model') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="Camry">
                    </div>
                    <div>
                        <label for="vehicle_color" class="block text-sm font-medium text-gray-300">Color</label>
                        <input type="text" name="vehicle_color" id="vehicle_color" value="{{ old('vehicle_color') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="Silver">
                    </div>
                </div>
            </div>

            <div class="surface-1 p-5 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-300 mb-4">Service</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="catalog_item_id" class="block text-sm font-medium text-gray-300">Service <span class="text-red-500">*</span></label>
                        <select name="catalog_item_id" id="catalog_item_id" required class="select-crystal mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border bg-transparent">
                            <option value="">Select a service...</option>
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
                    <div>
                        <label for="quoted_price" class="block text-sm font-medium text-gray-300">Quoted Price <span class="text-red-500">*</span></label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="quoted_price" id="quoted_price" required step="0.01" min="0" value="{{ old('quoted_price') }}" class="block w-full pl-7 rounded-md border-white/10 input-crystal sm:text-sm p-2 border" placeholder="0.00">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Auto-filled from catalog. Edit as needed.</p>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-between">
                <button type="button" id="btn-back-2" class="flex justify-center py-2 px-4 btn-crystal-secondary text-sm font-medium rounded-md">
                    <svg class="mr-2 w-4 h-4 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <button type="button" id="btn-next-2" class="flex justify-center py-2 px-4 btn-crystal text-sm font-medium rounded-md">
                    Next
                    <svg class="ml-2 w-4 h-4 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- Step 3: Location & Notes -->
        <div id="wizard-step-3" class="wizard-step hidden">
            <div class="surface-1 p-5 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-300 mb-4">Location & Notes</h3>
                <div class="space-y-4">
                    <div>
                        <label for="street_address" class="block text-sm font-medium text-gray-300">Street</label>
                        <input type="text" name="street_address" id="street_address" maxlength="255" value="{{ old('street_address') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="e.g. 123 Main St">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-300">City</label>
                            <input type="text" name="city" id="city" maxlength="100" value="{{ old('city') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="e.g. Tampa">
                        </div>
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-300">State</label>
                            <input type="text" name="state" id="state" maxlength="100" value="{{ old('state') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="e.g. FL">
                        </div>
                    </div>
                    <div>
                        <p class="mt-1 text-xs text-gray-500">Optional — leave all address fields blank if you do not have the location yet. You can also send a GPS location request via SMS after creating the ticket.</p>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-300">Notes</label>
                        <input type="text" name="notes" id="notes" maxlength="1000" value="{{ old('notes') }}" class="mt-1 block w-full rounded-md border-white/10 shadow-sm input-crystal sm:text-sm p-2 border" placeholder="e.g. Flat tire, driver side rear">
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 shadow-sm" id="verbal-optin-section">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="verbal_opt_in" id="verbal_opt_in" value="1"
                           class="mt-1 rounded border-white/10 text-amber-600 focus:ring-amber-500">
                    <div class="w-full">
                        <label for="verbal_opt_in" class="block text-sm font-semibold text-white">Customer verbally opts in to SMS</label>
                        <p class="text-xs text-gray-500 mt-0.5">Read the script below, then check the box to confirm the customer agreed.</p>

                        <div id="optin-script-toggle" class="mt-2">
                            <button type="button" onclick="toggleScript()" class="text-sm text-amber-700 underline font-medium focus:outline-none">Show opt-in script</button>
                        </div>
                        <div id="optin-script" class="hidden mt-3 surface-0 border border-amber-500/30 rounded-md p-4 text-sm text-gray-300 leading-relaxed">
                            <p class="font-semibold text-amber-200 mb-2">Read to customer:</p>
                            <blockquote class="italic border-l-4 border-amber-300 pl-3">
                                &ldquo;By providing your phone number, you consent to receive SMS messages from {{ $companyName }} regarding your service, including status updates and location requests. Message frequency varies. Message and data rates may apply. You can reply STOP at any time to opt out, or HELP for assistance. Consent is not a condition of purchase or service. Do you agree?&rdquo;
                            </blockquote>
                            <p class="mt-3 text-xs text-gray-500"><strong>If the customer says yes</strong>, check the box above to record their verbal consent.</p>
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

            <div class="mt-4 rounded-xl border border-cyan-500/30 bg-cyan-500/10 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="send_location_request" id="send_location_request" value="1" checked
                           class="mt-1 rounded border-white/10 text-cyan-400 focus:ring-cyan-500">
                    <div>
                        <label for="send_location_request" class="block text-sm font-semibold text-white">Send GPS location request via SMS</label>
                        <p class="text-xs text-gray-500 mt-0.5">Texts the customer a link to share their exact GPS location. If they haven't opted in yet, an opt-in message will be sent first.</p>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-between">
                <button type="button" id="btn-back-3" class="flex justify-center py-2 px-4 btn-crystal-secondary text-sm font-medium rounded-md">
                    <svg class="mr-2 w-4 h-4 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <button type="submit" class="flex justify-center py-2 px-4 btn-crystal-success text-sm font-medium rounded-md">
                    <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Create Service Request
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Customer Match Modal -->
<div id="customer-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-[rgba(5,8,16,0.82)] backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom surface-2 rounded-lg text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="surface-2 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full border border-cyan-400/30 bg-cyan-500/10 sm:mx-0 sm:h-10 sm:w-10">
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
