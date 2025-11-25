@csrf
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <x-input-label for="vendor_name" value="Vendor Name" />
        <x-text-input id="vendor_name" name="vendor_name" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" required value="{{ old('vendor_name', $vendor->vendor_name ?? '') }}" />
        <x-input-error :messages="$errors->get('vendor_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="bank_account" value="Bank Account" />
        <x-text-input id="bank_account" name="bank_account" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" value="{{ old('bank_account', $vendor->bank_account ?? '') }}" />
        <x-input-error :messages="$errors->get('bank_account')" class="mt-2" />
    </div>
    <div class="md:col-span-3">
        <x-input-label for="address" value="Address" />
        <textarea id="address" name="address" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm">{{ old('address', $vendor->address ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ route('vendors.index') }}" class="border border-gray-300 text-gray-700 rounded-xl px-4 py-2 hover:bg-gray-50">Cancel</a>
    <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 rounded-xl px-6 py-3 text-white">Save</x-primary-button>
</div>
