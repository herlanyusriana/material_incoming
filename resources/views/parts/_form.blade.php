@csrf
<div class="w-full p-0 space-y-8">
    <!-- Section 1 — Vendor Information -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-slate-500 tracking-wide uppercase">{{ __('master.parts.form.section_vendor') }}</h2>
        <div class="space-y-2">
            <label for="vendor_id" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.vendor_label') }}</label>
            <select id="vendor_id" name="vendor_id" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">{{ __('master.parts.form.vendor_select') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" data-type="{{ strtolower($vendor->vendor_type) }}" @selected(old('vendor_id', $part->vendor_id ?? '') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500">{{ __('master.parts.form.vendor_hint') }}</p>
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

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-slate-500 tracking-wide uppercase">{{ __('master.parts.form.section_ident') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label for="part_no" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.part_no') }}</label>
                <input type="text" id="part_no" name="part_no" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" value="{{ old('part_no', $part->part_no ?? '') }}" required>
                <p class="text-xs text-slate-500">{{ __('master.parts.form.part_no_hint') }}</p>
                <x-input-error :messages="$errors->get('part_no')" class="mt-1" />
            </div>
            <div class="space-y-1">
                <label class="text-xs font-medium text-slate-700">{{ __('master.parts.form.size') }}</label>
                <div class="flex flex-wrap items-center gap-1.5">
                    <div class="flex-1 min-w-[60px]">
                        <input type="text" id="size_thickness" name="thickness" class="w-full rounded border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-xs px-2 py-1" placeholder="{{ __('master.parts.form.size_thick') }}" value="{{ $thicknessValue }}">
                    </div>
                    <span class="text-slate-400 text-xs font-bold">×</span>
                    <div class="flex-1 min-w-[60px]">
                        <input type="text" id="size_width" name="width" class="w-full rounded border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-xs px-2 py-1" placeholder="{{ __('master.parts.form.size_width') }}" value="{{ $widthValue }}">
                    </div>
                    <span class="text-slate-400 text-xs font-bold">×</span>
                    <div class="flex-1 min-w-[60px]">
                        <input type="text" id="size_length" name="length" class="w-full rounded border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-xs px-2 py-1" placeholder="{{ __('master.parts.form.size_length') }}" value="{{ $lengthValue }}">
                    </div>
                    <label class="flex items-center gap-1 rounded border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700 whitespace-nowrap">
                        <input type="checkbox" id="size_is_coil" name="is_coil" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-3.5 h-3.5" @checked($isCoil)>
                        <span>{{ __('master.parts.form.coil') }}</span>
                    </label>
                </div>
                <p class="text-[10px] text-slate-500">
                    {{ __('master.parts.form.size_hint_format') }} <span id="size-preview-form" class="font-mono">{{ $rawSize ?: '-' }}</span>
                    &nbsp;·&nbsp; {{ __('master.parts.form.size_hint_coil') }}
                    &nbsp;·&nbsp; {{ __('master.parts.form.size_hint_stored') }} <span class="font-semibold">register_no</span>
                </p>
                <input type="hidden" id="register_no" name="register_no" value="{{ $rawSize }}" required>
                <x-input-error :messages="$errors->get('register_no')" class="mt-0.5" />
            </div>
        </div>
    </div>

    <!-- Section 3 — Naming Details -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-slate-500 tracking-wide uppercase">{{ __('master.parts.form.section_naming') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                @if(!($part->exists ?? false))
                    <label for="vendor_part_name_select" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.vendor_part_existing') }}</label>
                    <select id="vendor_part_name_select" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" disabled>
                        <option value="">{{ __('master.parts.form.vendor_part_available') }}</option>
                        <option value="__other__">{{ __('master.parts.form.vendor_part_other') }}</option>
                    </select>
                    <p class="text-xs text-slate-500">{{ __('master.parts.form.vendor_part_hint') }}</p>
                @endif
                <label for="part_name_vendor" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.vendor_part_name') }}</label>
                <input type="text" id="part_name_vendor" name="part_name_vendor" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" value="{{ old('part_name_vendor', $part->part_name_vendor ?? '') }}" required>
                <x-input-error :messages="$errors->get('part_name_vendor')" class="mt-1" />
            </div>
            <div class="space-y-2">
                <label for="part_name_gci" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.gci_part_name') }}</label>
                <input type="text" id="part_name_gci" name="part_name_gci" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" value="{{ old('part_name_gci', $part->part_name_gci ?? '') }}" required>
                <x-input-error :messages="$errors->get('part_name_gci')" class="mt-1" />
            </div>
        </div>
        <div class="space-y-2">
            <label for="hs_code" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.hs_code') }}</label>
            <input type="text" id="hs_code" name="hs_code" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="e.g., 7225.99.10" value="{{ old('hs_code', $part->hs_code ?? '') }}">
            <p class="text-xs text-slate-500">{{ __('master.parts.form.hs_hint') }}</p>
            <x-input-error :messages="$errors->get('hs_code')" class="mt-1" />
        </div>
        <div class="space-y-2">
            <label for="quality_inspection" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.qi_label') }}</label>
            @php $qi = old('quality_inspection', $part->quality_inspection ?? ''); @endphp
            <select id="quality_inspection" name="quality_inspection" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="" @selected($qi === '' || $qi === null)>-</option>
                <option value="YES" @selected(strtoupper((string) $qi) === 'YES')>YES</option>
            </select>
            <p class="text-xs text-slate-500">{{ __('master.parts.form.qi_hint') }}</p>
            <x-input-error :messages="$errors->get('quality_inspection')" class="mt-1" />
        </div>

        <!-- Local Vendor Specifics -->
        <div id="local-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100 hidden">
            <div class="col-span-full">
                <h3 class="text-xs font-semibold text-indigo-600 tracking-wide uppercase">{{ __('master.parts.form.local_title') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('master.parts.form.local_hint') }}</p>
            </div>
            <div class="space-y-2">
                <label for="uom" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.uom') }}</label>
                <select id="uom" name="uom" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">{{ __('master.parts.form.uom_select') }}</option>
                    @foreach(['PCS', 'KG', 'SET', 'EA', 'SHEET', 'COIL', 'LITER', 'METER', 'ROLL'] as $pkg)
                        <option value="{{ $pkg }}" @selected(old('uom', $part->uom ?? '') === $pkg)>{{ $pkg }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('uom')" class="mt-1" />
            </div>
        </div>
    </div>

    <!-- Section 4 — Operational Status -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-semibold text-slate-500 tracking-wide uppercase">{{ __('master.parts.form.section_status') }}</h2>
        <div class="space-y-2">
            <label for="status" class="text-sm font-medium text-slate-700">{{ __('master.parts.form.status_label') }}</label>
            <select name="status" id="status" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="active" @selected(old('status', $part->status ?? 'active') === 'active')>{{ __('master.parts.form.status_active') }}</option>
                <option value="inactive" @selected(old('status', $part->status ?? 'active') === 'inactive')>{{ __('master.parts.form.status_inactive') }}</option>
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-1" />
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700">{{ __('master.parts.form.save') }}</button>
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
		        vendorPartSelect.innerHTML = '<option value="">' + @js(__('master.parts.form.js_loading')) + '</option>';

                if (!vendorId) {
                    vendorPartSelect.innerHTML = '<option value="">' + @js(__('master.parts.form.vendor_part_available')) + '</option><option value="__other__">' + @js(__('master.parts.form.vendor_part_other')) + '</option>';
                    vendorPartSelect.disabled = true;
                    return;
                }

		        try {
		            const res = await fetch(`${vendorsPartsBase}/${vendorId}/parts?mode=names&limit=500`, { headers: { 'Accept': 'application/json' } });
		            const payload = await res.json();
		            const list = Array.isArray(payload) ? payload : (payload?.names || []);
		            const names = Array.from(new Set(
		                (Array.isArray(list) ? list : [])
		                    .map((n) => String(n || '').trim())
		                    .filter(Boolean)
		            )).sort((a, b) => a.localeCompare(b));

                    const current = String(vendorPartInput?.value || '').trim().toUpperCase();
                    vendorPartSelect.innerHTML = '<option value="">' + @js(__('master.parts.form.vendor_part_available')) + '</option><option value="__other__">' + @js(__('master.parts.form.vendor_part_other')) + '</option>' + names.map((n) => {
                        const up = n.toUpperCase();
                        const selected = current && up === current ? ' selected' : '';
                        return `<option value="${escapeHtml(up)}"${selected}>${escapeHtml(up)}</option>`;
                    }).join('');
                    vendorPartSelect.disabled = false;
                } catch (e) {
                    vendorPartSelect.innerHTML = '<option value="">' + @js(__('master.parts.form.vendor_part_available')) + '</option><option value="__other__">' + @js(__('master.parts.form.vendor_part_other')) + '</option>';
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
                vendorSelect.addEventListener('change', () => {
                     loadVendorPartNames(vendorSelect.value);
                     toggleLocalFields();
                });
                vendorPartSelect.addEventListener('change', () => {
                    const chosen = String(vendorPartSelect.value || '').trim();
                    if (!chosen || chosen === '__other__') {
                        if (chosen === '__other__' && vendorPartInput) {
                            vendorPartInput.focus();
                        }
                        return;
                    }
                    if (vendorPartInput) {
                        vendorPartInput.value = chosen;
                        vendorPartInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (gciNameInput) gciNameInput.focus();
                });
                loadVendorPartNames(vendorSelect.value);
                toggleLocalFields();
            }

            function toggleLocalFields() {
                const selectedOption = vendorSelect.options[vendorSelect.selectedIndex];
                const type = selectedOption ? selectedOption.dataset.type : '';
                const localFields = document.getElementById('local-fields');

                if (localFields) {
                    if (type === 'local') {
                        localFields.classList.remove('hidden');
                    } else {
                        localFields.classList.add('hidden');
                    }
                }
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
		                lengthInput.placeholder = @js(__('master.parts.form.size_length'));
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