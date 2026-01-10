@csrf
<div class="w-full p-0 space-y-8">
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
    @php
        $rawSize = old('register_no', $part->register_no ?? '');
	        $thicknessValue = old('thickness');
	        $widthValue = old('width');
	        $lengthValue = old('length');
	        $sizeType = old('size_type');
	        $isCoil = old('is_coil');

	        if (!$thicknessValue && !$widthValue && !$sizeType && $rawSize) {
            $segments = preg_split('/[xX]/', $rawSize);
            $segments = array_map('trim', $segments);

            if (count($segments) >= 1) {
                $thicknessValue = $segments[0];
            }
            if (count($segments) >= 2) {
                $widthValue = $segments[1];
            }
            if (count($segments) >= 3) {
	                $lastSegment = strtoupper($segments[2]);
	                if ($lastSegment === 'C') {
	                    $sizeType = 'coil';
	                    $lengthValue = '';
	                } else {
	                    $sizeType = 'sheet';
	                    $lengthValue = $segments[2];
	                }
	            }
	        }

	        if (!$sizeType) {
	            $sizeType = 'sheet';
	        }

	        if ($isCoil === null) {
	            $isCoil = $sizeType === 'coil';
	        } else {
	            $isCoil = (bool) $isCoil;
	        }
	    @endphp

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
                <label class="text-sm font-medium text-gray-700">Size*</label>
	                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    <div>
                        <input
                            type="text"
                            id="size_thickness"
                            name="thickness"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Thickness"
                            value="{{ $thicknessValue }}"
                        >
                    </div>
                    <div>
                        <input
                            type="text"
                            id="size_width"
                            name="width"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Width"
                            value="{{ $widthValue }}"
                        >
                    </div>
                    <div>
                        <input
                            type="text"
                            id="size_length"
                            name="length"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Length"
                            value="{{ $lengthValue }}"
                        >
                    </div>
	                    <div>
	                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
	                            <input
	                                type="checkbox"
	                                id="size_is_coil"
	                                name="is_coil"
	                                value="1"
	                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
	                                @checked($isCoil)
	                            >
	                            <span>Coil (C)</span>
	                        </label>
	                    </div>
	                </div>
	                <p class="text-xs text-gray-500">
	                    Format otomatis: <span id="size-preview-form">{{ $rawSize ?: '-' }}</span>
	                    <br>Kalau dicentang, ukuran akan jadi <span class="font-semibold">C</span> di bagian panjang.
                        <br>Tersimpan ke field: <span class="font-semibold">register_no</span>.
	                </p>
                <input
                    type="hidden"
                    id="register_no"
                    name="register_no"
                    value="{{ $rawSize }}"
                    required
                >
                <x-input-error :messages="$errors->get('register_no')" class="mt-1" />
            </div>
        </div>
    </div>

    <!-- Section 3 — Naming Details -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 tracking-wide uppercase">Naming Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                @if(!($part->exists ?? false))
                    <label for="vendor_part_name_select" class="text-sm font-medium text-gray-700">Vendor Part (Existing)</label>
                    <select id="vendor_part_name_select" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" disabled>
                        <option value="">New vendor part...</option>
                    </select>
                    <p class="text-xs text-gray-500">Pilih vendor dulu untuk melihat daftar existing.</p>
                @endif
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
        <div class="space-y-2">
            <label for="quality_inspection" class="text-sm font-medium text-gray-700">Quality Inspection</label>
            @php $qi = old('quality_inspection', $part->quality_inspection ?? ''); @endphp
            <select id="quality_inspection" name="quality_inspection" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="" @selected($qi === '' || $qi === null)>-</option>
                <option value="YES" @selected(strtoupper((string) $qi) === 'YES')>YES</option>
            </select>
            <p class="text-xs text-gray-500">Isi YES jika part butuh QC inspection.</p>
            <x-input-error :messages="$errors->get('quality_inspection')" class="mt-1" />
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

	    <script>
	    (function () {
            const vendorsPartsBase = @json(url('/vendors'));
            const vendorSelect = document.getElementById('vendor_id');
            const vendorPartSelect = document.getElementById('vendor_part_name_select');
            const vendorPartInput = document.getElementById('part_name_vendor');
            const gciNameInput = document.getElementById('part_name_gci');

            async function loadVendorPartNames(vendorId) {
                if (!vendorPartSelect) return;
                vendorPartSelect.disabled = true;
                vendorPartSelect.innerHTML = '<option value=\"\">Loading...</option>';

                if (!vendorId) {
                    vendorPartSelect.innerHTML = '<option value=\"\">New vendor part...</option>';
                    vendorPartSelect.disabled = true;
                    return;
                }

                try {
                    const res = await fetch(`${vendorsPartsBase}/${vendorId}/parts`, { headers: { 'Accept': 'application/json' } });
                    const parts = await res.json();
                    const names = Array.from(new Set(
                        (Array.isArray(parts) ? parts : [])
                            .map(p => String(p.part_name_vendor || '').trim())
                            .filter(Boolean)
                    )).sort((a, b) => a.localeCompare(b));

                    const current = String(vendorPartInput?.value || '').trim().toUpperCase();
                    vendorPartSelect.innerHTML = '<option value=\"\">New vendor part...</option>' + names.map((n) => {
                        const up = n.toUpperCase();
                        const selected = current && up === current ? ' selected' : '';
                        return `<option value=\"${escapeHtml(up)}\"${selected}>${escapeHtml(up)}</option>`;
                    }).join('');
                    vendorPartSelect.disabled = false;
                } catch (e) {
                    vendorPartSelect.innerHTML = '<option value=\"\">New vendor part...</option>';
                    vendorPartSelect.disabled = false;
                }
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            if (vendorSelect && vendorPartSelect) {
                vendorSelect.addEventListener('change', () => loadVendorPartNames(vendorSelect.value));
                vendorPartSelect.addEventListener('change', () => {
                    const chosen = String(vendorPartSelect.value || '').trim();
                    if (!chosen) return;
                    if (vendorPartInput) {
                        vendorPartInput.value = chosen;
                        vendorPartInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (gciNameInput) gciNameInput.focus();
                });
                loadVendorPartNames(vendorSelect.value);
            }

	        const thicknessInput = document.getElementById('size_thickness');
	        const widthInput = document.getElementById('size_width');
	        const lengthInput = document.getElementById('size_length');
	        const coilCheckbox = document.getElementById('size_is_coil');
	        const previewEl = document.getElementById('size-preview-form');
	        const registerNoInput = document.getElementById('register_no');

	        if (!thicknessInput || !widthInput || !lengthInput || !coilCheckbox || !previewEl || !registerNoInput) {
	            return;
	        }

	        function updateSizeFields() {
	            const isCoil = coilCheckbox.checked;
	            const thickness = thicknessInput.value.trim();
	            const width = widthInput.value.trim();
	            let length = lengthInput.value.trim();

	            if (isCoil) {
	                length = 'C';
	                lengthInput.value = '';
	                lengthInput.disabled = true;
	                lengthInput.placeholder = 'C';
	            } else {
	                lengthInput.disabled = false;
	                if (lengthInput.placeholder === 'C') {
	                    lengthInput.placeholder = 'Length';
	                }
	            }

            const parts = [];
            if (thickness) parts.push(thickness);
            if (width) parts.push(width);
            if (length) parts.push(length);

            const sizeString = parts.join(' x ');
            previewEl.textContent = sizeString || '-';
            registerNoInput.value = sizeString;
        }

	        ['input', 'change'].forEach(eventName => {
	            thicknessInput.addEventListener(eventName, updateSizeFields);
	            widthInput.addEventListener(eventName, updateSizeFields);
	            lengthInput.addEventListener(eventName, updateSizeFields);
	            coilCheckbox.addEventListener(eventName, updateSizeFields);
	        });

        // Initialize state on load
        updateSizeFields();
    })();
</script>
