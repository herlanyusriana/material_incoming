@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="register_no" value="Register No" />
        <x-text-input id="register_no" name="register_no" type="text" class="mt-1 block w-full" required value="{{ old('register_no', $part->register_no ?? '') }}" />
        <x-input-error :messages="$errors->get('register_no')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="part_no" value="Part No" />
        <x-text-input id="part_no" name="part_no" type="text" class="mt-1 block w-full" required value="{{ old('part_no', $part->part_no ?? '') }}" />
        <x-input-error :messages="$errors->get('part_no')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="part_name_vendor" value="Part Name (Vendor)" />
        <x-text-input id="part_name_vendor" name="part_name_vendor" type="text" class="mt-1 block w-full" required value="{{ old('part_name_vendor', $part->part_name_vendor ?? '') }}" />
        <x-input-error :messages="$errors->get('part_name_vendor')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="part_name_gci" value="Part Name (GCI)" />
        <x-text-input id="part_name_gci" name="part_name_gci" type="text" class="mt-1 block w-full" required value="{{ old('part_name_gci', $part->part_name_gci ?? '') }}" />
        <x-input-error :messages="$errors->get('part_name_gci')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="vendor_id" value="Vendor" />
        <select name="vendor_id" id="vendor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="">Select vendor</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $part->vendor_id ?? '') == $vendor->id)>{{ $vendor->vendor_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="trucking_company" value="Trucking Company" />
        <x-text-input id="trucking_company" name="trucking_company" type="text" class="mt-1 block w-full" value="{{ old('trucking_company', $part->trucking_company ?? '') }}" />
        <x-input-error :messages="$errors->get('trucking_company')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="storage_reg" value="Storage Reg" />
        <x-text-input id="storage_reg" name="storage_reg" type="text" class="mt-1 block w-full" value="{{ old('storage_reg', $part->storage_reg ?? '') }}" />
        <x-input-error :messages="$errors->get('storage_reg')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status" />
        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="active" @selected(old('status', $part->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $part->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('parts.index') }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
    <x-primary-button>Save</x-primary-button>
</div>
