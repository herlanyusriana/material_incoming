<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-3 w-10"><input type="checkbox" class="rounded border-slate-300 text-indigo-600" :checked="allVisibleSelected()" @click.stop="toggleSelectAll($event.target.checked)"></th>
                    <th class="px-3 py-3 text-left font-semibold">Part</th>
                    <th class="px-3 py-3 text-left font-semibold">Model</th>
                    <th class="px-3 py-3 text-left font-semibold">Policy</th>
                    <th class="px-3 py-3 text-left font-semibold">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($parts as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2.5" @click.stop>
                            <input type="checkbox" class="rounded border-slate-300 text-indigo-600" :value="{{ $p->id }}" x-model="selectedPartIds">
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="font-mono font-semibold text-slate-900">{{ $p->part_no }}</span>
                            <span class="block text-xs text-slate-500">{{ $p->part_name }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-slate-600">{{ $p->model ?? '-' }}</td>
                        <td class="px-3 py-2.5">@include('parts._policy_badge', ['policy' => $p->consumption_policy ?: (($p->is_backflush ?? true) ? 'backflush_return' : 'direct_issue'), 'part' => $p])</td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center w-2 h-2 rounded-full {{ $p->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            <span class="ml-1.5 text-xs {{ $p->status === 'active' ? 'text-slate-700' : 'text-slate-400' }}">{{ $p->status }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm" @click="openEditPart(@js($p))">Edit</button>
                            <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Hapus part {{ $p->part_no }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="font-semibold text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8-4-8 4m0 0v10l8 4m0-10 8-4m-8 4v10m8-14v10l-8 4"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-600">Tidak ada Work in Progress.</p>
                        <p class="mt-1 text-xs text-slate-400">Coba ubah filter atau tambah part baru.</p>
                        <button type="button" @click="openCreatePart()" class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">+ Add Part</button>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $parts->links() }}</div>
</div>
