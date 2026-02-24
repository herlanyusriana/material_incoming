<x-app-layout>
    <x-slot name="header">Parts Master</x-slot>

    <div class="py-3" x-data="partsMaster()">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-4 space-y-4">
                {{-- Filters --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Classification</label>
                            <select name="classification" class="mt-1 rounded-xl border-slate-200 text-sm">
                                <option value="">All</option>
                                <option value="FG" @selected(($classification ?? '') === 'FG')>FG</option>
                                <option value="WIP" @selected(($classification ?? '') === 'WIP')>WIP</option>
                                <option value="RM" @selected(($classification ?? '') === 'RM')>RM</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200 text-sm">
                                <option value="">All</option>
                                <option value="active" @selected(($status ?? '') === 'active')>Active</option>
                                <option value="inactive" @selected(($status ?? '') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                            <input name="q" value="{{ $search ?? '' }}" class="rounded-xl border-slate-200 pl-10 text-sm" placeholder="Search part...">
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Filter</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('parts.export') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold">Export</a>
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold" @click="importOpen=true">Import</button>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold" @click="openCreatePart()">+ Add Part</button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold w-8"></th>
                                <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">Type</th>
                                <th class="px-4 py-3 text-center font-semibold">Vendors</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($parts as $p)
                                {{-- GCI Part row --}}
                                <tr class="hover:bg-slate-50 cursor-pointer" @click="toggle({{ $p->id }})">
                                    <td class="px-4 py-3">
                                        <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="expanded[{{ $p->id }}] && 'rotate-90'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($p->customer)
                                            <span class="font-bold text-indigo-700">{{ $p->customer->code }}</span>
                                            <div class="text-[10px] text-slate-500">{{ $p->customer->name }}</div>
                                        @else
                                            <span class="text-slate-400 italic text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $p->part_no }}</td>
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
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border {{ $color }}">{{ $p->classification }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ $p->vendorLinks->count() > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500' }}">{{ $p->vendorLinks->count() }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($p->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right" @click.stop>
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold" @click="openEditPart(@js($p))">Edit</button>
                                        <button type="button" class="ml-2 text-emerald-600 hover:text-emerald-800 text-xs font-semibold" @click="openCreateVendorPart({{ $p->id }})">+ Vendor</button>
                                        <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Delete this part?')">
                                            @csrf @method('DELETE')
                                            <button class="ml-2 text-red-600 hover:text-red-800 text-xs font-semibold">Del</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Vendor parts (expandable) --}}
                                @if($p->vendorLinks->count() > 0)
                                    <template x-if="expanded[{{ $p->id }}]">
                                        <tr>
                                            <td colspan="9" class="px-0 py-0">
                                                <div class="bg-gradient-to-r from-indigo-50/50 to-slate-50 border-l-4 border-indigo-300 mx-4 my-2 rounded-lg overflow-hidden">
                                                    <table class="min-w-full text-xs divide-y divide-indigo-100">
                                                        <thead class="bg-indigo-50/80">
                                                            <tr class="text-indigo-600 uppercase tracking-wider">
                                                                <th class="px-4 py-2 text-left font-semibold">Vendor</th>
                                                                <th class="px-4 py-2 text-left font-semibold">Vendor Part No</th>
                                                                <th class="px-4 py-2 text-left font-semibold">Vendor Part Name</th>
                                                                <th class="px-4 py-2 text-left font-semibold">Register No</th>
                                                                <th class="px-4 py-2 text-right font-semibold">Price</th>
                                                                <th class="px-4 py-2 text-left font-semibold">UOM</th>
                                                                <th class="px-4 py-2 text-left font-semibold">HS Code</th>
                                                                <th class="px-4 py-2 text-left font-semibold">QI</th>
                                                                <th class="px-4 py-2 text-left font-semibold">Status</th>
                                                                <th class="px-4 py-2 text-right font-semibold">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-indigo-50 bg-white/60">
                                                            @foreach($p->vendorLinks as $vl)
                                                                <tr class="hover:bg-indigo-50/40">
                                                                    <td class="px-4 py-2 font-semibold text-slate-800">{{ $vl->vendor->vendor_name ?? '-' }}</td>
                                                                    <td class="px-4 py-2 text-slate-700">{{ $vl->vendor_part_no ?? '-' }}</td>
                                                                    <td class="px-4 py-2 text-slate-700">{{ $vl->vendor_part_name ?? '-' }}</td>
                                                                    <td class="px-4 py-2 text-slate-600">{{ $vl->register_no ?? '-' }}</td>
                                                                    <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($vl->price, 2) }}</td>
                                                                    <td class="px-4 py-2 text-slate-600">{{ $vl->uom ?? '-' }}</td>
                                                                    <td class="px-4 py-2 text-slate-600">{{ $vl->hs_code ?? '-' }}</td>
                                                                    <td class="px-4 py-2">
                                                                        @if($vl->quality_inspection)
                                                                            <span class="text-emerald-600 font-bold">YES</span>
                                                                        @else
                                                                            <span class="text-slate-400">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-4 py-2">
                                                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $vl->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($vl->status) }}</span>
                                                                    </td>
                                                                    <td class="px-4 py-2 text-right">
                                                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEditVendorPart(@js($vl))">Edit</button>
                                                                        <form action="{{ route('parts.vendor-parts.destroy', $vl) }}" method="POST" class="inline" onsubmit="return confirm('Delete this vendor part?')">
                                                                            @csrf @method('DELETE')
                                                                            <button class="ml-2 text-red-600 hover:text-red-800 font-semibold">Del</button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                @endif
                            @empty
                                <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">No parts found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $parts->links() }}</div>
            </div>
        </div>

        {{-- GCI Part Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="partModal" x-cloak @keydown.escape.window="partModal=false">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="partMode === 'create' ? 'Add Part' : 'Edit Part'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="partModal=false">✕</button>
                </div>
                <form :action="partAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="partMode==='edit'"><input type="hidden" name="_method" value="PUT"></template>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer (Optional)</label>
                        <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.customer_id">
                            <option value="">— No Customer —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Part No <span class="text-red-500">*</span></label>
                            <input name="part_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required x-model="partForm.part_no">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Classification <span class="text-red-500">*</span></label>
                            <select name="classification" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required x-model="partForm.classification">
                                <option value="FG">FG</option>
                                <option value="WIP">WIP</option>
                                <option value="RM">RM</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Part Name</label>
                        <input name="part_name" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.part_name">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Model</label>
                            <input name="model" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.model">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Status</label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="partForm.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="partModal=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Vendor Part Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="vpModal" x-cloak @keydown.escape.window="vpModal=false">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="vpMode === 'create' ? 'Add Vendor Part' : 'Edit Vendor Part'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="vpModal=false">✕</button>
                </div>
                <form :action="vpAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="vpMode==='edit'"><input type="hidden" name="_method" value="PUT"></template>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Vendor <span class="text-red-500">*</span></label>
                        <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required x-model="vpForm.vendor_id">
                            <option value="">Select vendor...</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Vendor Part No</label>
                            <input name="vendor_part_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.vendor_part_no">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Register No</label>
                            <input name="register_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.register_no">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Vendor Part Name</label>
                        <input name="vendor_part_name" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.vendor_part_name">
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Price</label>
                            <input name="price" type="number" step="0.001" min="0" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.price">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">UOM</label>
                            <input name="uom" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="PCS" x-model="vpForm.uom">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">HS Code</label>
                            <input name="hs_code" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.hs_code">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Quality Inspection</label>
                            <select name="quality_inspection" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.quality_inspection">
                                <option value="">No</option>
                                <option value="YES">Yes</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Status</label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" x-model="vpForm.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="vpModal=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Import Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="importOpen" x-cloak @keydown.escape.window="importOpen=false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Import Parts</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="importOpen=false">✕</button>
                </div>
                <form action="{{ route('parts.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Excel File</label>
                        <input type="file" name="file" accept=".xlsx,.xls" required class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm" @click="importOpen=false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function partsMaster() {
                return {
                    expanded: {},
                    importOpen: false,

                    // GCI Part modal
                    partModal: false,
                    partMode: 'create',
                    partAction: @js(route('parts.store')),
                    partForm: { customer_id: '', part_no: '', part_name: '', model: '', classification: 'RM', status: 'active' },

                    // Vendor Part modal
                    vpModal: false,
                    vpMode: 'create',
                    vpAction: '',
                    vpForm: { vendor_id: '', vendor_part_no: '', vendor_part_name: '', register_no: '', price: 0, uom: '', hs_code: '', quality_inspection: '', status: 'active' },

                    toggle(id) { this.expanded[id] = !this.expanded[id]; },

                    openCreatePart() {
                        this.partMode = 'create';
                        this.partAction = @js(route('parts.store'));
                        this.partForm = { customer_id: '', part_no: '', part_name: '', model: '', classification: 'RM', status: 'active' };
                        this.partModal = true;
                    },
                    openEditPart(p) {
                        this.partMode = 'edit';
                        this.partAction = @js(url('/parts')) + '/' + p.id;
                        this.partForm = { customer_id: p.customer_id || '', part_no: p.part_no, part_name: p.part_name || '', model: p.model || '', classification: p.classification, status: p.status };
                        this.partModal = true;
                    },

                    openCreateVendorPart(partId) {
                        this.vpMode = 'create';
                        this.vpAction = @js(url('/parts')) + '/' + partId + '/vendor-parts';
                        this.vpForm = { vendor_id: '', vendor_part_no: '', vendor_part_name: '', register_no: '', price: 0, uom: '', hs_code: '', quality_inspection: '', status: 'active' };
                        this.vpModal = true;
                    },
                    openEditVendorPart(vl) {
                        this.vpMode = 'edit';
                        this.vpAction = @js(url('/vendor-parts')) + '/' + vl.id;
                        this.vpForm = {
                            vendor_id: vl.vendor_id,
                            vendor_part_no: vl.vendor_part_no || '',
                            vendor_part_name: vl.vendor_part_name || '',
                            register_no: vl.register_no || '',
                            price: vl.price || 0,
                            uom: vl.uom || '',
                            hs_code: vl.hs_code || '',
                            quality_inspection: vl.quality_inspection ? 'YES' : '',
                            status: vl.status || 'active',
                        };
                        this.vpModal = true;
                    },
                }
            }
        </script>
    </div>
</x-app-layout>
