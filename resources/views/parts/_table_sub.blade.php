<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-3 text-left font-semibold">Substitute</th>
                    <th class="px-3 py-3 text-left font-semibold">Component RM</th>
                    <th class="px-3 py-3 text-left font-semibold">FG</th>
                    <th class="px-3 py-3 text-center font-semibold">Ratio</th>
                    <th class="px-3 py-3 text-center font-semibold">Prio</th>
                    <th class="px-3 py-3 text-left font-semibold">Status</th>
                    <th class="px-3 py-3 text-left font-semibold">Notes</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($substitutes ?? [] as $sub)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2.5">
                            <span class="font-mono font-semibold text-slate-900">{{ $sub->substitute_part_no ?? ($sub->part->part_no ?? '-') }}</span>
                            <span class="block text-xs text-slate-500">{{ $sub->part->part_name ?? '' }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="font-mono text-slate-700">{{ $sub->bomItem->componentPart->part_no ?? '-' }}</span>
                            <span class="block text-xs text-slate-400">{{ $sub->bomItem->componentPart->part_name ?? '' }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="font-mono text-slate-700">{{ $sub->bomItem->bom->part->part_no ?? '-' }}</span>
                            <span class="block text-xs text-slate-400">{{ $sub->bomItem->bom->part->part_name ?? '' }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-center font-mono text-slate-700">{{ $sub->ratio }}</td>
                        <td class="px-3 py-2.5 text-center text-slate-500">{{ $sub->priority }}</td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center w-2 h-2 rounded-full {{ $sub->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            <span class="ml-1.5 text-xs {{ $sub->status === 'active' ? 'text-slate-700' : 'text-slate-400' }}">{{ $sub->status }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-xs text-slate-500 max-w-48 truncate" title="{{ $sub->notes }}">{{ $sub->notes ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm" @click="openEditSubFromSubTab(@js([
                                'id' => $sub->id,
                                'substitute_part_id' => $sub->substitute_part_id,
                                'ratio' => $sub->ratio,
                                'priority' => $sub->priority,
                                'status' => $sub->status,
                                'notes' => $sub->notes,
                                'fg_part_no' => $sub->bomItem->bom->part->part_no ?? '',
                                'component_part_no' => $sub->bomItem->componentPart->part_no ?? $sub->bomItem->component_part_no,
                            ]))">Edit</button>
                            <form action="{{ route('planning.gci-part-substitutes.destroy', $sub) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Hapus substitute ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="font-semibold text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8-4-8 4m0 0v10l8 4m0-10 8-4m-8 4v10m8-14v10l-8 4"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-600">Tidak ada substitute.</p>
                        <p class="mt-1 text-xs text-slate-400">Coba ubah filter atau tutup &amp; buka kembali tab ini.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ ($substitutes ?? collect())->links() }}</div>
</div>
