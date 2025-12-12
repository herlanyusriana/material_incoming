@csrf
<div class="max-w-6xl mx-auto p-0 space-y-8">
    <!-- Section 1 — Vendor Information -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 tracking-wide uppercase">Vendor Information</h2>
        <div class="space-y-2">
            <label for="vendor_id" class="text-sm font-medium text-gray-700">Vendor</label>
            <select id="vendor_id" name="vendor_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="">Select vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(old('vendor_id', $part->vendor_id ?? '') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500">Ketik satu kata, daftar vendor akan muncul.</p>
            <x-input-error :messages="$errors->get('vendor_id')" class="mt-1" />
        </div>
    </div>

    <!-- Section 2 — Part Identification -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 tracking-wide uppercase">Part Identification</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label for="part_no" class="text-sm font-medium text-gray-700">Part Number*</label>
                <input type="text" id="part_no" name="part_no" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" value="{{ old('part_no', $part->part_no ?? '') }}" required>
                <p class="text-xs text-gray-500">Gunakan kode internal singkat.</p>
                <x-input-error :messages="$errors->get('part_no')" class="mt-1" />
            </div>
            <div class="space-y-2">
                <label for="register_no" class="text-sm font-medium text-gray-700">Size*</label>
                <input type="text" id="register_no" name="register_no" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" value="{{ old('register_no', $part->register_no ?? '') }}" placeholder="1.00 x 200.0 x C" required>
                <x-input-error :messages="$errors->get('register_no')" class="mt-1" />
            </div>
        </div>
    </div>

    <!-- Section 3 — Naming Details -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 tracking-wide uppercase">Naming Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label for="part_name_vendor" class="text-sm font-medium text-gray-700">Vendor Part Name*</label>
                <input type="text" id="part_name_vendor" name="part_name_vendor" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" value="{{ old('part_name_vendor', $part->part_name_vendor ?? '') }}" required>
                <x-input-error :messages="$errors->get('part_name_vendor')" class="mt-1" />
            </div>
            <div class="space-y-2">
                <label for="part_name_gci" class="text-sm font-medium text-gray-700">GCI Part Name*</label>
                <input type="text" id="part_name_gci" name="part_name_gci" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" value="{{ old('part_name_gci', $part->part_name_gci ?? '') }}" required>
                <x-input-error :messages="$errors->get('part_name_gci')" class="mt-1" />
            </div>
        </div>
        <div class="space-y-2">
            <label for="hs_code" class="text-sm font-medium text-gray-700">HS Code</label>
            <input type="text" id="hs_code" name="hs_code" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="e.g., 7225.99.10" value="{{ old('hs_code', $part->hs_code ?? '') }}">
            <p class="text-xs text-gray-500">Harmonized System code for customs.</p>
            <x-input-error :messages="$errors->get('hs_code')" class="mt-1" />
        </div>
    </div>

    <!-- Section 4 — Operational Status -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 tracking-wide uppercase">Operational Status</h2>
        <div class="space-y-2">
            <label for="status" class="text-sm font-medium text-gray-700">Status</label>
            <select name="status" id="status" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="active" @selected(old('status', $part->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $part->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-1" />
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">Save</button>
    </div>
</div>
