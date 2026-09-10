<form action="{{ route('pricing.store') }}" method="POST" class="space-y-4">
    @csrf
    <div>
        <label for="gci_part_id" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.part') }}</label>
        <select id="gci_part_id" name="gci_part_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
            <option value="">{{ __('master.pricing.form.part_select') }}</option>
            @foreach($parts as $part)
                <option value="{{ $part->id }}" @selected(old('gci_part_id') == $part->id)>{{ $part->classification }} - {{ $part->part_no }} - {{ $part->part_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="price_type" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.price_type') }}</label>
            <select id="price_type" name="price_type" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                @foreach($priceTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('price_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="currency" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.currency') }}</label>
            <input id="currency" type="text" name="currency" value="{{ old('currency', 'IDR') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="vendor_id" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.vendor') }}</label>
            <select id="vendor_id" name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                <option value="">{{ __('master.pricing.form.vendor_general') }}</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="customer_id" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.customer') }}</label>
            <select id="customer_id" name="customer_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                <option value="">{{ __('master.pricing.form.customer_general') }}</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label for="price" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.price') }}</label>
            <input id="price" type="number" name="price" step="0.001" min="0" value="{{ old('price') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm tabular-nums" required>
        </div>
        <div>
            <label for="uom" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.uom') }}</label>
            <input id="uom" type="text" name="uom" value="{{ old('uom') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('master.pricing.form.uom_placeholder') }}">
        </div>
        <div>
            <label for="min_qty" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.min_qty') }}</label>
            <input id="min_qty" type="number" name="min_qty" step="0.001" min="0" value="{{ old('min_qty') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm tabular-nums">
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label for="effective_from" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.effective_from') }}</label>
            <input id="effective_from" type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
        </div>
        <div>
            <label for="effective_to" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.effective_to') }}</label>
            <input id="effective_to" type="date" name="effective_to" value="{{ old('effective_to') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label for="status" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.status') }}</label>
            <select id="status" name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                <option value="active" @selected(old('status', 'active') === 'active')>{{ __('master.pricing.form.status_active') }}</option>
                <option value="inactive" @selected(old('status') === 'inactive')>{{ __('master.pricing.form.status_inactive') }}</option>
            </select>
        </div>
    </div>
    <div>
        <label for="notes" class="text-sm font-semibold text-slate-700">{{ __('master.pricing.form.notes') }}</label>
        <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('master.pricing.form.notes_placeholder') }}">{{ old('notes') }}</textarea>
    </div>
    <button class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('master.pricing.form.save') }}</button>
</form>
