<x-app-layout>
    <x-slot name="header">
        Trolly Management
    </x-slot>

    <div class="py-6" x-data="trollyPage()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div
                    class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-800 shadow-sm flex items-center gap-3">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Header Actions -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by code..."
                            class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-slate-800 text-white rounded-xl text-sm font-semibold hover:bg-slate-900 transition-all">
                        Filter
                    </button>
                    @if($search || $type || $kind)
                        <a href="{{ route('warehouse.trollies.index') }}"
                            class="text-sm text-slate-500 hover:text-slate-700 font-medium">Clear</a>
                    @endif
                </form>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('warehouse.trollies.export') }}"
                        class="flex-1 sm:flex-none px-3 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    <button @click="openRange()"
                        class="flex-1 sm:flex-none px-3 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4h-4v-4H8m1-5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                        </svg>
                        Print Range
                    </button>
                    <form action="{{ route('warehouse.trollies.import') }}" method="POST" enctype="multipart/form-data"
                        class="flex-1 sm:flex-none">
                        @csrf
                        <label
                            class="cursor-pointer px-3 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Import
                            <input type="file" name="file" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                    <button @click="openCreate()"
                        class="flex-1 sm:flex-none px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-100 flex items-center justify-center gap-2 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Add
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead
                        class="bg-slate-50 text-slate-500 font-bold uppercase text-[11px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Trolly Code</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Kind (Category)</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($trollies as $trolly)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <span class="font-mono font-bold text-slate-900">{{ $trolly->code }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                                        {{ $trolly->type ?: 'GENERAL' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-600 font-medium italic">
                                        {{ $trolly->kind ?: '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $trolly->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $trolly->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('warehouse.trollies.print', $trolly) }}" target="_blank"
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                                            title="Print QR">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v1m6 11h2m-6 0h-2v4h-4v-4H8m1-5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                                            </svg>
                                        </a>
                                        <button @click="openEdit(@js($trolly))"
                                            class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('warehouse.trollies.destroy', $trolly) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Delete this trolly?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No trollies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-slate-50 bg-slate-50/30">
                    {{ $trollies->links() }}
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
            x-show="modalOpen" x-cloak x-transition>
            <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-lg font-bold text-slate-900"
                        x-text="mode === 'create' ? 'Add New Trolly' : 'Edit Trolly'"></h3>
                    <button @click="close()"
                        class="p-2 text-slate-400 hover:text-slate-600 transition-colors">✕</button>
                </div>

                <form :action="formAction" method="POST" class="p-6 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Trolly
                            Code</label>
                        <input type="text" name="code" required x-model="form.code"
                            class="w-full rounded-xl border-slate-200 uppercase placeholder:text-slate-300 font-bold focus:ring-indigo-500"
                            placeholder="e.g. TRL-001">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Type</label>
                            <input type="text" name="type" x-model="form.type"
                                class="w-full rounded-xl border-slate-200 uppercase placeholder:text-slate-300 focus:ring-indigo-500"
                                placeholder="External">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kind
                                (Cat)</label>
                            <input type="text" name="kind" x-model="form.kind"
                                class="w-full rounded-xl border-slate-200 uppercase placeholder:text-slate-300 focus:ring-indigo-500"
                                placeholder="Backplate">
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                        <select name="status" x-model="form.status"
                            class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 font-semibold">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                            <option value="MAINTENANCE">MAINTENANCE</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="close()"
                            class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                            Save Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Range Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
            x-show="rangeModalOpen" x-cloak x-transition>
            <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-lg font-bold text-slate-900">Print Range Labels</h3>
                    <button @click="rangeModalOpen = false"
                        class="p-2 text-slate-400 hover:text-slate-600 transition-colors">✕</button>
                </div>

                <form action="{{ route('warehouse.trollies.print-range') }}" method="GET" target="_blank"
                    class="p-6 space-y-4" @submit="rangeModalOpen = false">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Start
                                Code</label>
                            <input type="text" name="start"
                                class="w-full rounded-xl border-slate-200 uppercase placeholder:text-slate-300 font-bold focus:ring-indigo-500"
                                placeholder="TRL-001">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">End
                                Code</label>
                            <input type="text" name="end"
                                class="w-full rounded-xl border-slate-200 uppercase placeholder:text-slate-300 font-bold focus:ring-indigo-500"
                                placeholder="TRL-010">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Limit
                            (Max 100)</label>
                        <input type="number" name="limit" value="50" min="1" max="100"
                            class="w-full rounded-xl border-slate-200 font-bold focus:ring-indigo-500">
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="rangeModalOpen = false"
                            class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-slate-800 text-white rounded-xl font-bold hover:bg-slate-900 shadow-lg transition-all flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print Range
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function trollyPage() {
            return {
                modalOpen: false,
                rangeModalOpen: false,
                mode: 'create',
                formAction: @js(route('warehouse.trollies.store')),
                form: { id: null, code: '', type: '', kind: '', status: 'ACTIVE' },
                openCreate() {
                    this.mode = 'create';
                    this.formAction = @js(route('warehouse.trollies.store'));
                    this.form = { id: null, code: '', type: '', kind: '', status: 'ACTIVE' };
                    this.modalOpen = true;
                },
                openEdit(t) {
                    this.mode = 'edit';
                    this.formAction = @js(url('/warehouse/trollies')) + '/' + t.id;
                    this.form = {
                        id: t.id,
                        code: t.code ?? '',
                        type: t.type ?? '',
                        kind: t.kind ?? '',
                        status: t.status ?? 'ACTIVE',
                    };
                    this.modalOpen = true;
                },
                openRange() {
                    this.rangeModalOpen = true;
                },
                close() { this.modalOpen = false; }
            }
        }
    </script>
</x-app-layout>