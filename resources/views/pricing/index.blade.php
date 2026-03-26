@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ openEditId: null }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Pricing Master</h1>
            <p class="mt-1 text-sm text-slate-500">Pusat pengelolaan harga untuk vendor, material cost, processing cost, selling price, dan harga khusus per effective date.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Records</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ number_format($prices->total()) }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-widest text-emerald-600">Active</div>
                <div class="mt-1 text-2xl font-black text-emerald-700">{{ number_format($prices->getCollection()->where('status', 'active')->count()) }}</div>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-widest text-indigo-600">Visible Types</div>
                <div class="mt-1 text-2xl font-black text-indigo-700">{{ number_format($prices->getCollection()->pluck('price_type')->unique()->count()) }}</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Pricing Master List</h2>
            </div>
            <a href="{{ route('pricing.create') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Add New Pricing</a>
        </div>
        <div class="rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <form method="GET" class="grid gap-3 lg:grid-cols-5">
                    <input name="search" value="{{ $filters['search'] }}" class="rounded-xl border-slate-200 text-sm lg:col-span-2" placeholder="Search part, vendor, customer">
                    <select name="classification" class="rounded-xl border-slate-200 text-sm">
                        <option value="">All Class</option>
                        <option value="RM" @selected($filters['classification'] === 'RM')>RM</option>
                        <option value="WIP" @selected($filters['classification'] === 'WIP')>WIP</option>
                        <option value="FG" @selected($filters['classification'] === 'FG')>FG</option>
                    </select>
                    <select name="price_type" class="rounded-xl border-slate-200 text-sm">
                        <option value="">All Types</option>
                        @foreach($priceTypes as $value => $label)
                            <option value="{{ $value }}" @selected($filters['priceType'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-xl border-slate-200 text-sm">
                        <option value="">All Status</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                    </select>
                </form>
                <div class="mt-3 text-xs text-slate-500">
                    Harga yang tampil adalah master pricing. Dokumen transaksi tetap menyimpan snapshot harga masing-masing.
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Part</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Counterparty</th>
                            <th class="px-4 py-3 font-semibold">Price</th>
                            <th class="px-4 py-3 font-semibold">Effective</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($prices as $price)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $price->gciPart?->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $price->gciPart?->part_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $priceTypes[$price->price_type] ?? $price->price_type }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-slate-700">{{ $price->vendor?->vendor_name ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $price->customer?->name ?: 'General' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $price->currency }} {{ number_format((float) $price->price, 3) }}</div>
                                    <div class="text-xs text-slate-500">{{ $price->uom ?: '-' }} @if($price->min_qty) | min {{ number_format((float) $price->min_qty, 3) }} @endif</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div>{{ $price->effective_from?->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">to {{ $price->effective_to?->format('d M Y') ?: 'open' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $price->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst($price->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            @click="openEditId = openEditId === {{ $price->id }} ? null : {{ $price->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('pricing.destroy', $price) }}" method="POST" onsubmit="return confirm('Delete pricing master ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="openEditId === {{ $price->id }}" x-cloak>
                                <td colspan="7" class="bg-slate-50 px-4 py-4">
                                    <form action="{{ route('pricing.update', $price) }}" method="POST" class="grid gap-3 lg:grid-cols-6">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="gci_part_id" value="{{ $price->gci_part_id }}">
                                        <div class="lg:col-span-2">
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Price Type</label>
                                            <select name="price_type" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                @foreach($priceTypes as $value => $label)
                                                    <option value="{{ $value }}" @selected($price->price_type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Currency</label>
                                            <input type="text" name="currency" value="{{ $price->currency }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Price</label>
                                            <input type="number" name="price" step="0.001" min="0" value="{{ $price->price }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">UOM</label>
                                            <input type="text" name="uom" value="{{ $price->uom }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Min Qty</label>
                                            <input type="number" name="min_qty" step="0.001" min="0" value="{{ $price->min_qty }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</label>
                                            <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                <option value="">General / no vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" @selected($price->vendor_id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer</label>
                                            <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                <option value="">General / no customer</option>
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}" @selected($price->customer_id === $customer->id)>{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From</label>
                                            <input type="date" name="effective_from" value="{{ $price->effective_from?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective To</label>
                                            <input type="date" name="effective_to" value="{{ $price->effective_to?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                <option value="active" @selected($price->status === 'active')>Active</option>
                                                <option value="inactive" @selected($price->status === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="lg:col-span-6">
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                                            <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ $price->notes }}</textarea>
                                        </div>
                                        <div class="lg:col-span-6 flex items-center justify-end gap-2">
                                            <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-white"
                                                @click="openEditId = null">
                                                Cancel
                                            </button>
                                            <button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Update Pricing
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada pricing master.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4">{{ $prices->links() }}</div>
        </div>
    </div>
</div>
@endsection
