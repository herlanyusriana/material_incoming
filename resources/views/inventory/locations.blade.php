<x-app-layout>
    <x-slot name="header">
        Warehouse Locations
    </x-slot>

    <div class="py-6" x-data="warehouseLocationsPage()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Warehouse Map -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6" x-data="{ expanded: false }">
                <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Warehouse Map (Denah)
                    </h3>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('inventory.locations.print-map') }}" target="_blank"
                            class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 rounded-lg text-slate-600 hover:text-indigo-600 hover:border-indigo-600 transition-colors flex items-center gap-1"
                            @click.stop>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print A4
                        </a>
                        <button class="text-slate-500 hover:text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-200"
                                :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mt-4" x-show="expanded" x-cloak x-transition>
                    <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 flex justify-center p-4">
                        <img src="{{ asset('assets/denah_warehouse.jpeg') }}" alt="Denah Warehouse"
                            class="max-w-full h-auto object-contain rounded-lg shadow-sm">
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl flex flex-col">

                <!-- Toolbar Section -->
                <div class="p-5 border-b border-slate-100 space-y-4">
                    <!-- Top Row: Filters & Primary Action -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                        <!-- Search & Filters -->
                        <form method="GET" class="flex flex-wrap items-end gap-3 w-full lg:w-auto">
                            <div class="w-full sm:w-64">
                                <label
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Search</label>
                                <div class="relative">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input name="search" value="{{ $search }}"
                                        class="w-full pl-9 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Location code / QR...">
                                </div>
                            </div>

                            <div class="w-24">
                                <label
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Class</label>
                                <input name="class" value="{{ $class }}"
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 text-center uppercase"
                                    placeholder="A">
                            </div>

                            <div class="w-24">
                                <label
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Zone</label>
                                <input name="zone" value="{{ $zone }}"
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 text-center uppercase"
                                    placeholder="Z1">
                            </div>

                            <div class="w-32">
                                <label
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Status</label>
                                <select name="status"
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Status</option>
                                    <option value="ACTIVE" @selected(strtoupper($status) === 'ACTIVE')>Active</option>
                                    <option value="INACTIVE" @selected(strtoupper($status) === 'INACTIVE')>Inactive
                                    </option>
                                </select>
                            </div>

                            <button
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm shadow-sm transition-colors">
                                Filter
                            </button>

                            @if(request()->anyFilled(['search', 'class', 'zone', 'status']))
                                <a href="{{ route('inventory.locations.index') }}"
                                    class="px-3 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Clear</a>
                            @endif
                        </form>

                        <!-- Add Button -->
                        <button
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-md shadow-indigo-100 transition-all flex items-center gap-2 whitespace-nowrap"
                            @click="openCreate()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Add Location
                        </button>
                    </div>

                    <!-- Bottom Row: Tools & Bulk Actions -->
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2 border-t border-slate-50">
                        <!-- Bulk Print Tool (Collapsible-ish concept) -->
                        <form action="{{ route('inventory.locations.print-range') }}" method="GET" target="_blank"
                            class="flex flex-wrap items-center gap-2 bg-slate-50 rounded-lg p-1.5 border border-slate-200">
                            <span class="text-xs font-bold text-slate-500 px-2 uppercase">Print Range:</span>
                            <input name="start" value="{{ request('start') }}"
                                class="w-24 px-2 py-1 text-sm rounded-md border-slate-200 placeholder-slate-400 uppercase focus:border-indigo-500 focus:ring-0"
                                placeholder="Start">
                            <span class="text-slate-400 text-xs">to</span>
                            <input name="end" value="{{ request('end') }}"
                                class="w-24 px-2 py-1 text-sm rounded-md border-slate-200 placeholder-slate-400 uppercase focus:border-indigo-500 focus:ring-0"
                                placeholder="End">
                            <input type="number" name="limit" min="1" max="50" value="{{ request('limit', 20) }}"
                                class="w-16 px-2 py-1 text-sm rounded-md border-slate-200 placeholder-slate-400 focus:border-indigo-500 focus:ring-0"
                                title="Limit">
                            <button
                                class="px-3 py-1 bg-white border border-slate-200 rounded-md text-slate-600 text-xs font-bold hover:text-indigo-600 hover:border-indigo-300 transition-colors uppercase tracking-wide">
                                Print
                            </button>
                        </form>

                        <!-- Data Tools -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('inventory.locations.export') }}"
                                class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 hover:border-slate-300 rounded-lg transition-colors flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                            </a>
                            <form action="{{ route('inventory.locations.import') }}" method="POST"
                                enctype="multipart/form-data" class="flex items-center">
                                @csrf
                                <label
                                    class="cursor-pointer px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 hover:border-slate-300 rounded-lg transition-colors flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>Import</span>
                                    <input type="file" name="file" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Location</th>
                                <th class="px-6 py-4">Class</th>
                                <th class="px-6 py-4">Zone</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($locations as $loc)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-slate-800">{{ $loc->location_code }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-600">
                                        @if(strtoupper($loc->class) === 'TROLLY')
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-bold border border-indigo-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                TROLLY
                                            </span>
                                        @else
                                            {{ $loc->class ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-600">{{ $loc->zone ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold tracking-wide {{ strtoupper($loc->status) === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ strtoupper($loc->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('inventory.locations.print', $loc) }}" target="_blank"
                                                class="p-1 text-slate-400 hover:text-indigo-600 transition-colors"
                                                title="Print QR">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v1m6 11h2m-6 0h-2v4h-4v-4H8m1-5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                                                </svg>
                                            </a>
                                            <button type="button"
                                                class="p-1 text-slate-400 hover:text-amber-600 transition-colors"
                                                @click="openEdit(@js($loc))" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form action="{{ route('inventory.locations.destroy', $loc) }}" method="POST"
                                                class="inline" onsubmit="return confirm('Delete location?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1 text-slate-400 hover:text-red-600 transition-colors"
                                                    title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <span class="font-medium">No locations found</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $locations->links() }}
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
            x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900"
                        x-text="mode === 'create' ? 'Add Location' : 'Edit Location'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                        @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Lokasi (Location Code)</label>
                        <input type="text" name="location_code"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="RACK-A1" required
                            x-model="form.location_code">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Class</label>
                            <input type="text" name="class" class="mt-1 w-full rounded-xl border-slate-200 uppercase"
                                placeholder="A or TROLLY" x-model="form.class">
                            <p class="text-[10px] text-slate-400 mt-1 italic">Type 'TROLLY' for mobile storage.</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Zone</label>
                            <input type="text" name="zone" class="mt-1 w-full rounded-xl border-slate-200 uppercase"
                                placeholder="Z1" x-model="form.zone">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.status">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="close()">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function warehouseLocationsPage() {
                return {
                    modalOpen: false,
                    mode: 'create',
                    formAction: @js(route('inventory.locations.store')),
                    form: { id: null, location_code: '', class: '', zone: '', status: 'ACTIVE' },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('inventory.locations.store'));
                        this.form = { id: null, location_code: '', class: '', zone: '', status: 'ACTIVE' };
                        this.modalOpen = true;
                    },
                    openEdit(loc) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/inventory/locations')) + '/' + loc.id;
                        this.form = {
                            id: loc.id,
                            location_code: loc.location_code ?? '',
                            class: loc.class ?? '',
                            zone: loc.zone ?? '',
                            status: (loc.status ?? 'ACTIVE'),
                        };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>