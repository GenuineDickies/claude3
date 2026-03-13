{{-- Shared vendor form fields (used by create & edit) --}}
<div class="surface-1 p-6">
    <h2 class="text-lg font-semibold text-gray-300 mb-4">Vendor Information</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Vendor Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $vendor?->name) }}"
                   placeholder="e.g. AutoZone, O'Reilly"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contact_name" class="block text-sm font-medium text-gray-300 mb-1">Contact Name</label>
            <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $vendor?->contact_name) }}"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            @error('contact_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $vendor?->phone) }}"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $vendor?->email) }}"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="account_number" class="block text-sm font-medium text-gray-300 mb-1">Account Number</label>
            <input type="text" name="account_number" id="account_number" value="{{ old('account_number', $vendor?->account_number) }}"
                   placeholder="Your account # with this vendor"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            @error('account_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="surface-1 p-6">
    <h2 class="text-lg font-semibold text-gray-300 mb-4">Address</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="address" class="block text-sm font-medium text-gray-300 mb-1">Street Address</label>
            <input type="text" name="address" id="address" value="{{ old('address', $vendor?->address) }}"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="city" class="block text-sm font-medium text-gray-300 mb-1">City</label>
            <input type="text" name="city" id="city" value="{{ old('city', $vendor?->city) }}"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="state" class="block text-sm font-medium text-gray-300 mb-1">State</label>
                <input type="text" name="state" id="state" value="{{ old('state', $vendor?->state) }}"
                       maxlength="2" placeholder="TX"
                       class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            </div>
            <div>
                <label for="zip" class="block text-sm font-medium text-gray-300 mb-1">ZIP</label>
                <input type="text" name="zip" id="zip" value="{{ old('zip', $vendor?->zip) }}"
                       class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
            </div>
        </div>
    </div>
</div>

<div class="surface-1 p-6">
    <h2 class="text-lg font-semibold text-gray-300 mb-4">Accounting</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="payment_terms" class="block text-sm font-medium text-gray-300 mb-1">Payment Terms</label>
            <input type="text" name="payment_terms" id="payment_terms" value="{{ old('payment_terms', $vendor?->payment_terms) }}"
                   placeholder="e.g. Net 30, Due on Receipt"
                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
        </div>
        <div>
            <label for="default_expense_account_id" class="block text-sm font-medium text-gray-300 mb-1">Default Expense Account</label>
            <select name="default_expense_account_id" id="default_expense_account_id"
                    class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                <option value="">— None —</option>
                @foreach ($expenseAccounts as $acct)
                    <option value="{{ $acct->id }}" {{ old('default_expense_account_id', $vendor?->default_expense_account_id) == $acct->id ? 'selected' : '' }}>
                        {{ $acct->code }} – {{ $acct->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="surface-1 p-6">
    <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
    <textarea name="notes" id="notes" rows="3"
              class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">{{ old('notes', $vendor?->notes) }}</textarea>
    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
