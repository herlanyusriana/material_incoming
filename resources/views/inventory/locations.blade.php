<x-app-layout>
    <x-slot name="header">
        Warehouse Locations
    </x-slot>

    <div class="py-6" x-data="warehouseLocationsPage()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Search</label>
                            <input name="search" value="{{ $search }}" class="mt-1 rounded-xl border-slate-200" placeholder="RACK-A1 / payload...">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Class</label>
                            <input name="class" value="{{ $class }}" class="mt-1 w-24 rounded-xl border-slate-200" placeholder="A">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Zone</label>
                            <input name="zone" value="{{ $zone }}" class="mt-1 w-28 rounded-xl border-slate-200" placeholder="Z1">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="ACTIVE" @selected(strtoupper($status) === 'ACTIVE')>ACTIVE</option>
                                <option value="INACTIVE" @selected(strtoupper($status) === 'INACTIVE')>INACTIVE</option>
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('inventory.locations.export') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Export</a>
                        <form action="{{ route('inventory.locations.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="file" class="rounded-xl border-slate-200 text-sm" required>
                            <button class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">Import</button>
                        </form>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                            Add Location
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Lokasi</th>
                                <th class="px-4 py-3 text-left font-semibold">Class</th>
                                <th class="px-4 py-3 text-left font-semibold">Zone</th>
                                <th class="px-4 py-3 text-left font-semibold">QR Payload</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($locations as $loc)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono font-semibold">{{ $loc->location_code }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $loc->class ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $loc->zone ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="max-w-[520px] truncate font-mono text-[11px] text-slate-600" title="{{ $loc->qr_payload }}">
                                            {{ $loc->qr_payload }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ strtoupper((string) $loc->status) === 'ACTIVE' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-slate-50 text-slate-700 border border-slate-200' }}">
                                            {{ strtoupper((string) $loc->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="text-slate-700 hover:text-slate-900 font-semibold" href="{{ route('inventory.locations.print', $loc) }}" target="_blank">QR</a>
                                        <button type="button" class="ml-3 text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($loc))">Edit</button>
                                        <form action="{{ route('inventory.locations.destroy', $loc) }}" method="POST" class="inline" onsubmit="return confirm('Delete location?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">No locations</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $locations->links() }}
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Location' : 'Edit Location'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Lokasi (Location Code)</label>
                        <input type="text" name="location_code" class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="RACK-A1" required x-model="form.location_code">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Class</label>
                            <input type="text" name="class" class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="A" x-model="form.class">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Zone</label>
                            <input type="text" name="zone" class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="Z1" x-model="form.zone">
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
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
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

