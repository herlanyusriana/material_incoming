<x-app-layout>
    <x-slot name="header">
        Detail Contract Number
    </x-slot>

    <div class="space-y-6" x-data="contractDetail()">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <a href="{{ route('contract-numbers.index') }}" class="text-xs font-bold uppercase tracking-widest text-slate-300 hover:text-white">
                            Back to Contract Numbers
                        </a>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <h1 class="text-3xl font-black">{{ $contract->contract_no }}</h1>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $contract->status === 'active' ? 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/40' : 'bg-slate-400/20 text-slate-200 ring-1 ring-slate-300/40' }}">
                                {{ strtoupper($contract->status) }}
                            </span>
                        </div>
                        <p class="mt-2 max-w-3xl text-sm text-slate-300">{{ $contract->description ?: 'Tidak ada deskripsi kontrak.' }}</p>
                    </div>
                    <form action="{{ route('contract-numbers.destroy', $contract) }}" method="POST" onsubmit="return confirm('Hapus nomor kontrak beserta itemnya secara permanen?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-xl border border-rose-300/40 bg-rose-500/10 px-4 py-2 text-sm font-bold text-rose-100 hover:bg-rose-500/20">
                            Delete Contract
                        </button>
                    </form>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Vendor</div>
                        <div class="mt-1 truncate text-sm font-black">{{ $contract->vendor?->vendor_name ?? '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Effective</div>
                        <div class="mt-1 text-sm font-black">{{ $contract->effective_from?->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Expired</div>
                        <div class="mt-1 text-sm font-black">{{ $contract->effective_to?->format('d M Y') ?: 'OPEN' }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Items</div>
                        <div class="mt-1 font-mono text-lg font-black">{{ number_format($contract->items->count()) }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Updated By</div>
                        <div class="mt-1 truncate text-sm font-black">{{ $contract->updater?->name ?? $contract->creator?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-0 xl:grid-cols-[1fr_420px]">
                <div class="border-b border-slate-200 p-6 xl:border-b-0 xl:border-r">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Detail Item Kontrak</h2>
                            <p class="text-sm text-slate-500">Monitoring target, pemakaian, sisa kontrak, dan alarm per part.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">WIP Part</th>
                                    <th class="px-4 py-3">RM Part</th>
                                    <th class="px-4 py-3">Process</th>
                                    <th class="px-4 py-3 text-right">Target</th>
                                    <th class="px-4 py-3 text-right">Sent</th>
                                    <th class="px-4 py-3 text-right">Remain</th>
                                    <th class="px-4 py-3 text-right">Alarm</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($contract->items as $item)
                                    @php
                                        $remaining = (float) $item->remaining_qty;
                                        $alarm = $item->warning_limit_qty !== null ? (float) $item->warning_limit_qty : null;
                                        $isAlarm = $alarm !== null && $remaining <= $alarm;
                                    @endphp
                                    <tr class="{{ $isAlarm ? 'bg-amber-50/70' : '' }}">
                                        <td class="px-4 py-3">
                                            <div class="font-mono font-black text-slate-900">{{ $item->gciPart?->part_no ?? '-' }}</div>
                                            <div class="max-w-[220px] truncate text-xs text-slate-500">{{ $item->gciPart?->part_name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-mono font-black text-indigo-700">{{ $item->rmPart?->part_no ?? '-' }}</div>
                                            <div class="max-w-[220px] truncate text-xs text-slate-500">{{ $item->rmPart?->part_name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-600">{{ $item->process_type ?: '-' }}</td>
                                        <td class="px-4 py-3 text-right font-mono font-black text-slate-900">{{ number_format((float) $item->target_qty, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-blue-700">{{ number_format((float) $item->sent_qty, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono font-black {{ $isAlarm ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($remaining, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs font-bold text-slate-500">{{ $alarm !== null ? number_format($alarm, 2) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">Belum ada item pada kontrak ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-slate-50/70 p-6">
                    <h2 class="text-lg font-black text-slate-900">Edit Kontrak</h2>
                    <p class="mb-5 text-sm text-slate-500">Perubahan header dan item kontrak dilakukan dari halaman detail ini.</p>

                    <form action="{{ route('contract-numbers.update', $contract) }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</label>
                            <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected($contract->vendor_id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak</label>
                            <input type="text" name="contract_no" value="{{ old('contract_no', $contract->contract_no) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi</label>
                            <input type="text" name="description" value="{{ old('description', $contract->description) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From</label>
                                <input type="date" name="effective_from" value="{{ old('effective_from', $contract->effective_from?->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Expired</label>
                                <input type="date" name="effective_to" value="{{ old('effective_to', $contract->effective_to?->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                <option value="active" @selected($contract->status === 'active')>Active</option>
                                <option value="inactive" @selected($contract->status === 'inactive')>Inactive</option>
                            </select>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black text-slate-900">Edit Items</h3>
                                    <p class="text-xs text-slate-500">Target dan alarm per WIP/RM.</p>
                                </div>
                                <button type="button" @click="addRow()" class="rounded-lg bg-indigo-100 px-3 py-1.5 text-xs font-black text-indigo-700 hover:bg-indigo-200">
                                    + Part
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(row, index) in rows" :key="row.id">
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase text-slate-500">Part Mapping</label>
                                            <select class="mt-1 w-full rounded-lg border-slate-300 text-xs" x-model="row.selected_part_key" @change="onPartChange(index)" required>
                                                <option value="">Pilih WIP - RM...</option>
                                                <template x-for="opt in subconPartsOptions" :key="opt.key">
                                                    <option :value="opt.key" x-text="`${opt.part_no} (${opt.process_name}) => ${opt.rm_part_no}`"></option>
                                                </template>
                                            </select>
                                            <input type="hidden" :name="`items[${index}][gci_part_id]`" x-model="row.gci_part_id">
                                            <input type="hidden" :name="`items[${index}][rm_gci_part_id]`" x-model="row.rm_gci_part_id">
                                            <input type="hidden" :name="`items[${index}][process_type]`" x-model="row.process_type">
                                            <input type="hidden" :name="`items[${index}][bom_item_id]`" x-model="row.bom_item_id">
                                        </div>
                                        <div class="mt-3 grid grid-cols-[1fr_1fr_auto] gap-2">
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase text-slate-500">Target</label>
                                                <input type="number" step="0.0001" min="0" :name="`items[${index}][target_qty]`" x-model="row.target_qty" class="mt-1 w-full rounded-lg border-slate-300 text-sm font-bold" required>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase text-slate-500">Alarm</label>
                                                <input type="number" step="0.0001" min="0" :name="`items[${index}][warning_limit_qty]`" x-model="row.warning_limit_qty" class="mt-1 w-full rounded-lg border-slate-300 text-sm font-bold">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" @click="rows.splice(index, 1)" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-50">
                                                    X
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                            <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('notes', $contract->notes) }}</textarea>
                        </div>

                        <button class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700">
                            Update Contract
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contractDetail', () => ({
                subconPartsOptions: @json($subconPartsJson ?? []),
                rows: @json($editItemsJson ?? []),
                addRow() {
                    this.rows.push({
                        id: Date.now() + Math.random(),
                        selected_part_key: '',
                        gci_part_id: '',
                        rm_gci_part_id: '',
                        process_type: '',
                        bom_item_id: '',
                        target_qty: '',
                        warning_limit_qty: '',
                    });
                },
                onPartChange(index) {
                    const row = this.rows[index];
                    const selected = this.subconPartsOptions.find(opt => opt.key === row.selected_part_key);
                    if (selected) {
                        row.gci_part_id = selected.id;
                        row.rm_gci_part_id = selected.rm_part_id;
                        row.process_type = selected.process_name;
                        row.bom_item_id = selected.bom_item_id;
                    } else {
                        row.gci_part_id = '';
                        row.rm_gci_part_id = '';
                        row.process_type = '';
                        row.bom_item_id = '';
                    }
                },
            }));
        });
    </script>
</x-app-layout>
