<x-app-layout>
    <x-slot name="header">
        Planning • Part GCI
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
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-4 space-y-4">

                {{-- Classification Tabs --}}
                @php
                    $totalAll = array_sum($classCounts ?? []);
                    $tabs = [
                        '' => ['label' => 'Semua', 'count' => $totalAll, 'color' => 'slate'],
                        'RM' => ['label' => 'RM', 'count' => $classCounts['RM'] ?? 0, 'color' => 'green'],
                        'WIP' => ['label' => 'WIP', 'count' => $classCounts['WIP'] ?? 0, 'color' => 'yellow'],
                        'FG' => ['label' => 'FG', 'count' => $classCounts['FG'] ?? 0, 'color' => 'blue'],
                    ];
                    $activeTab = $classification ?? '';
                @endphp
                <div class="flex items-center gap-1 border-b border-slate-200 pb-0">
                    @foreach ($tabs as $tabValue => $tab)
                                    @php
                                        $isActive = $activeTab === $tabValue;
                                        $params = array_filter([
                                            'classification' => $tabValue ?: null,
                                            'q' => $qParam ?: null,
                                            'status' => $status ?: null,
                                        ]);
                                        $tabUrl = route('planning.gci-parts.index', $params);
                                    @endphp
                                    <a href="{{ $tabUrl }}" class="relative px-4 py-2.5 text-sm font-semibold rounded-t-lg transition-colors
                                                {{ $isActive
                        ? 'text-slate-900 bg-white border border-slate-200 border-b-white -mb-px z-10'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">
                                        {{ $tab['label'] }}
                                        <span class="ml-1.5 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold
                                                {{ $isActive
                        ? 'bg-' . $tab['color'] . '-100 text-' . $tab['color'] . '-800'
                        : 'bg-slate-100 text-slate-600' }}">
                                            {{ number_format($tab['count']) }}
                                        </span>
                                    </a>
                    @endforeach
                </div>

                {{-- Toolbar --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        @if($classification)
                            <input type="hidden" name="classification" value="{{ $classification }}">
                        @endif
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">Semua</option>
                                <option value="active" @selected($status === 'active')>Active</option>
                                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input name="q" value="{{ $qParam ?? '' }}" class="rounded-xl border-slate-200 pl-10"
                                placeholder="Cari part no / nama...">
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('planning.gci-parts.export') }}"
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Export</a>
                        <button type="button"
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold"
                            @click="openImport()">Import</button>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"
                            @click="openCreate()">+ Tambah Part</button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Size</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">Tipe</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($parts as $p)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        @if($p->customers->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($p->customers as $cust)
                                                    <div
                                                        class="inline-flex flex-col items-start bg-indigo-50 px-2 py-1 rounded border border-indigo-100">
                                                        <span class="font-bold text-indigo-700 text-xs">{{ $cust->code }}</span>
                                                        <span class="text-[9px] text-slate-500 uppercase">{{ $cust->name }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold font-mono text-slate-900">{{ $p->part_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $p->part_name }}</td>
                                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $p->size ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $p->model ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $classColors = [
                                                'FG' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                'WIP' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'RM' => 'bg-green-100 text-green-800 border-green-200',
                                            ];
                                            $color = $classColors[$p->classification] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold border {{ $color }}">
                                            {{ $p->classification ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($p->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold"
                                            @click="openEdit(@js($p))">Edit</button>
                                        <form action="{{ route('planning.gci-parts.destroy', $p) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Hapus Part GCI ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold"
                                                type="submit">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                        Tidak ada Part GCI{{ $classification ? ' (' . $classification . ')' : '' }}
                                    </td>
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

        {{-- Create/Edit Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
            x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full bg-white rounded-2xl shadow-xl border border-slate-200 transition-all duration-200 max-h-[85vh] overflow-y-auto"
                :class="subsOpen ? 'max-w-3xl' : 'max-w-lg'">
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-slate-200 sticky top-0 bg-white z-10">
                    <div class="text-sm font-semibold text-slate-900"
                        x-text="mode === 'create' ? 'Tambah Part GCI' : 'Edit Part GCI'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                        @click="close()">&#10005;</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4" x-ref="gciPartForm">
                    @csrf
                    <input type="hidden" name="confirm_duplicate" value="0" x-ref="confirmDuplicateInput">
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Part No <span
                                    class="text-red-600">*</span></label>
                            <input name="part_no" class="mt-1 w-full rounded-xl border-slate-200" required
                                x-model="form.part_no">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Classification <span
                                    class="text-red-600">*</span></label>
                            <select name="classification" class="mt-1 w-full rounded-xl border-slate-200" required
                                x-model="form.classification"
                                @change="if (form.classification === 'RM') { form.customer_id = ''; form.model = ''; } else { form.vendor_ids = []; form.destination_fg_ids = []; }">
                                <option value="FG">FG (Finished Goods)</option>
                                <option value="WIP">WIP (Work in Progress)</option>
                                <option value="RM">RM (Raw Materials)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Part Name</label>
                        <input name="part_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.part_name"
                            placeholder="Kosongkan = otomatis pakai Part No">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Size</label>
                            <input name="size" class="mt-1 w-full rounded-xl border-slate-200"
                                placeholder="e.g. 100x50x2mm" x-model="form.size">
                        </div>
                        <div x-show="form.classification !== 'RM'" x-cloak>
                            <label class="text-sm font-semibold text-slate-700">Model</label>
                            <input name="model" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.model">
                        </div>
                    </div>
                    <div x-show="form.classification === 'RM'" x-cloak>
                        <label class="text-sm font-semibold text-slate-700">FG Destination (BOM)</label>
                        <div class="mt-1 border border-slate-200 rounded-xl max-h-48 overflow-y-auto">
                            <div class="px-3 py-2 sticky top-0 bg-white border-b border-slate-100">
                                <input type="text" x-model="fgSearch" placeholder="Cari FG part..."
                                    class="w-full text-sm rounded-lg border-slate-200 px-2 py-1">
                            </div>
                            @foreach($fgPartsWithBom as $fg)
                                <label class="flex items-center px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm"
                                    x-show="!fgSearch || '{{ strtolower($fg->part_no . ' ' . $fg->part_name) }}'.includes(fgSearch.toLowerCase())">
                                    <input type="checkbox" name="destination_fg_ids[]" value="{{ $fg->id }}"
                                        class="rounded border-slate-300 text-indigo-600 mr-2"
                                        :checked="form.destination_fg_ids.includes({{ $fg->id }})">
                                    <span class="font-semibold text-indigo-700">{{ $fg->part_no }}</span>
                                    <span class="ml-2 text-slate-500 truncate">{{ $fg->part_name }}</span>
                                </label>
                            @endforeach
                            @if($fgPartsWithBom->isEmpty())
                                <div class="px-3 py-4 text-center text-sm text-slate-400">Tidak ada FG part dengan BOM</div>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">RM ini akan otomatis ditambahkan ke BOM FG yang dipilih.
                        </p>
                    </div>
                    <div x-show="form.classification === 'RM'" x-cloak>
                        <label class="text-sm font-semibold text-slate-700">Assign Vendor</label>
                        <div class="mt-1 border border-slate-200 rounded-xl max-h-48 overflow-y-auto">
                            <div class="px-3 py-2 sticky top-0 bg-white border-b border-slate-100">
                                <input type="text" x-model="vendorSearch" placeholder="Cari vendor..."
                                    class="w-full text-sm rounded-lg border-slate-200 px-2 py-1">
                            </div>
                            @foreach($vendors as $v)
                                <label class="flex items-center px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm"
                                    x-show="!vendorSearch || '{{ strtolower($v->code . ' ' . $v->name) }}'.includes(vendorSearch.toLowerCase())">
                                    <input type="checkbox" name="vendor_ids[]" value="{{ $v->id }}"
                                        class="rounded border-slate-300 text-indigo-600 mr-2"
                                        :checked="form.vendor_ids.includes({{ $v->id }})">
                                    <span class="font-semibold text-indigo-700">{{ $v->code }}</span>
                                    <span class="ml-2 text-slate-500 truncate">{{ $v->name }}</span>
                                </label>
                            @endforeach
                            @if($vendors->isEmpty())
                                <div class="px-3 py-4 text-center text-sm text-slate-400">Tidak ada vendor aktif</div>
                            @endif
                        </div>
                    </div>
                    {{-- Substitutes CRUD (Edit mode, RM only) --}}
                    <div x-show="mode === 'edit' && form.classification === 'RM'" x-cloak>
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <button type="button"
                                class="w-full flex items-center justify-between px-3 py-2 bg-orange-50 hover:bg-orange-100 text-sm font-semibold text-orange-800 transition-colors"
                                @click="subsOpen = !subsOpen">
                                <span>Substitutes</span>
                                <span class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-orange-200 text-orange-900 text-[10px] font-bold"
                                        x-text="(form.substitutes_for || []).length"></span>
                                    <svg class="w-4 h-4 transition-transform" :class="subsOpen && 'rotate-180'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>

                            <div x-show="subsOpen" x-cloak class="px-3 py-3 space-y-3 text-xs">
                                {{-- Existing substitutes table --}}
                                <template x-if="(form.substitutes_for || []).length > 0">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs divide-y divide-slate-100">
                                            <thead>
                                                <tr class="text-slate-500">
                                                    <th class="text-left py-1 pr-2">FG</th>
                                                    <th class="text-left py-1 pr-2">Substitute</th>
                                                    <th class="text-right py-1 pr-2">Ratio</th>
                                                    <th class="text-right py-1 pr-2">Prio</th>
                                                    <th class="text-left py-1 pr-2">Status</th>
                                                    <th class="text-right py-1">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="s in form.substitutes_for" :key="s.id">
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="py-1.5 pr-2 font-mono text-slate-600"
                                                            x-text="s.fg_part_no"></td>
                                                        <td class="py-1.5 pr-2">
                                                            <span class="font-mono font-semibold text-indigo-700"
                                                                x-text="s.substitute_part_no"></span>
                                                            <span class="text-slate-400 ml-1"
                                                                x-text="s.substitute_part_name"></span>
                                                        </td>
                                                        <td class="py-1.5 pr-2 text-right font-mono" x-text="s.ratio">
                                                        </td>
                                                        <td class="py-1.5 pr-2 text-right">
                                                            <span
                                                                class="inline-flex items-center justify-center min-w-[18px] h-4 px-1 rounded-full bg-slate-100 text-slate-700 text-[9px] font-bold"
                                                                x-text="'#' + s.priority"></span>
                                                        </td>
                                                        <td class="py-1.5 pr-2">
                                                            <span
                                                                class="inline-flex px-1.5 py-0.5 rounded-full text-[9px] font-bold"
                                                                :class="s.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                                                x-text="(s.status || '').toUpperCase()"></span>
                                                        </td>
                                                        <td class="py-1.5 text-right whitespace-nowrap">
                                                            <button type="button"
                                                                class="text-indigo-600 hover:text-indigo-800 font-semibold"
                                                                @click="editSub(s)">Edit</button>
                                                            <button type="button"
                                                                class="ml-2 text-red-600 hover:text-red-800 font-semibold"
                                                                @click="deleteSub(s)">Hapus</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                                <template x-if="(form.substitutes_for || []).length === 0">
                                    <div class="text-slate-400 italic py-1">Belum ada substitute</div>
                                </template>

                                {{-- "Dipakai sebagai substitute" info --}}
                                <template x-if="(form.as_substitute || []).length > 0">
                                    <div class="border-t border-slate-100 pt-2">
                                        <div
                                            class="font-semibold text-slate-500 uppercase tracking-wider mb-1 text-[10px]">
                                            Dipakai sebagai substitute untuk</div>
                                        <template x-for="s in form.as_substitute" :key="s.id">
                                            <div class="text-slate-500 py-0.5">
                                                <span class="font-mono" x-text="s.fg_part_no"></span> &rarr;
                                                <span class="font-mono font-semibold text-indigo-700"
                                                    x-text="s.original_rm_part_no"></span>
                                                <span class="text-slate-400 ml-1"
                                                    x-text="'(ratio: ' + s.ratio + ', #' + s.priority + ')'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                            </div>
                        </div>
                    </div>

                    <div x-show="form.classification === 'FG' || form.classification === 'WIP'" x-cloak>
                        <label class="text-sm font-semibold text-slate-700">Assign Customers</label>
                        <div class="mt-1 border border-slate-200 rounded-xl max-h-48 overflow-y-auto">
                            @foreach($customers as $c)
                                <label class="flex items-center px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm">
                                    <input type="checkbox" name="customer_ids[]" value="{{ $c->id }}"
                                        class="rounded border-slate-300 text-indigo-600 mr-2"
                                        :checked="form.customer_ids.includes({{ $c->id }})"
                                        @change="toggleCustomer({{ $c->id }})">
                                    <span class="font-semibold text-indigo-700">{{ $c->code }}</span>
                                    <span class="ml-2 text-slate-500 truncate">{{ $c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" required
                            x-model="form.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="close()">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Simpan</button>
                    </div>
                </form>

                {{-- Substitute Add/Edit Form (separate form, outside main part form) --}}
                <div x-show="mode === 'edit' && form.classification === 'RM' && subsOpen" x-cloak class="px-5 pb-4">
                    <form :action="subFormAction" method="POST"
                        class="space-y-3 border border-orange-200 rounded-xl p-3 bg-orange-50/50">
                        @csrf
                        <template x-if="subEditId">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="font-semibold text-slate-700 text-sm"
                            x-text="subEditId ? 'Edit Substitute' : '+ Tambah Substitute'"></div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-700">BOM FG <span
                                        class="text-red-600">*</span></label>
                                <select name="fg_part_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"
                                    required x-model="subForm.fg_part_id" :disabled="!!subEditId">
                                    <option value="">-- Pilih FG --</option>
                                    @foreach($fgPartsWithBom as $fg)
                                        <option value="{{ $fg->id }}">{{ $fg->part_no }} - {{ $fg->part_name }}</option>
                                    @endforeach
                                </select>
                                <template x-if="subEditId">
                                    <input type="hidden" name="fg_part_id" :value="subForm.fg_part_id">
                                </template>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Substitute RM <span
                                        class="text-red-600">*</span></label>
                                <select name="substitute_part_id"
                                    class="mt-1 w-full rounded-lg border-slate-200 text-sm" required
                                    x-model="subForm.substitute_part_id">
                                    <option value="">-- Pilih RM --</option>
                                    @foreach($rmParts as $rm)
                                        <option value="{{ $rm->id }}" x-show="form.id != {{ $rm->id }}">{{ $rm->part_no }} -
                                            {{ $rm->part_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Ratio</label>
                                <input type="number" name="ratio" step="0.001" min="0.001"
                                    class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.ratio">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Priority</label>
                                <input type="number" name="priority" min="1"
                                    class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.priority">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Status</label>
                                <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm"
                                    x-model="subForm.status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Notes</label>
                                <input type="text" name="notes" maxlength="255"
                                    class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.notes"
                                    placeholder="Opsional">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold"
                                @click="cancelSubEdit()" x-show="subEditId">Batal</button>
                            <button type="submit"
                                class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold"
                                x-text="subEditId ? 'Update' : 'Tambah'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function planningGciParts() {
                return {
                    modalOpen: false,
                    importOpen: false,
                    subsOpen: false,
                    subEditId: null,
                    subFormAction: '',
                    subForm: { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' },
                    mode: 'create',
                    formAction: @js(route('planning.gci-parts.store')),
                    fgSearch: '',
                    vendorSearch: '',
                    form: { id: null, customer_ids: [], part_no: '', classification: 'FG', part_name: '', size: '', model: '', status: 'active', destination_fg_ids: [], vendor_ids: [], substitutes_for: [], as_substitute: [] },

                    init() {
                        const warningData = @js(session('duplicate_warning_data'));
                        if (warningData) {
                            this.mode = 'create';
                            this.form = {
                                id: null,
                                customer_ids: warningData.customer_ids || [],
                                part_no: warningData.part_no || '',
                                classification: warningData.classification || 'FG',
                                part_name: warningData.part_name || '',
                                size: warningData.size || '',
                                model: warningData.model || '',
                                status: warningData.status || 'active',
                                destination_fg_ids: warningData.destination_fg_ids || [],
                                vendor_ids: warningData.vendor_ids || []
                            };
                            this.modalOpen = true;

                            setTimeout(() => {
                                if (confirm("Part number '" + this.form.part_no + "' sudah ada. Lanjutkan buat duplikat?")) {
                                    if (this.$refs.confirmDuplicateInput) {
                                        this.$refs.confirmDuplicateInput.value = '1';
                                        this.$refs.gciPartForm.submit();
                                    }
                                }
                            }, 300);
                        }
                    },

                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('planning.gci-parts.store'));
                        const currentClassification = @js($classification ?? 'FG');
                        this.fgSearch = '';
                        this.vendorSearch = '';
                        this.subsOpen = false;
                        this.cancelSubEdit();
                        this.form = { id: null, customer_ids: [], part_no: '', classification: currentClassification, part_name: '', size: '', model: '', status: 'active', destination_fg_ids: [], vendor_ids: [], substitutes_for: [], as_substitute: [] };
                        this.modalOpen = true;
                    },
                    openEdit(p) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/planning/gci-parts')) + '/' + p.id;
                        this.fgSearch = '';
                        this.vendorSearch = '';
                        const rmFgMap = @js($rmFgMap ?? []);
                        const linkedFgs = (rmFgMap[p.id] || []).map(Number);
                        const partVendorMap = @js($partVendorMap ?? []);
                        const linkedVendors = (partVendorMap[p.id] || []).map(Number);
                        const partSubstitutesMap = @js($partSubstitutesMap ?? []);
                        const partAsSubstituteMap = @js($partAsSubstituteMap ?? []);
                        this.subsOpen = false;
                        this.cancelSubEdit();
                        this.subFormAction = @js(url('/planning/gci-parts')) + '/' + p.id + '/substitutes';
                        this.form = {
                            id: p.id,
                            customer_ids: p.customers ? p.customers.map(c => Number(c.id)) : [],
                            part_no: p.part_no || '',
                            classification: p.classification || 'FG',
                            part_name: p.part_name || '',
                            size: p.size || '',
                            model: p.model || '',
                            status: p.status || 'active',
                            destination_fg_ids: linkedFgs,
                            vendor_ids: linkedVendors,
                            substitutes_for: partSubstitutesMap[p.id] || [],
                            as_substitute: partAsSubstituteMap[p.id] || [],
                        };
                        this.modalOpen = true;
                    },
                    toggleCustomer(id) {
                        const index = this.form.customer_ids.indexOf(id);
                        if (index === -1) {
                            this.form.customer_ids.push(id);
                        } else {
                            this.form.customer_ids.splice(index, 1);
                        }
                    },
                    openImport() {
                        this.importOpen = true;
                    },
                    editSub(s) {
                        this.subEditId = s.id;
                        this.subFormAction = @js(url('/planning/gci-part-substitutes')) + '/' + s.id;
                        this.subForm = {
                            fg_part_id: String(s.fg_part_id || ''),
                            substitute_part_id: String(s.substitute_part_id || ''),
                            ratio: s.ratio || 1,
                            priority: s.priority || 1,
                            status: s.status || 'active',
                            notes: s.notes || '',
                        };
                    },
                    cancelSubEdit() {
                        this.subEditId = null;
                        if (this.form.id) {
                            this.subFormAction = @js(url('/planning/gci-parts')) + '/' + this.form.id + '/substitutes';
                        }
                        this.subForm = { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' };
                    },
                    deleteSub(s) {
                        if (!confirm('Hapus substitute ' + s.substitute_part_no + '?')) return;
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = @js(url('/planning/gci-part-substitutes')) + '/' + s.id;
                        form.innerHTML = '@csrf @method("DELETE")';
                        document.body.appendChild(form);
                        form.submit();
                    },
                    close() { this.modalOpen = false; this.subsOpen = false; this.cancelSubEdit(); },
                }
            }
        </script>

        {{-- Import Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
            x-show="importOpen" x-cloak @keydown.escape.window="importOpen=false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Import Part GCI</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                        @click="importOpen=false">&#10005;</button>
                </div>
                <form action="{{ route('planning.gci-parts.import') }}" method="POST" enctype="multipart/form-data"
                    class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">File Excel</label>
                        <input type="file" name="file" accept=".xlsx,.xls" required
                            class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div class="mt-2 text-xs text-slate-500">
                            Kolom Part: <span class="font-semibold text-indigo-700">customer</span>, part_no,
                            classification, part_name, model, status
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Kolom Substitute (opsional): <span
                                class="font-semibold text-indigo-700">component_part_no</span>, substitute_part_no,
                            substitute_ratio, substitute_priority, substitute_status, substitute_notes
                        </div>
                        <div
                            class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                            Import mode: <strong>Upsert</strong> — part yang sudah ada akan di-update, yang baru akan
                            dibuat. Part name kosong akan otomatis diisi dari Part No.
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="importOpen=false">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>