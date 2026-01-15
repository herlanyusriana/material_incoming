<x-app-layout>
    <x-slot name="header">
        Planning • 
        @if($classification === 'FG')
            FG Part
        @elseif($classification === 'WIP')
            WIP Part
        @elseif($classification === 'RM')
            RM Part
        @else
            Part GCI
        @endif
    </x-slot>

    <div class="py-3" x-data="planningGciParts()">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-4 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        @if(!$classification)
                            <!-- Only show classification filter if viewing all parts -->
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Classification</label>
                                <select name="classification" class="mt-1 rounded-xl border-slate-200">
                                    <option value="">All</option>
                                    <option value="FG" @selected($classification === 'FG')>FG</option>
                                    <option value="WIP" @selected($classification === 'WIP')>WIP</option>
                                    <option value="RM" @selected($classification === 'RM')>RM</option>
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="active" @selected($status === 'active')>Active</option>
                                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('planning.gci-parts.export') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Export</a>
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold" @click="openImport()">Import</button>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">Add Part GCI</button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">Type</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($parts as $p)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        @if($p->customer)
                                            <span class="font-bold text-indigo-700">{{ $p->customer->code }}</span>
                                            <div class="text-[10px] text-slate-500 uppercase">{{ $p->customer->name }}</div>
                                        @else
                                            <span class="text-slate-400 italic">No Customer</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ $p->part_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $p->part_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $p->model ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $classColors = [
                                                'FG' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                'WIP' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'RM' => 'bg-green-100 text-green-800 border-green-200',
                                            ];
                                            $color = $classColors[$p->classification] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold border {{ $color }}">
                                            {{ $p->classification ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($p->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($p))">Edit</button>
                                        <form action="{{ route('planning.gci-parts.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Delete Part GCI?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">No Part GCI</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $parts->links() }}
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Part GCI' : 'Edit Part GCI'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer (Optional)</label>
                        <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.customer_id">
                            <option value="">-- No Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Part No</label>
                        <input name="part_no" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.part_no">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Part Name</label>
                        <input name="part_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.part_name">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Model</label>
                        <input name="model" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.model">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Classification <span class="text-red-600">*</span></label>
                        <select name="classification" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.classification">
                            <option value="FG">FG (Finished Goods)</option>
                            <option value="WIP">WIP (Work in Progress)</option>
                            <option value="RM">RM (Raw Materials)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
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
            function planningGciParts() {
                return {
                    modalOpen: false,
                    importOpen: false,
                    mode: 'create',
                    formAction: @js(route('planning.gci-parts.store')),
                    form: { id: null, customer_id: '', part_no: '', classification: 'FG', part_name: '', model: '', status: 'active' },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('planning.gci-parts.store'));
                        // Pre-fill classification based on current view
                        const currentClassification = @js($classification ?? 'FG');
                        this.form = { id: null, customer_id: '', part_no: '', classification: currentClassification, part_name: '', model: '', status: 'active' };
                        this.modalOpen = true;
                    },
                    openEdit(p) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/planning/gci-parts')) + '/' + p.id;
                        this.form = { id: p.id, customer_id: p.customer_id || '', part_no: p.part_no, classification: p.classification || 'FG', part_name: p.part_name, model: p.model, status: p.status };
                        this.modalOpen = true;
                    },
                    openImport() {
                        this.importOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="importOpen" x-cloak @keydown.escape.window="importOpen=false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Import Part GCI</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="importOpen=false">✕</button>
                </div>
                <form action="{{ route('planning.gci-parts.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Excel File</label>
                        <input type="file" name="file" accept=".xlsx,.xls" required class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div class="mt-2 text-xs text-slate-500">
                            Kolom: <span class="font-semibold text-indigo-700">customer (NAMA)</span>, part_no, classification, part_name, model, status
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="importOpen=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
