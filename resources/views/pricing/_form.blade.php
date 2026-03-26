<form action="{{ route('pricing.store') }}" method="POST" class="space-y-4">
    @csrf
    <div>
        <label class="text-sm font-semibold text-slate-700">Part</label>
        <select name="gci_part_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
            <option value="">Select part...</option>
            @foreach($parts as $part)
                <option value="{{ $part->id }}" @selected(old('gci_part_id') == $part->id)>{{ $part->classification }} - {{ $part->part_no }} - {{ $part->part_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="text-sm font-semibold text-slate-700">Price Type</label>
            <select name="price_type" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                @foreach($priceTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('price_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">Currency</label>
            <input type="text" name="currency" value="{{ old('currency', 'IDR') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="text-sm font-semibold text-slate-700">Vendor</label>
            <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                <option value="">General / no vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">Customer</label>
            <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                <option value="">General / no customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="text-sm font-semibold text-slate-700">Price</label>
            <input type="number" name="price" step="0.001" min="0" value="{{ old('price') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">UOM</label>
            <input type="text" name="uom" value="{{ old('uom') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="PCS / KG">
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">Min Qty</label>
            <input type="number" name="min_qty" step="0.001" min="0" value="{{ old('min_qty') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="text-sm font-semibold text-slate-700">Effective From</label>
            <input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">Effective To</label>
            <input type="date" name="effective_to" value="{{ old('effective_to') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">Status</label>
            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
            </select>
        </div>
    </div>
    <div>
        <label class="text-sm font-semibold text-slate-700">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Optional notes">{{ old('notes') }}</textarea>
    </div>
    <button class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Pricing</button>
</form>
