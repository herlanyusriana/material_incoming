<x-app-layout>
    <x-slot name="header">{{ __('wo_tracking.create_title') }}</x-slot>

    <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <form id="woForm" method="POST" action="{{ route('production.wo-tracking.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.fg_part') }}</label>
                    <select name="gci_part_id" id="gci_part_id" required class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="">-- {{ __('wo_tracking.fg_part_ph') }} --</option>
                        @foreach ($fgParts as $p)
                            <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name }} ({{ $p->uom ?? 'PCE' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.qty_target') }}</label>
                        <input type="number" name="qty_target" id="qty_target" step="0.0001" min="0.0001" required class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.start_date') }}</label>
                        <input type="date" name="start_date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.notes') }}</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                </div>

                {{-- Preview kebutuhan (explosion) --}}
                <div id="explosionBox" class="hidden rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-indigo-700 mb-2">{{ __('wo_tracking.explosion_title') }}</h3>
                    <table class="min-w-full text-sm">
                        <thead><tr>
                            <th class="text-left text-xs text-slate-500 uppercase">{{ __('wo_tracking.th_component') }}</th>
                            <th class="text-right text-xs text-slate-500 uppercase">{{ __('wo_tracking.th_required') }}</th>
                            <th class="text-center text-xs text-slate-500 uppercase">{{ __('wo_tracking.th_policy') }}</th>
                        </tr></thead>
                        <tbody id="explosionBody" class="divide-y divide-indigo-100"></tbody>
                    </table>
                    <p id="explosionEmpty" class="hidden text-sm text-amber-700 mt-2">{{ __('wo_tracking.explosion_empty') }}</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">{{ __('wo_tracking.submit') }}</button>
                    <a href="{{ route('production.wo-tracking.index') }}" class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold">{{ __('wo_tracking.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const partSelect = document.getElementById('gci_part_id');
        const qtyInput = document.getElementById('qty_target');
        const box = document.getElementById('explosionBox');
        const body = document.getElementById('explosionBody');
        const emptyMsg = document.getElementById('explosionEmpty');
        let timer = null;
        const token = '{{ csrf_token() }}';
        const url = '{{ route('production.wo-tracking.preview-explosion') }}';

        async function refresh() {
            if (!partSelect.value || !qtyInput.value || parseFloat(qtyInput.value) <= 0) { box.classList.add('hidden'); return; }
            const fd = new FormData();
            fd.append('gci_part_id', partSelect.value);
            fd.append('qty_target', qtyInput.value);
            fd.append('_token', token);
            const res = await fetch(url, { method: 'POST', body: fd });
            const data = await res.json();
            body.innerHTML = '';
            (data.lines || []).forEach((l) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="py-2">${l.part_no ?? '-'}</td>
                    <td class="py-2 text-right tabular-nums">${Number(l.required_qty).toLocaleString()} ${l.uom}</td>
                    <td class="py-2 text-center text-xs">${l.policy}</td>`;
                body.appendChild(tr);
            });
            emptyMsg.classList.toggle('hidden', (data.lines || []).length > 0);
            if (!data.bom_found) { emptyMsg.textContent = @json(__('wo_tracking.explosion_no_bom')); emptyMsg.classList.remove('hidden'); }
            box.classList.remove('hidden');
        }

        partSelect.addEventListener('change', refresh);
        qtyInput.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(refresh, 400); });
    </script>
    @endpush
</x-app-layout>
