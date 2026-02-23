<x-app-layout>
    <x-slot name="header">
        Master Inventory
    </x-slot>

    <div class="py-6" x-data="inventoryPage('{{ $activeTab }}')">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div
                    class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-800 flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Top Actions & Navigation -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <!-- Tabs -->
                <div class="flex p-1 bg-slate-100 rounded-xl w-full md:w-auto overflow-x-auto">
                    <button @click="switchTab('rm')"
                        :class="activeTab === 'rm' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'"
                        class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap flex-1 md:flex-none text-center">
                        📦 Raw Material (RM)
                    </button>
                    <button @click="switchTab('fg')"
                        :class="activeTab === 'fg' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'"
                        class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap flex-1 md:flex-none text-center">
                        🏭 Finished Goods / WIP
                    </button>
                </div>

                <!-- Global Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('inventory.transfers.index') }}"
                        class="px-4 py-2.5 rounded-xl border-2 border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-sm transition-all focus:ring-2 focus:ring-slate-200">
                        ⇆ Transfers
                    </a>
                </div>
            </div>

            <!-- ============================== RM TAB ============================== -->
            <div x-show="activeTab === 'rm'" x-cloak
                class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6 space-y-6 transition-all">
                <!-- Header & Add Button -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">RM Inventory</h2>
                        <p class="text-sm text-slate-500">Manage raw materials received from vendors.</p>
                    </div>
                    <div>
                        <button @click="openModal('rm', 'create')"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-lg shadow-indigo-200 transition-all active:scale-95">
                            ＋ Add RM Stock
                        </button>
                    </div>
                </div>

                <!-- Filters & Import/Export -->
                <div
                    class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <form method="GET" class="flex flex-wrap items-end gap-3 w-full lg:w-auto">
                        <input type="hidden" name="tab" value="rm">
                        <div class="w-full sm:w-48">
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Part</label>
                            <select name="rm_part_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Parts</option>
                                @foreach ($rmParts as $p)
                                    <option value="{{ $p->id }}" @selected((string) $rmPartId === (string) $p->id)>
                                        {{ $p->part_no }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full sm:w-64">
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Search</label>
                            <input type="text" name="rm_q" value="{{ $rmQ ?? '' }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Part no / name...">
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-lg bg-slate-800 text-white font-bold text-sm hover:bg-slate-900 transition-all">Filter</button>
                            @if(!empty($rmPartId) || !empty($rmQ))
                                <a href="{{ route('inventory.index', ['tab' => 'rm']) }}"
                                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm text-center transition-all">Reset</a>
                            @endif
                        </div>
                    </form>

                    <div
                        class="flex items-center gap-2 w-full lg:w-auto pt-4 lg:pt-0 border-t lg:border-t-0 border-slate-200">
                        <a href="{{ route('inventory.export') }}"
                            class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 transition-all flex items-center gap-2">
                            ↓ Export
                        </a>
                        <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data"
                            class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="file"
                                class="w-48 rounded-lg border-slate-300 text-xs bg-white focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            <button type="submit"
                                class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm transition-all flex items-center gap-2">
                                ↑ Import
                            </button>
                        </form>
                    </div>
                </div>

                <!-- RM Table -->
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-100">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">
                                    Part RM</th>
                                <th
                                    class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">
                                    On Hand</th>
                                <th
                                    class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">
                                    On Order</th>
                                <th
                                    class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">
                                    As Of</th>
                                <th
                                    class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($inventories as $inv)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">{{ $inv->part->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ $inv->part->part_name_gci ?? '-' }}
                                            @if(!empty($inv->part?->register_no))
                                                <span
                                                    class="ml-2 inline-flex items-center rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">
                                                    {{ $inv->part->register_no }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-1 rounded">{{ number_format((float) $inv->on_hand, 3) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="font-mono text-slate-600">{{ number_format((float) $inv->on_order, 3) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 text-xs font-medium">
                                        {{ $inv->as_of_date?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-3">
                                        <button @click="openModal('rm', 'edit', @js($inv))"
                                            class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-wider">Edit</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-12 text-center border-2 border-dashed border-slate-200 rounded-xl">
                                        <p class="text-slate-500 font-semibold mb-1">No RM inventory found</p>
                                        <p class="text-xs text-slate-400">Try adjusting your filters or add new stock.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                @if($inventories->hasPages())
                    <div class="pt-4 border-t border-slate-100">
                        {{ $inventories->appends(['tab' => 'rm', 'gci_page' => request('gci_page')])->links() }}
                    </div>
                @endif
            </div>

            <!-- ============================== FG/WIP TAB ============================== -->
            <div x-show="activeTab === 'fg'" x-cloak
                class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6 space-y-6 transition-all">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">FG & WIP Inventory</h2>
                        <p class="text-sm text-slate-500">View internal part stock levels (Finished Goods & Works in
                            Progress).</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="tab" value="fg">
                        <div class="w-full sm:w-56">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Search
                                Part</label>
                            <input type="text" name="gci_search" value="{{ $gciSearch }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Number / Name / Model...">
                        </div>
                        <div class="w-full sm:w-32">
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Class</label>
                            <select name="gci_class"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="FG" @selected($gciClass === 'FG')>FG</option>
                                <option value="WIP" @selected($gciClass === 'WIP')>WIP</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-32">
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                            <select name="gci_status"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="active" @selected($gciStatus === 'active')>Active</option>
                                <option value="inactive" @selected($gciStatus === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-lg bg-slate-800 text-white font-bold text-sm hover:bg-slate-900 transition-all">Filter</button>
                            @if($gciSearch !== '' || $gciClass !== '' || $gciStatus !== '')
                                <a href="{{ route('inventory.index', ['tab' => 'fg']) }}"
                                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm text-center transition-all">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- FG/WIP Table -->
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-100">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">
                                    Part No</th>
                                <th
                                    class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">
                                    Name / Model</th>
                                <th
                                    class="px-4 py-3 text-center font-black text-slate-600 uppercase tracking-wider text-xs">
                                    Class</th>
                                <th
                                    class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">
                                    On Hand</th>
                                <th
                                    class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">
                                    On Order</th>
                                <th
                                    class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">
                                    As Of</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($gciRows as $inv)
                            @php($p = $inv->part)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-bold font-mono text-indigo-700">{{ $p?->part_no ?? '-' }}</div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">
                                        {{ $p?->customer?->name ?? 'No Customer' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $p?->part_name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $p?->model ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center px-2 py-1 rounded text-xs font-black tracking-wider',
                                        'bg-blue-100 text-blue-700' => $p?->classification === 'FG',
                                        'bg-amber-100 text-amber-700' => $p?->classification === 'WIP',
                                        'bg-slate-100 text-slate-700' => !in_array($p?->classification, ['FG', 'WIP'])
                                    ])>
                                        {{ strtoupper((string) ($p?->classification ?? '-')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span
                                        class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-1 rounded shadow-sm border border-slate-200">
                                        {{ formatNumber((float) ($inv->on_hand ?? 0)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span
                                        class="font-mono text-slate-500">{{ formatNumber((float) ($inv->on_order ?? 0)) }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs font-medium">
                                    {{ $inv->as_of_date ? \Carbon\Carbon::parse($inv->as_of_date)->format('d M Y') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6"
                                    class="px-4 py-12 text-center border-2 border-dashed border-slate-200 rounded-xl">
                                    <p class="text-slate-500 font-semibold mb-1">No FG/WIP inventory found</p>
                                    <p class="text-xs text-slate-400">Try adjusting your filters.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                @if($gciRows->hasPages())
                    <div class="pt-4 border-t border-slate-100">
                        {{ $gciRows->appends(['tab' => 'fg', 'rm_page' => request('rm_page')])->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- RM Edit/Create Modal (Only needed for RM in this context based on original code) -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
            x-show="modalOpen" x-cloak @keydown.escape.window="closeModal()">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transform transition-all"
                @click.outside="closeModal()">
                <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <span x-text="mode === 'create' ? '＋ Add' : '✎ Edit'"></span>
                        <span x-text="targetType === 'rm' ? 'RM Stock' : 'Stock'"></span>
                    </h3>
                    <button type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-colors"
                        @click="closeModal()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="p-6 space-y-5">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <template x-if="mode === 'create' && targetType === 'rm'">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Part RM</label>
                            <select name="part_id"
                                class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required x-model="form.part_id">
                                <option value="" disabled>Select Part...</option>
                                @foreach ($rmParts as $p)
                                    <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name_gci }}</option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">On Hand <span
                                    class="text-red-500">*</span></label>
                            <input type="number" step="0.001" min="0" name="on_hand"
                                class="w-full rounded-xl border-slate-300 font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                required x-model="form.on_hand">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">On Order <span
                                    class="text-red-500">*</span></label>
                            <input type="number" step="0.001" min="0" name="on_order"
                                class="w-full rounded-xl border-slate-300 font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                required x-model="form.on_order">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">As Of Date</label>
                        <input type="date" name="as_of_date"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                            x-model="form.as_of_date">
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button"
                            class="px-5 py-2.5 rounded-xl border-2 border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all"
                            @click="closeModal()">Cancel</button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black shadow-lg shadow-indigo-200 transition-all active:scale-95">Save
                            Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function inventoryPage(initialTab) {
            return {
                activeTab: initialTab || 'rm',
                modalOpen: false,
                mode: 'create', // create or edit
                targetType: 'rm', // rm (FG edit not supported natively in this view originally)
                formAction: '',
                form: { id: null, part_id: '', on_hand: '0', on_order: '0', as_of_date: '' },

                switchTab(tab) {
                    this.activeTab = tab;
                    // Update URL without refresh
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tab);
                    window.history.pushState({}, '', url);
                },

                openModal(type, mode, data = null) {
                    this.targetType = type;
                    this.mode = mode;

                    if (mode === 'create') {
                        this.formAction = type === 'rm' ? '{{ route('inventory.store') }}' : ''; // Adjust if FG creation is needed
                        this.form = { id: null, part_id: '', on_hand: '0', on_order: '0', as_of_date: '{{ date('Y-m-d') }}' };
                    } else if (mode === 'edit' && data) {
                        this.formAction = '{{ url('/inventory') }}/' + data.id; // Adjust URL logic if FG edit is added
                        this.form = {
                            id: data.id,
                            on_hand: data.on_hand,
                            on_order: data.on_order,
                            as_of_date: data.as_of_date ? data.as_of_date.split('T')[0] : '', // Handle iso string or simple date
                        };
                    }
                    this.modalOpen = true;
                },

                closeModal() {
                    this.modalOpen = false;
                }
            }
        }
    </script>
</x-app-layout>