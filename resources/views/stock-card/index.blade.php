<x-app-layout>
    @php
        $classificationOptions = [
            '' => 'Semua (RM + FG)',
            'RM' => 'Raw Material (RM)',
            'FG' => 'Finished Goods (FG)',
        ];
    @endphp

    <x-page-header
        title="Stock Card — Saldo Stok"
        subtitle="Saldo real-time per part &amp; lokasi. Sumber: sistem (bukan Excel)."
        :breadcrumbs="[
            ['label' => 'Inventory', 'url' => route('inventory.index')],
            ['label' => 'Stock Card']
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('stock-card.export', array_filter(['search' => $search, 'classification' => $classification])) }}"
               class="gci-btn-primary gci-btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Export Excel</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- Notifications --}}
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 animate-fade-in-up">
            {{ session('success') }}
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 animate-fade-in-up">
            <div class="font-bold">Cek lagi inputnya:</div>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stats summary --}}
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Parts</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($summary['total_parts'] ?? 0) }}</div>
            <div class="text-xs text-slate-400 mt-1">Part unik dengan stok > 0</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Stok</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format((float) ($summary['total_qty'] ?? 0), 0, ',', '.') }}</div>
            <div class="text-xs text-slate-400 mt-1">RM + FG</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Status</div>
            <div class="mt-2 text-base font-semibold text-slate-700">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Data real-time dari sistem
                </span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Terakhir update: {{ now()->format('H:i') }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 shadow-sm">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-64">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Part</label>
                <input type="text" name="search" value="{{ $search }}"
                       class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                       placeholder="Part no / Name / Model...">
            </div>
            <div class="w-full sm:w-48">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Klasifikasi</label>
                <select name="classification"
                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    @foreach ($classificationOptions as $value => $label)
                        <option value="{{ $value }}" @selected($classification === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="gci-btn-primary gci-btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v2.586a1 1 0 0 1-.293.707l-6.414 6.414a1 1 0 0 0-.293.707V17l-4 4v-6.586a1 1 0 0 0-.293-.707L3.293 7.293A1 1 0 0 1 3 6.586V4Z" />
                    </svg>
                    Filter
                </button>
                @if ($search !== '' || $classification !== '')
                    <a href="{{ route('stock-card.index') }}"
                       class="gci-btn-secondary gci-btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm divide-y divide-slate-200">
                <colgroup>
                    <col class="w-[180px]">
                    <col class="w-[280px]">
                    <col class="w-[80px]">
                    <col class="w-[90px]">
                    <col class="w-[200px]">
                    <col class="w-[180px]">
                </colgroup>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Part No</th>
                        <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Part Name / Model</th>
                        <th class="px-3 py-3 text-center font-black text-slate-600 uppercase tracking-wider text-xs">Class</th>
                        <th class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">Saldo</th>
                        <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Lokasi</th>
                        <th class="px-4 py-3 text-center font-black text-slate-600 uppercase tracking-wider text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($rows as $row)
                        @php
                            $partId = $row->gci_part_id;
                            $bgClass = $row->classification === 'RM' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700';
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-4 py-3 font-mono text-sm text-slate-700">{{ $row->part_no ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $row->part_name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $row->model ?? '' }}</div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $bgClass }}">
                                    {{ $row->classification ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                                {{ number_format((float) ($row->total_qty ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 truncate max-w-[200px]">
                                {{ $row->locations ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    type="button"
                                    onclick="showMutations({{ $partId }}, '{{ addslashes($row->part_no ?? '') }}')"
                                    class="gci-btn-secondary gci-btn-sm text-xs inline-flex items-center gap-1.5"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Mutasi
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4" />
                                    </svg>
                                    <span class="text-sm font-medium">Tidak ada data stok</span>
                                    <span class="text-xs">Belum ada part dengan saldo > 0 di RM atau FG.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-slate-200 px-4 py-3 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-slate-500">
                Menampilkan <span class="font-semibold text-slate-700">{{ $rows->firstItem() ?? 0 }}</span> -
                <span class="font-semibold text-slate-700">{{ $rows->lastItem() ?? 0 }}</span>
                dari <span class="font-semibold text-slate-700">{{ $rows->total() }}</span> part
            </div>
            <div class="flex gap-1">
                {{ $rows->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Mutasi --}}
    <x-modal name="mutasi-modal" maxWidth="2xl">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900" id="mutasi-title">Riwayat Mutasi</h2>
                    <p class="text-sm text-slate-500 mt-0.5" id="mutasi-subtitle">Loading...</p>
                </div>
                <button type="button" @click="$dispatch('close-modal', 'mutasi-modal')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="mutasi-content" class="max-h-[60vh] overflow-y-auto">
                <div class="text-center py-8 text-slate-400 text-sm">Memuat data...</div>
            </div>
        </div>
    </x-modal>

    <script>
        function showMutations(partId, partNo) {
            const modal = document.querySelector('[x-data]')?.__x?.$dispatch('open-modal', 'mutasi-modal');
            // Fallback jika Alpine belum ready
            if (!modal) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'mutasi-modal' }));
            }

            document.getElementById('mutasi-title').textContent = 'Riwayat Mutasi — ' + partNo;
            document.getElementById('mutasi-subtitle').textContent = 'Loading...';
            document.getElementById('mutasi-content').innerHTML = `
                <div class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    <span class="text-sm text-slate-400 mt-2 block">Memuat riwayat mutasi...</span>
                </div>
            `;

            const url = '{{ route("stock-card.mutations", ":id") }}'.replace(':id', partId);

            fetch(url, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.html) {
                    document.getElementById('mutasi-content').innerHTML = data.html;
                    document.getElementById('mutasi-subtitle').textContent = 'Riwayat 100 mutasi terakhir';
                } else {
                    throw new Error('Gagal memuat data');
                }
            })
            .catch(() => {
                document.getElementById('mutasi-content').innerHTML = `
                    <div class="text-center py-8 text-red-500 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75v.008h.008V15.75H12Z" />
                        </svg>
                        Gagal memuat riwayat mutasi.
                        <br><span class="text-xs text-slate-400">Coba refresh halaman atau cek koneksi.</span>
                    </div>
                `;
            });
        }
    </script>
</x-app-layout>