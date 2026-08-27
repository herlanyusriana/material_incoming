<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-3 w-10"><input type="checkbox" class="rounded border-slate-300 text-indigo-600" :checked="allVisibleSelected()" @click.stop="toggleSelectAll($event.target.checked)"></th>
                    <th class="px-3 py-3 w-8"></th>
                    <th class="px-3 py-3 text-left font-semibold">Part</th>
                    <th class="px-3 py-3 text-left font-semibold">Part Vendor</th>
                    <th class="px-3 py-3 text-left font-semibold">Policy</th>
                    <th class="px-3 py-3 text-left font-semibold">Vendor</th>
                    <th class="px-3 py-3 text-left font-semibold">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($parts as $p)
                    <tr class="hover:bg-slate-50 cursor-pointer" @click="toggle({{ $p->id }})">
                        <td class="px-3 py-2.5" @click.stop>
                            <input type="checkbox" class="rounded border-slate-300 text-indigo-600" :value="{{ $p->id }}" x-model="selectedPartIds">
                        </td>
                        <td class="px-3 py-2.5">
                            @if ($p->vendorLinks->count())
                                <svg class="h-4 w-4 text-slate-400 transition-transform" :class="expanded[{{ $p->id }}] && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="font-mono font-semibold text-slate-900">{{ $p->part_no }}</span>
                            <span class="block text-xs text-slate-600">{{ $p->part_name }}</span>
                            @if ($p->subcount_enabled)
                                <span class="mt-0.5 inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 border border-blue-200 text-blue-700">SUBCOUNT {{ $p->subcount_process_type ?: 'PG' }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-slate-600">
                            @php $vendorNames = $p->vendorLinks->pluck('vendor_part_name')->filter()->unique()->values(); @endphp
                            @if ($vendorNames->isNotEmpty())
                                {{ $vendorNames->first() }}@if ($vendorNames->count() > 1)<span class="text-slate-400"> +{{ $vendorNames->count() - 1 }}</span>@endif
                            @else
                                <span class="text-slate-300">&ndash;</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">@include('parts._policy_badge', ['policy' => $p->consumption_policy ?: (($p->is_backflush ?? true) ? 'backflush_return' : 'direct_issue'), 'part' => $p])</td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ $p->vendorLinks->count() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">{{ $p->vendorLinks->count() }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center w-2 h-2 rounded-full {{ $p->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            <span class="ml-1.5 text-xs {{ $p->status === 'active' ? 'text-slate-700' : 'text-slate-500' }}">{{ $p->status }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap" @click.stop>
                            <div class="inline-flex items-center gap-1">
                                <button type="button" class="px-2 py-1.5 rounded-md text-sm font-semibold text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800" @click="openEditPart(@js($p))">Edit</button>
                                <button type="button" class="px-2 py-1.5 rounded-md text-sm font-semibold text-emerald-600 hover:bg-emerald-50 hover:text-emerald-800" @click="openCreateVendorPart({{ $p->id }})">+ Vendor</button>
                                <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus part {{ $p->part_no }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1.5 rounded-md text-sm font-semibold text-red-600 hover:bg-red-50 hover:text-red-800">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @if ($p->vendorLinks->count())
                        <template x-if="expanded[{{ $p->id }}]">
                            <tr>
                                <td colspan="8" class="p-0">
                                    <div class="bg-slate-50/80 border-y border-slate-100 py-2 px-6">
                                        <table class="min-w-full text-xs divide-y divide-slate-200 rounded-lg overflow-hidden border border-slate-200 bg-white">
                                            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold">Vendor</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Part No</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Part Name</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Register</th>
                                                    <th class="px-3 py-2 text-left font-semibold">UOM</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Status</th>
                                                    <th class="px-3 py-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($p->vendorLinks as $vl)
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-3 py-2 font-semibold text-slate-700">{{ $vl->vendor->vendor_name ?? '-' }}</td>
                                                        <td class="px-3 py-2 font-mono text-slate-600">{{ $vl->vendor_part_no ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-slate-600">{{ $vl->vendor_part_name ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-slate-500">{{ $vl->register_no ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-slate-500">{{ $vl->uom ?? '-' }}</td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-flex items-center w-1.5 h-1.5 rounded-full {{ $vl->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                                            <span class="ml-1 text-slate-500">{{ $vl->status }}</span>
                                                        </td>
                                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                                            <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800" @click="openEditVendorPart(@js($vl))">Edit</button>
                                                            <form action="{{ route('parts.vendor-parts.destroy', $vl) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Hapus vendor part ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="font-semibold text-red-600 hover:text-red-800">Hapus</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <p class="mt-1.5 text-[11px] text-slate-400">Harga diatur di <a href="{{ route('pricing.index') }}" class="font-semibold text-indigo-600 hover:underline">Pricing Master</a>.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    @endif
                @empty
                    <tr><td colspan="8" class="px-4 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8-4-8 4m0 0v10l8 4m0-10 8-4m-8 4v10m8-14v10l-8 4"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-600">Tidak ada Raw Material.</p>
                        <p class="mt-1 text-xs text-slate-400">Coba ubah filter atau tambah part baru.</p>
                        <button type="button" @click="openCreatePart()" class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">+ Add Part</button>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $parts->links() }}</div>
</div>
