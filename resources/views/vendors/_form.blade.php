@csrf
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <x-input-label for="vendor_name" value="Vendor Name" />
        <x-text-input id="vendor_name" name="vendor_name" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" required value="{{ old('vendor_name', $vendor->vendor_name ?? '') }}" />
        <x-input-error :messages="$errors->get('vendor_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="vendor_type" value="Vendor Type" />
        <select id="vendor_type" name="vendor_type" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" required>
            @php $vt = old('vendor_type', $vendor->vendor_type ?? 'import'); @endphp
            <option value="import" @selected($vt === 'import')>Import</option>
            <option value="local" @selected($vt === 'local')>Local</option>
            <option value="tolling" @selected($vt === 'tolling')>Tolling</option>
        </select>
        <x-input-error :messages="$errors->get('vendor_type')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="country_code" value="Country Code (ISO-2)" />
        <x-text-input id="country_code" name="country_code" type="text" class="mt-1 w-full uppercase rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" required maxlength="2" placeholder="ID" pattern="[A-Za-z]{2}" title="Country code harus 2 huruf (contoh: ID)" value="{{ old('country_code', $vendor->country_code ?? '') }}" />
        <x-input-error :messages="$errors->get('country_code')" class="mt-2" />
    </div>
    <div class="md:col-span-3">
        <x-input-label for="bank_account" value="Bank Account" />
        <textarea id="bank_account" name="bank_account" rows="4" maxlength="255" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm">{{ old('bank_account', $vendor->bank_account ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('bank_account')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="contact_person" value="Contact Person" />
        <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" value="{{ old('contact_person', $vendor->contact_person ?? '') }}" />
        <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" value="{{ old('email', $vendor->email ?? '') }}" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="phone" value="Phone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" value="{{ old('phone', $vendor->phone ?? '') }}" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm">
            <option value="active" {{ old('status', $vendor->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $vendor->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
    <div class="md:col-span-3">
        <x-input-label for="address" value="Address" />
        <textarea id="address" name="address" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm">{{ old('address', $vendor->address ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
    <div class="md:col-span-3">
        <x-input-label for="signature" value="Signature" />
        <input type="file" id="signature" name="signature" accept="image/*" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" />
        <p class="mt-1 text-xs text-gray-500">Upload signature image for invoices (JPG, PNG, max 2MB)</p>
        @if(isset($vendor) && $vendor->signature_path)
            <div class="mt-2">
                <p class="text-xs text-gray-600 mb-1">Current signature:</p>
                <img src="{{ Storage::url($vendor->signature_path) }}" alt="Signature" class="h-16 border rounded">
            </div>
        @endif
        <x-input-error :messages="$errors->get('signature')" class="mt-2" />
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ route('vendors.index') }}" class="border border-gray-300 text-gray-700 rounded-xl px-4 py-2 hover:bg-gray-50">Cancel</a>
    <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 rounded-xl px-6 py-3 text-white">Save</x-primary-button>
</div>
