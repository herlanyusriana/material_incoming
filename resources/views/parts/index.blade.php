<x-app-layout>
    <x-slot name="header">Parts Master</x-slot>

    <div class="py-3" x-data="partsMaster()">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            {{-- Classification Tabs --}}
            @php
                $tabs = [
                    'RM'  => ['label' => 'Raw Material', 'icon' => '📦', 'color' => 'emerald'],
                    'FG'  => ['label' => 'Finished Goods', 'icon' => '🏭', 'color' => 'blue'],
                    'WIP' => ['label' => 'Work in Progress', 'icon' => '⚙️', 'color' => 'amber'],
                    'SUB' => ['label' => 'Substitute', 'icon' => '🔄', 'color' => 'purple'],
                ];
                $activeTab = $classification ?? 'RM';
            @endphp
            <div class="flex items-center gap-2 border-b border-slate-200 pb-0">
                @foreach($tabs as $key => $tab)
                    <a href="{{ route('parts.index', ['classification' => $key, 'status' => $status ?? '']) }}"
                       class="px-5 py-2.5 text-sm font-semibold rounded-t-xl border border-b-0 transition-all
                              {{ $activeTab === $key
                                  ? 'bg-white text-slate-900 border-slate-200 -mb-px z-10 shadow-sm'
                                  : 'bg-slate-50 text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-100' }}">
                        <span class="mr-1.5">{{ $tab['icon'] }}</span>{{ $tab['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white shadow-lg border border-slate-200 rounded-b-2xl rounded-tr-2xl p-4 space-y-4">
                {{-- Filters --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        <input type="hidden" name="classification" value="{{ $activeTab }}">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200 text-sm">
                                <option value="">All</option>
                                <option value="active" @selected(($status ?? '') === 'active')>Active</option>
                                <option value="inactive" @selected(($status ?? '') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                            <input name="q" value="{{ $search ?? '' }}" class="rounded-xl border-slate-200 pl-10 text-sm" placeholder="Search part...">
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Filter</button>
                    </form>

                    @if($activeTab !== 'SUB')
                        <div class="flex items-center gap-2">
                            <a href="{{ route('parts.export', ['classification' => $activeTab]) }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold">Export</a>
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold" @click="importOpen=true">Import</button>
                            <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold" @click="openCreatePart()">+ Add Part</button>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <a href="{{ route('planning.boms.substitutes.export') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold">Export Substitute</a>
                            <a href="{{ route('planning.boms.substitutes.template') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold">Template</a>
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold" @click="subImportOpen=true">Import Substitute</button>
                        </div>
                    @endif
                </div>

                {{-- RM Table --}}
                @if($activeTab === 'RM')
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-emerald-50">
                                <tr class="text-emerald-700 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold w-8"></th>
                                    <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                    <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                    <th class="px-4 py-3 text-left font-semibold">Size</th>
                                    <th class="px-4 py-3 text-center font-semibold">Vendors</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($parts as $p)
                                    <tr class="hover:bg-slate-50 cursor-pointer" @click="toggle({{ $p->id }})">
                                        <td class="px-4 py-3">
                                            @if($p->vendorLinks->count() > 0)
                                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="expanded[{{ $p->id }}] && 'rotate-90'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $p->part_no }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $p->part_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $p->size ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ $p->vendorLinks->count() > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $p->vendorLinks->count() }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($p->status) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right" @click.stop>
                                            <div class="flex justify-end gap-1">
                                                <button type="button" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Part" @click="openEditPart(@js($p))">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button type="button" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Add Vendor" @click="openCreateVendorPart({{ $p->id }})">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                                </button>
                                                <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Delete this part?')">
                                                    @csrf @method('DELETE')
                                                    <button class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete Part">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Vendor parts expand --}}
                                    @if($p->vendorLinks->count() > 0)
                                        <template x-if="expanded[{{ $p->id }}]">
                                            <tr>
                                                <td colspan="7" class="px-0 py-0">
                                                    <div class="bg-gradient-to-r from-emerald-50/50 to-slate-50 border-l-4 border-emerald-300 mx-4 my-2 rounded-lg overflow-hidden">
                                                        <table class="min-w-full text-xs divide-y divide-emerald-100">
                                                            <thead class="bg-emerald-50/80">
                                                                <tr class="text-emerald-600 uppercase tracking-wider">
                                                                    <th class="px-4 py-2 text-left font-semibold">Vendor</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Vendor Part No</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Vendor Part Name</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Register No</th>
                                                                    <th class="px-4 py-2 text-right font-semibold">Price</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">UOM</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Status</th>
                                                                    <th class="px-4 py-2 text-right font-semibold">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-emerald-50 bg-white/60">
                                                                @foreach($p->vendorLinks as $vl)
                                                                    <tr class="hover:bg-emerald-50/40">
                                                                        <td class="px-4 py-2 font-semibold text-slate-800">{{ $vl->vendor->vendor_name ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-slate-700">{{ $vl->vendor_part_no ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-slate-700">{{ $vl->vendor_part_name ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-slate-600">{{ $vl->register_no ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($vl->price, 2) }}</td>
                                                                        <td class="px-4 py-2 text-slate-600">{{ $vl->uom ?? '-' }}</td>
                                                                        <td class="px-4 py-2">
                                                                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $vl->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($vl->status) }}</span>
                                                                        </td>
                                                                        <td class="px-4 py-2 text-right">
                                                                            <div class="flex justify-end gap-1">
                                                                                <button type="button" class="p-1 text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors" title="Edit Vendor Part" @click="openEditVendorPart(@js($vl))">
                                                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                </button>
                                                                                <form action="{{ route('parts.vendor-parts.destroy', $vl) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                                                                    @csrf @method('DELETE')
                                                                                    <button class="p-1 text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete Vendor Part">
                                                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    @endif
                                @empty
                                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No RM parts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- FG Table --}}
                @if($activeTab === 'FG')
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-blue-50">
                                <tr class="text-blue-700 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold w-8"></th>
                                    <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                    <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                    <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                    <th class="px-4 py-3 text-left font-semibold">Model</th>
                                    <th class="px-4 py-3 text-center font-semibold">Customer Parts</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($parts as $p)
                                    <tr class="hover:bg-slate-50 cursor-pointer" @click="toggle({{ $p->id }})">
                                        <td class="px-4 py-3">
                                            @if($p->customerPartUsages->count() > 0)
                                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="expanded[{{ $p->id }}] && 'rotate-90'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($p->customer)
                                                <span class="font-bold text-blue-700">{{ $p->customer->code ?? '' }}</span>
                                                <div class="text-[10px] text-slate-500">{{ $p->customer->name }}</div>
                                            @else
                                                <span class="text-slate-400 italic text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $p->part_no }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $p->part_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $p->model ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ $p->customerPartUsages->count() > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">{{ $p->customerPartUsages->count() }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($p->status) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right" @click.stop>
                                            <div class="flex justify-end gap-1">
                                                <button type="button" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Part" @click="openEditPart(@js($p))">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Delete this part?')">
                                                    @csrf @method('DELETE')
                                                    <button class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete Part">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Customer parts expand --}}
                                    @if($p->customerPartUsages->count() > 0)
                                        <template x-if="expanded[{{ $p->id }}]">
                                            <tr>
                                                <td colspan="8" class="px-0 py-0">
                                                    <div class="bg-gradient-to-r from-blue-50/50 to-slate-50 border-l-4 border-blue-300 mx-4 my-2 rounded-lg overflow-hidden">
                                                        <table class="min-w-full text-xs divide-y divide-blue-100">
                                                            <thead class="bg-blue-50/80">
                                                                <tr class="text-blue-600 uppercase tracking-wider">
                                                                    <th class="px-4 py-2 text-left font-semibold">Customer</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Customer Part No</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Customer Part Name</th>
                                                                    <th class="px-4 py-2 text-left font-semibold">Line</th>
                                                                    <th class="px-4 py-2 text-right font-semibold">Qty/Unit</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-blue-50 bg-white/60">
                                                                @foreach($p->customerPartUsages as $cpu)
                                                                    <tr class="hover:bg-blue-50/40">
                                                                        <td class="px-4 py-2 font-semibold text-slate-800">{{ $cpu->customerPart->customer->name ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-slate-700">{{ $cpu->customerPart->customer_part_no ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-slate-700">{{ $cpu->customerPart->customer_part_name ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-slate-600">{{ $cpu->customerPart->line ?? '-' }}</td>
                                                                        <td class="px-4 py-2 text-right font-medium text-slate-900">{{ $cpu->qty_per_unit }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    @endif
                                @empty
                                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No FG parts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- WIP Table --}}
                @if($activeTab === 'WIP')
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-amber-50">
                                <tr class="text-amber-700 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                    <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                    <th class="px-4 py-3 text-left font-semibold">Model</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($parts as $p)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $p->part_no }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $p->part_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $p->model ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($p->status) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1">
                                                <button type="button" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Part" @click="openEditPart(@js($p))">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Delete this part?')">
                                                    @csrf @method('DELETE')
                                                    <button class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete Part">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No WIP parts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- SUB Table --}}
                @if($activeTab === 'SUB')
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-purple-50">
                                <tr class="text-purple-700 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">Substitute Part No</th>
                                    <th class="px-4 py-3 text-left font-semibold">Substitute Part Name</th>
                                    <th class="px-4 py-3 text-left font-semibold">Component Part</th>
                                    <th class="px-4 py-3 text-left font-semibold">FG Part</th>
                                    <th class="px-4 py-3 text-center font-semibold">Ratio</th>
                                    <th class="px-4 py-3 text-center font-semibold">Priority</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold">Notes</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($substitutes ?? [] as $sub)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $sub->substitute_part_no ?? ($sub->part->part_no ?? '-') }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $sub->part->part_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">
                                            <div class="font-medium">{{ $sub->bomItem->componentPart->part_no ?? '-' }}</div>
                                            <div class="text-[10px] text-slate-500">{{ $sub->bomItem->componentPart->part_name ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            <div class="font-medium">{{ $sub->bomItem->bom->part->part_no ?? '-' }}</div>
                                            <div class="text-[10px] text-slate-500">{{ $sub->bomItem->bom->part->part_name ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-medium text-slate-900">{{ $sub->ratio }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold bg-purple-100 text-purple-700">{{ $sub->priority }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $sub->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($sub->status) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-500 max-w-[200px] truncate" title="{{ $sub->notes }}">{{ $sub->notes ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1">
                                                <button type="button"
                                                    class="px-2 py-1 text-[10px] font-semibold rounded-md border border-indigo-200 text-indigo-700 hover:bg-indigo-50"
                                                    @click="openEditSubFromSubTab(@js([
                                                        'id' => $sub->id,
                                                        'substitute_part_id' => $sub->substitute_part_id,
                                                        'substitute_part_no' => $sub->part?->part_no ?? $sub->substitute_part_no,
                                                        'ratio' => $sub->ratio,
                                                        'priority' => $sub->priority,
                                                        'status' => $sub->status,
                                                        'notes' => $sub->notes,
                                                        'fg_part_no' => $sub->bomItem->bom->part->part_no ?? '',
                                                        'component_part_no' => $sub->bomItem->componentPart->part_no ?? $sub->bomItem->component_part_no,
                                                    ]))">Edit</button>
                                                <form action="{{ route('planning.gci-part-substitutes.destroy', $sub) }}" method="POST" class="inline" onsubmit="return confirm('Hapus substitute ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2 py-1 text-[10px] font-semibold rounded-md border border-red-200 text-red-700 hover:bg-red-50">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">No substitute parts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-4">
                    @if($activeTab === 'SUB')
                        {{ ($substitutes ?? collect())->links() }}
                    @else
                        {{ $parts->links() }}
                    @endif
                </div>
            </div>
        </div>

        {{-- GCI Part Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="partModal" x-cloak @keydown.escape.window="partModal=false">
            <div class="w-full bg-white rounded-2xl shadow-xl border border-slate-200" :class="subsOpen ? 'max-w-4xl' : 'max-w-lg'">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="partMode === 'create' ? 'Add Part' : 'Edit Part'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="partModal=false">✕</button>
                </div>
                <form :action="partAction" method="POST" class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    @csrf
                    <template x-if="partMode==='edit'"><input type="hidden" name="_method" value="PUT"></template>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Part No <span class="text-red-500">*</span></label>
                            <input name="part_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required x-model="partForm.part_no">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Classification <span class="text-red-500">*</span></label>
                            <select name="classification" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required x-model="partForm.classification">
                                <option value="FG">FG</option>
                                <option value="WIP">WIP</option>
                                <option value="RM">RM</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Part Name</label>
                        <input name="part_name" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.part_name">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Size</label>
                            <input name="size" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. 100x50x2mm" x-model="partForm.size">
                        </div>
                        <div x-show="partForm.classification !== 'RM'" x-cloak>
                            <label class="text-sm font-semibold text-slate-700">Model</label>
                            <input name="model" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.model">
                        </div>
                    </div>
                    <div x-show="partForm.classification === 'RM'" x-cloak>
                        <label class="text-sm font-semibold text-slate-700">Assign Vendor</label>
                        <div class="mt-1 border border-slate-200 rounded-xl max-h-48 overflow-y-auto">
                            <div class="px-3 py-2 sticky top-0 bg-white border-b border-slate-100">
                                <input type="text" x-model="vendorSearch" placeholder="Search vendor..." class="w-full text-sm rounded-lg border-slate-200 px-2 py-1">
                            </div>
                            @foreach($vendors as $v)
                                <label class="flex items-center px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm" x-show="!vendorSearch || '{{ strtolower($v->vendor_name) }}'.includes(vendorSearch.toLowerCase())">
                                    <input type="checkbox" name="vendor_ids[]" value="{{ $v->id }}" class="rounded border-slate-300 text-indigo-600 mr-2" :checked="partForm.vendor_ids.includes({{ $v->id }})">
                                    <span class="text-slate-700">{{ $v->vendor_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div x-show="partMode==='edit' && partForm.classification === 'RM'" x-cloak class="rounded-xl border border-orange-200 bg-orange-50/70 overflow-hidden">
                        <button type="button" class="w-full px-4 py-2.5 flex items-center justify-between text-left" @click="subsOpen = !subsOpen">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-orange-700 text-sm">Substitutes Detail</span>
                                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-orange-100 text-orange-700 text-[10px] font-bold" x-text="(partForm.substitutes_for || []).length"></span>
                            </div>
                            <span class="text-xs text-orange-700" x-text="subsOpen ? 'Hide' : 'Show'"></span>
                        </button>
                        <div x-show="subsOpen" class="border-t border-orange-200 px-4 py-3 space-y-3">
                            <template x-if="(partForm.substitutes_for || []).length > 0">
                                <div class="overflow-x-auto border border-orange-100 rounded-lg bg-white">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-orange-50 text-orange-700">
                                            <tr>
                                                <th class="text-left px-2 py-1.5 font-semibold">FG</th>
                                                <th class="text-left px-2 py-1.5 font-semibold">Substitute</th>
                                                <th class="text-center px-2 py-1.5 font-semibold">Ratio</th>
                                                <th class="text-center px-2 py-1.5 font-semibold">Priority</th>
                                                <th class="text-left px-2 py-1.5 font-semibold">Status</th>
                                                <th class="text-right px-2 py-1.5 font-semibold">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="s in partForm.substitutes_for" :key="s.id">
                                                <tr class="border-t border-orange-50">
                                                    <td class="px-2 py-1.5 font-mono text-[11px]" x-text="s.fg_part_no"></td>
                                                    <td class="px-2 py-1.5">
                                                        <div class="font-mono font-semibold text-indigo-700" x-text="s.substitute_part_no"></div>
                                                        <div class="text-slate-500" x-text="s.substitute_part_name || ''"></div>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-center" x-text="s.ratio"></td>
                                                    <td class="px-2 py-1.5 text-center" x-text="s.priority"></td>
                                                    <td class="px-2 py-1.5">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                                              :class="s.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                                              x-text="(s.status || 'inactive').toUpperCase()"></span>
                                                    </td>
                                                    <td class="px-2 py-1.5">
                                                        <div class="flex justify-end gap-1">
                                                            <button type="button" class="px-2 py-1 rounded-md border border-indigo-200 text-indigo-700 hover:bg-indigo-50 text-[10px] font-semibold" @click="editSub(s)">Edit</button>
                                                            <button type="button" class="px-2 py-1 rounded-md border border-red-200 text-red-700 hover:bg-red-50 text-[10px] font-semibold" @click="deleteSub(s)">Hapus</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <template x-if="(partForm.substitutes_for || []).length === 0">
                                <div class="text-slate-500 italic text-xs">Belum ada substitute</div>
                            </template>

                            <template x-if="(partForm.as_substitute || []).length > 0">
                                <div class="rounded-lg border border-slate-200 bg-white p-2">
                                    <div class="font-semibold text-slate-600 text-[10px] uppercase tracking-wider mb-1">Dipakai sebagai substitute untuk</div>
                                    <template x-for="s in partForm.as_substitute" :key="s.id">
                                        <div class="text-xs text-slate-700">
                                            <span class="font-mono" x-text="s.fg_part_no"></span>
                                            <span class="mx-1">-</span>
                                            <span class="font-mono" x-text="s.original_rm_part_no"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div class="text-[11px] text-orange-800 bg-orange-100/70 px-2 py-1.5 rounded">
                                Management substitute bersifat per BOM FG.
                            </div>
                        </div>
                    </div>
                    <div x-show="partForm.classification === 'FG' || partForm.classification === 'WIP'" x-cloak>
                        <label class="text-sm font-semibold text-slate-700">Assign Customer</label>
                        <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.customer_id">
                            <option value="">-- No Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->code ?? '' }} - {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="partModal=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Save</button>
                    </div>
                </form>
                <form x-show="partMode==='edit' && partForm.classification === 'RM' && subsOpen" x-cloak :action="subFormAction" method="POST" class="px-5 pb-5 pt-3 border-t border-slate-200 bg-slate-50 space-y-3">
                    @csrf
                    <template x-if="subEditId"><input type="hidden" name="_method" value="PUT"></template>
                    <template x-if="subEditId"><input type="hidden" name="fg_part_id" :value="subForm.fg_part_id"></template>
                    <div class="font-semibold text-slate-700 text-sm" x-text="subEditId ? 'Edit Substitute' : '+ Tambah Substitute'"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-700">BOM FG <span class="text-red-600">*</span></label>
                            <select name="fg_part_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm" required x-model="subForm.fg_part_id" :disabled="!!subEditId">
                                <option value="">Pilih FG...</option>
                                <template x-for="fg in subFgOptions" :key="fg.id">
                                    <option :value="String(fg.id)" x-text="fg.part_no + ' - ' + (fg.part_name || '')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Substitute RM <span class="text-red-600">*</span></label>
                            <select name="substitute_part_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm" required x-model="subForm.substitute_part_id">
                                <option value="">Pilih RM...</option>
                                @foreach(($rmParts ?? collect()) as $rm)
                                    <option value="{{ $rm->id }}" x-show="String({{ $rm->id }}) !== String(partForm.id || '')">{{ $rm->part_no }} - {{ $rm->part_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Ratio</label>
                            <input type="number" step="0.0001" min="0.0001" name="ratio" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.ratio">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Priority</label>
                            <input type="number" min="1" name="priority" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.priority">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Status</label>
                            <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-3 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold" x-text="subEditId ? 'Update' : 'Tambah'"></button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700">Notes</label>
                        <input type="text" name="notes" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.notes">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold" @click="cancelSubEdit()" x-show="subEditId">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Vendor Part Modal (RM only) --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="vpModal" x-cloak @keydown.escape.window="vpModal=false">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="vpMode === 'create' ? 'Add Vendor Part' : 'Edit Vendor Part'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="vpModal=false">✕</button>
                </div>
                <form :action="vpAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="vpMode==='edit'"><input type="hidden" name="_method" value="PUT"></template>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Vendor <span class="text-red-500">*</span></label>
                        <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required x-model="vpForm.vendor_id">
                            <option value="">Select vendor...</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Vendor Part No</label>
                            <input name="vendor_part_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.vendor_part_no">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Register No</label>
                            <input name="register_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.register_no">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Vendor Part Name</label>
                        <input name="vendor_part_name" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.vendor_part_name">
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Price</label>
                            <input name="price" type="number" step="0.001" min="0" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.price">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">UOM</label>
                            <input name="uom" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="PCS" x-model="vpForm.uom">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">HS Code</label>
                            <input name="hs_code" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.hs_code">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Quality Inspection</label>
                            <select name="quality_inspection" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.quality_inspection">
                                <option value="">No</option>
                                <option value="YES">Yes</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Status</label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="vpModal=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Substitute Edit Modal (SUB tab) --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
             x-show="subListEditOpen" x-cloak @keydown.escape.window="subListEditOpen=false">
            <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Edit Substitute</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="subListEditOpen=false">✕</button>
                </div>
                <form :action="subListEditAction" method="POST" class="px-5 py-4 space-y-3">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">FG</label>
                            <input type="text" class="mt-1 w-full rounded-lg border-slate-200 text-sm bg-slate-100" x-model="subListForm.fg_part_no" readonly>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Component RM</label>
                            <input type="text" class="mt-1 w-full rounded-lg border-slate-200 text-sm bg-slate-100" x-model="subListForm.component_part_no" readonly>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Substitute RM</label>
                            <select name="substitute_part_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.substitute_part_id" required>
                                <option value="">Pilih RM...</option>
                                @foreach(($rmParts ?? collect()) as $rm)
                                    <option value="{{ $rm->id }}">{{ $rm->part_no }} - {{ $rm->part_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Ratio</label>
                            <input type="number" step="0.0001" min="0.0001" name="ratio" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.ratio" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Priority</label>
                            <input type="number" min="1" name="priority" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.priority" required>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Notes</label>
                        <input type="text" name="notes" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.notes">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold" @click="subListEditOpen=false">Cancel</button>
                        <button type="submit" class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">Update</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Import Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="importOpen" x-cloak @keydown.escape.window="importOpen=false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Import Parts</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="importOpen=false">✕</button>
                </div>
                <form action="{{ route('parts.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Excel File</label>
                        <input type="file" name="file" accept=".xlsx,.xls" required class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="importOpen=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Import Substitute Modal (SUB tab) --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="subImportOpen" x-cloak @keydown.escape.window="subImportOpen=false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Import Substitute Excel</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="subImportOpen=false">✕</button>
                </div>
                <form action="{{ route('planning.boms.substitutes.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Excel File</label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-slate-500">Gunakan file dari tombol <span class="font-semibold">Export Substitute</span> atau <span class="font-semibold">Template</span>.</p>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="auto_create_parts" value="1" class="rounded border-slate-300">
                        Auto create RM part jika belum ada di master
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="subImportOpen=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function partsMaster() {
                return {
                    expanded: {},
                    importOpen: false,
                    subImportOpen: false,
                    activeTab: @js($activeTab),

                    // GCI Part modal
                    partModal: false,
                    partMode: 'create',
                    partAction: @js(route('parts.store')),
                    subsOpen: false,
                    vendorSearch: '',
                    partForm: { id: null, customer_id: '', part_no: '', part_name: '', size: '', model: '', classification: @js($activeTab), status: 'active', vendor_ids: [], substitutes_for: [], as_substitute: [] },
                    subEditId: null,
                    subFormAction: '',
                    subForm: { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' },
                    subFgOptions: [],

                    // Vendor Part modal
                    vpModal: false,
                    vpMode: 'create',
                    vpAction: '',
                    vpForm: { vendor_id: '', vendor_part_no: '', vendor_part_name: '', register_no: '', price: 0, uom: '', hs_code: '', quality_inspection: '', status: 'active' },
                    subListEditOpen: false,
                    subListEditAction: '',
                    subListForm: { id: '', fg_part_no: '', component_part_no: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' },

                    toggle(id) { this.expanded[id] = !this.expanded[id]; },

                    openCreatePart() {
                        this.partMode = 'create';
                        this.partAction = @js(route('parts.store'));
                        this.subsOpen = false;
                        this.vendorSearch = '';
                        this.partForm = { id: null, customer_id: '', part_no: '', part_name: '', size: '', model: '', classification: this.activeTab, status: 'active', vendor_ids: [], substitutes_for: [], as_substitute: [] };
                        this.subEditId = null;
                        this.subFormAction = '';
                        this.subForm = { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' };
                        this.subFgOptions = [];
                        this.partModal = true;
                    },
                    openEditPart(p) {
                        this.partMode = 'edit';
                        this.partAction = @js(url('/parts')) + '/' + p.id;
                        this.subsOpen = false;
                        this.vendorSearch = '';
                        const pvMap = @js($partVendorMap ?? []);
                        const partSubstitutesMap = @js($partSubstitutesMap ?? []);
                        const partAsSubstituteMap = @js($partAsSubstituteMap ?? []);
                        const rmFgMap = @js($rmFgMap ?? []);
                        const fgPartsWithBom = @js($fgPartsWithBom ?? []);
                        const fgMap = {};
                        (fgPartsWithBom || []).forEach(fg => { fgMap[String(fg.id)] = fg; });
                        const linkedVendors = pvMap[p.id] || [];
                        const linkedFgs = (rmFgMap[p.id] || []).map(id => fgMap[String(id)]).filter(Boolean);
                        this.subFgOptions = linkedFgs;
                        this.partForm = {
                            id: p.id,
                            customer_id: p.customer_id || '',
                            part_no: p.part_no,
                            part_name: p.part_name || '',
                            size: p.size || '',
                            model: p.model || '',
                            classification: p.classification,
                            status: p.status,
                            vendor_ids: linkedVendors,
                            substitutes_for: partSubstitutesMap[p.id] || [],
                            as_substitute: partAsSubstituteMap[p.id] || [],
                        };
                        this.subFormAction = @js(url('/planning/gci-parts')) + '/' + p.id + '/substitutes';
                        this.cancelSubEdit();
                        this.partModal = true;
                    },
                    editSub(s) {
                        this.subEditId = s.id;
                        this.subFormAction = @js(url('/planning/gci-part-substitutes')) + '/' + s.id;
                        this.subForm = {
                            fg_part_id: String(s.fg_part_id || ''),
                            substitute_part_id: String(s.substitute_part_id || ''),
                            ratio: s.ratio || 1,
                            priority: s.priority || 1,
                            status: s.status || 'active',
                            notes: s.notes || '',
                        };
                        this.subsOpen = true;
                    },
                    cancelSubEdit() {
                        this.subEditId = null;
                        if (this.partForm.id) {
                            this.subFormAction = @js(url('/planning/gci-parts')) + '/' + this.partForm.id + '/substitutes';
                        }
                        this.subForm = { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' };
                    },
                    deleteSub(s) {
                        if (!confirm('Hapus substitute ' + s.substitute_part_no + '?')) return;
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = @js(url('/planning/gci-part-substitutes')) + '/' + s.id;
                        form.innerHTML = '@csrf @method("DELETE")';
                        document.body.appendChild(form);
                        form.submit();
                    },
                    openEditSubFromSubTab(s) {
                        this.subListEditAction = @js(url('/planning/gci-part-substitutes')) + '/' + s.id;
                        this.subListForm = {
                            id: String(s.id || ''),
                            fg_part_no: s.fg_part_no || '',
                            component_part_no: s.component_part_no || '',
                            substitute_part_id: String(s.substitute_part_id || ''),
                            ratio: s.ratio || 1,
                            priority: s.priority || 1,
                            status: s.status || 'active',
                            notes: s.notes || '',
                        };
                        this.subListEditOpen = true;
                    },

                    openCreateVendorPart(partId) {
                        this.vpMode = 'create';
                        this.vpAction = @js(url('/parts')) + '/' + partId + '/vendor-parts';
                        this.vpForm = { vendor_id: '', vendor_part_no: '', vendor_part_name: '', register_no: '', price: 0, uom: '', hs_code: '', quality_inspection: '', status: 'active' };
                        this.vpModal = true;
                    },
                    openEditVendorPart(vl) {
                        this.vpMode = 'edit';
                        this.vpAction = @js(url('/vendor-parts')) + '/' + vl.id;
                        this.vpForm = {
                            vendor_id: vl.vendor_id,
                            vendor_part_no: vl.vendor_part_no || '',
                            vendor_part_name: vl.vendor_part_name || '',
                            register_no: vl.register_no || '',
                            price: vl.price || 0,
                            uom: vl.uom || '',
                            hs_code: vl.hs_code || '',
                            quality_inspection: vl.quality_inspection ? 'YES' : '',
                            status: vl.status || 'active',
                        };
                        this.vpModal = true;
                    },
                }
            }
        </script>
    </div>
</x-app-layout>
