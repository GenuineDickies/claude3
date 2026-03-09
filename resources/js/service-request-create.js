/**
 * Service Request create page — multi-step wizard, phone formatting,
 * customer lookup with vehicle auto-fill, service type pricing.
 */
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('phone');
    if (!phoneInput) return; // guard: only run on the create page

    // --- DOM refs ---
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const statusDiv = document.getElementById('customer-status');
    const customerActionInput = document.getElementById('customer_action');

    const modal = document.getElementById('customer-modal');
    const modalName = document.getElementById('modal-customer-name');
    const btnSame = document.getElementById('btn-same-customer');
    const btnNew = document.getElementById('btn-new-customer');

    // Vehicle fields
    const vehicleYear = document.getElementById('vehicle_year');
    const vehicleMake = document.getElementById('vehicle_make');
    const vehicleModel = document.getElementById('vehicle_model');
    const vehicleColor = document.getElementById('vehicle_color');

    // Service fields
    const serviceTypeSelect = document.getElementById('catalog_item_id');
    const quotedPriceInput = document.getElementById('quoted_price');

    // Wizard buttons
    const btnNext1 = document.getElementById('btn-next-1');
    const btnNext2 = document.getElementById('btn-next-2');
    const btnBack2 = document.getElementById('btn-back-2');
    const btnBack3 = document.getElementById('btn-back-3');

    let tempCustomer = null;
    let tempVehicle = null;
    let currentStep = 1;
    let customerCheckResolved = false;

    // =========================================================================
    // Wizard Navigation
    // =========================================================================

    function showStep(step) {
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('hidden'));
        document.getElementById('wizard-step-' + step).classList.remove('hidden');
        currentStep = step;
        updateStepIndicator();
    }

    function updateStepIndicator() {
        for (let i = 1; i <= 3; i++) {
            const circle = document.getElementById('step-circle-' + i);
            const label = document.getElementById('step-label-' + i);

            if (i < currentStep) {
                // Completed step
                circle.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-green-500 text-white text-sm font-bold';
                circle.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
                label.className = 'ml-2 text-sm font-medium text-green-600';
            } else if (i === currentStep) {
                // Active step
                circle.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold';
                circle.textContent = i;
                label.className = 'ml-2 text-sm font-medium text-blue-600';
            } else {
                // Future step
                circle.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-gray-300 text-gray-600 text-sm font-bold';
                circle.textContent = i;
                label.className = 'ml-2 text-sm font-medium text-gray-500';
            }
        }

        // Step connector lines
        const line1 = document.getElementById('step-line-1');
        const line2 = document.getElementById('step-line-2');
        line1.className = 'flex-1 h-0.5 mx-4 ' + (currentStep > 1 ? 'bg-green-500' : 'bg-gray-300');
        line2.className = 'flex-1 h-0.5 mx-4 ' + (currentStep > 2 ? 'bg-green-500' : 'bg-gray-300');
    }

    // Step 1 → 2
    btnNext1.addEventListener('click', function () {
        if (!validateStep1()) return;
        showStep(2);
    });

    // Step 2 → 3
    btnNext2.addEventListener('click', function () {
        if (!validateStep2()) return;
        showStep(3);
    });

    // Step 2 ← 1
    btnBack2.addEventListener('click', function () {
        showStep(1);
    });

    // Step 3 ← 2
    btnBack3.addEventListener('click', function () {
        showStep(2);
    });

    // =========================================================================
    // Step Validation (client-side, before advancing)
    // =========================================================================

    function validateStep1() {
        if (!firstNameInput.value.trim() || !lastNameInput.value.trim() || phoneInput.value.length < 14) {
            highlightEmpty([firstNameInput, lastNameInput, phoneInput]);
            return false;
        }
        if (!customerCheckResolved) {
            statusDiv.innerHTML = '<span class="text-red-600 font-medium">Please wait for customer check to complete.</span>';
            return false;
        }
        return true;
    }

    function validateStep2() {
        const requiredFields = [vehicleYear, vehicleMake, vehicleModel, serviceTypeSelect, quotedPriceInput];
        if (!vehicleYear.value.trim() || !vehicleMake.value.trim() || !vehicleModel.value.trim() || !serviceTypeSelect.value || !quotedPriceInput.value) {
            highlightEmpty(requiredFields);
            return false;
        }
        if (!/^\d{4}$/.test(vehicleYear.value.trim())) {
            vehicleYear.classList.add('border-red-500');
            setTimeout(() => vehicleYear.classList.remove('border-red-500'), 2000);
            return false;
        }
        return true;
    }

    function highlightEmpty(fields) {
        fields.forEach(f => {
            if (!f.value || !f.value.trim()) {
                f.classList.add('border-red-500');
                setTimeout(() => f.classList.remove('border-red-500'), 2000);
            }
        });
    }

    // =========================================================================
    // "Next" Button Gating — Step 1
    // =========================================================================

    function updateNextButtonState() {
        const hasName = firstNameInput.value.trim() && lastNameInput.value.trim();
        const hasFullPhone = phoneInput.value.length === 14;
        btnNext1.disabled = !(hasName && hasFullPhone && customerCheckResolved);
    }

    firstNameInput.addEventListener('input', updateNextButtonState);
    lastNameInput.addEventListener('input', updateNextButtonState);

    // =========================================================================
    // Phone Formatting & Customer Lookup
    // =========================================================================

    phoneInput.addEventListener('input', function (e) {
        // Reset check state when phone changes
        customerCheckResolved = false;
        updateNextButtonState();

        // Strip to digits only
        let digits = e.target.value.replace(/\D/g, '').slice(0, 10);

        // Always format with (  ) and - present as user types
        let formatted = '';
        if (digits.length === 0) {
            formatted = '';
        } else if (digits.length <= 3) {
            formatted = '(' + digits;
        } else if (digits.length <= 6) {
            formatted = '(' + digits.slice(0, 3) + ') ' + digits.slice(3);
        } else {
            formatted = '(' + digits.slice(0, 3) + ') ' + digits.slice(3, 6) + '-' + digits.slice(6);
        }

        e.target.value = formatted;

        // Check if full phone number is entered
        if (e.target.value.length === 14) {
            statusDiv.innerHTML = '<span class="text-gray-500">Checking records...</span>';

            fetch(`/api/customers/search?phone=${encodeURIComponent(e.target.value)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.customer) {
                        tempCustomer = data.customer;
                        tempVehicle = data.vehicle || null;
                        modalName.innerText = tempCustomer.first_name + ' ' + tempCustomer.last_name;
                        modal.classList.remove('hidden');
                        statusDiv.innerHTML = '';
                        // Next stays disabled until modal is resolved
                    } else {
                        customerActionInput.value = 'create_new';
                        statusDiv.innerHTML = '<span class="text-blue-600 font-medium">New customer.</span>';
                        customerCheckResolved = true;
                        updateNextButtonState();
                    }
                })
                .catch(error => {
                    statusDiv.innerHTML = '<span class="text-red-600">No match found. Proceeding as new customer.</span>';
                    customerCheckResolved = true;
                    updateNextButtonState();
                    console.error('Error fetching customer:', error);
                });
        } else {
            statusDiv.innerHTML = '';
            customerActionInput.value = 'create_new';
        }
    });

    // =========================================================================
    // Customer Modal Handlers
    // =========================================================================

    // "Yes, Same Customer"
    btnSame.addEventListener('click', function () {
        customerActionInput.value = 'use_existing';
        firstNameInput.value = tempCustomer.first_name;
        lastNameInput.value = tempCustomer.last_name;

        modal.classList.add('hidden');
        statusDiv.innerHTML = '<span class="text-green-600 font-medium">✓ Using existing customer</span>';

        // Flash green on name fields
        flashGreen([firstNameInput, lastNameInput]);

        // Auto-fill vehicle if available
        if (tempVehicle) {
            vehicleYear.value = tempVehicle.year || '';
            vehicleMake.value = tempVehicle.make || '';
            vehicleModel.value = tempVehicle.model || '';
            vehicleColor.value = tempVehicle.color || '';
            flashGreen([vehicleYear, vehicleMake, vehicleModel, vehicleColor]);
        }

        customerCheckResolved = true;
        updateNextButtonState();
    });

    // "No, New Customer"
    btnNew.addEventListener('click', function () {
        customerActionInput.value = 'create_new';
        firstNameInput.value = '';
        lastNameInput.value = '';

        modal.classList.add('hidden');
        statusDiv.innerHTML = '<span class="text-blue-600 font-medium">Creating new customer record</span>';
        firstNameInput.focus();

        customerCheckResolved = true;
        updateNextButtonState();
    });

    function flashGreen(fields) {
        fields.forEach(f => {
            f.classList.add('bg-green-50');
            setTimeout(() => f.classList.remove('bg-green-50'), 1500);
        });
    }

    // =========================================================================
    // Service Type → Auto-fill Quoted Price
    // =========================================================================

    serviceTypeSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const price = selected.getAttribute('data-price');
        if (price) {
            quotedPriceInput.value = parseFloat(price).toFixed(2);
            flashGreen([quotedPriceInput]);
        } else {
            quotedPriceInput.value = '';
        }
    });

    // =========================================================================
    // Vehicle Year — digits only, max 4
    // =========================================================================

    vehicleYear.addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    });

    // =========================================================================
    // Init
    // =========================================================================

    updateNextButtonState();
});
