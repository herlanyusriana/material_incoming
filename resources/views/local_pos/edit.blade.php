<x-app-layout>
    <x-slot name="header">
        Edit Local PO — {{ $arrival->invoice_no }}
    </x-slot>

    @php
        $partsPayload = collect($parts)->map(fn($p) => [
            'id' => $p->id,
            'vendor_id' => $p->vendor_id,
            'price' => $p->price,
            'uom' => $p->uom,
            'size' => $p->storage_reg,
            'label' => trim((string) $p->part_no) . ' — ' . trim((string) ($p->part_name_gci ?? $p->part_name_vendor ?? '')),
        ])->values();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                    <div class="font-semibold mb-2">Failed to update Local PO:</div>
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $initialItems = old('items', $arrival->items->map(fn($item) => [
                    'id' => $item->id,
                    'part_id' => $item->part_id,
                    'size' => $item->size,
                    'qty_goods' => $item->qty_goods,
                    'unit_goods' => $item->unit_goods,
                    'price' => $item->price
                ]));
            @endphp

            <form action="{{ route('local-pos.update', $arrival) }}" method="POST" enctype="multipart/form-data"
                class="bg-white border border-slate-200 rounded-2xl shadow-lg p-8 space-y-8" id="local-po-form"
                x-data="localPoEdit()">
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between pb-6 border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Edit Local PO</h3>
                        <p class="text-sm text-slate-600 mt-1">Update items will synchronize with database.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('local-pos.index') }}"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Back</a>
                    </div>
                </div>

                <div class="grid md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-slate-700">Vendor (LOCAL)</label>
                        <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200" required
                            x-model="vendor_id">
                            <option value="" disabled>Select vendor</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">PO No</label>
                        <input type="text" name="po_no" value="{{ old('po_no', $arrival->invoice_no) }}"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="PO-LOCAL-001"
                            required>
                        @error('po_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">PO Date</label>
                        <input type="date" name="po_date"
                            value="{{ old('po_date', $arrival->invoice_date?->format('Y-m-d')) }}"
                            class="mt-1 w-full rounded-xl border-slate-200" required>
                        @error('po_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Currency</label>
                        <input type="text" name="currency" value="{{ old('currency', $arrival->currency) }}"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="IDR">
                        @error('currency') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-sm font-semibold text-slate-700">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $arrival->notes) }}"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="Optional">
                        @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Items</h4>
                        <button type="button"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold"
                            @click="addItem()">+ Add Item</button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl min-h-[200px]">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold w-72">Part</th>
                                    <th class="px-4 py-3 text-left font-semibold">Size</th>
                                    <th class="px-4 py-3 text-right font-semibold">Qty Goods</th>
                                    <th class="px-4 py-3 text-right font-semibold">Price per UOM (IDR)</th>
                                    <th class="px-4 py-3 text-right font-semibold">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3">
                                            <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">

                                            <!-- Custom Dropdown with Teleport -->
                                            <div x-data="{
                                                open: false,
                                                search: '',
                                                trigger: null,
                                                dropdown: null,
                                                get filteredParts() {
                                                    if (!vendor_id) return [];
                                                    if (this.search === '') return parts.filter(p => p.vendor_id == vendor_id);
                                                    return parts.filter(p => p.vendor_id == vendor_id && p.label.toLowerCase().includes(this.search.toLowerCase()));
                                                },
                                                get selectedLabel() {
                                                    if (!vendor_id) return 'Select vendor first';
                                                    let p = parts.find(p => p.id == item.part_id);
                                                    return p ? p.label : 'Select part';
                                                },
                                                init() {
                                                    this.trigger = this.$refs.trigger;
                                                },
                                                toggle() {
                                                    if (!vendor_id) return;
                                                    this.open = !this.open;
                                                    if (this.open) {
                                                        this.$nextTick(() => this.updatePosition());
                                                    }
                                                },
                                                updatePosition() {
                                                    if (!this.open) return;
                                                    const rect = this.trigger.getBoundingClientRect();
                                                    this.dropdown = this.$refs.dropdown;
                                                    
                                                    this.dropdown.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                                                    this.dropdown.style.left = (rect.left + window.scrollX) + 'px';
                                                    this.dropdown.style.width = rect.width + 'px';
                                                },
                                                select(id) {
                                                    item.part_id = id;
                                                    this.open = false;
                                                    this.search = '';
                                                    onPartChange(item);
                                                }
                                            }" @resize.window="updatePosition()" @scroll.window="updatePosition()">
                                                <input type="hidden" :name="`items[${index}][part_id]`"
                                                    x-model="item.part_id">

                                                <button type="button" x-ref="trigger"
                                                    class="w-full text-left bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex items-center justify-between"
                                                    :class="{'opacity-50 cursor-not-allowed': !vendor_id}"
                                                    @click="toggle()">
                                                    <span class="block truncate" x-text="selectedLabel"></span>
                                                    <svg class="h-5 w-5 text-gray-400"
                                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>

                                                <template x-teleport="body">
                                                    <div x-show="open" x-ref="dropdown" @click.outside="open = false"
                                                        class="absolute z-[9999] bg-white shadow-xl border border-slate-200 rounded-xl py-1 overflow-auto max-h-60"
                                                        style="display: none;">

                                                        <div
                                                            class="sticky top-0 z-10 bg-white px-2 py-1.5 border-b border-slate-100">
                                                            <input type="text" x-model="search"
                                                                class="w-full border-slate-200 rounded-lg text-xs placeholder-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="Search part..." @click.stop>
                                                        </div>

                                                        <template x-for="p in filteredParts" :key="p.id">
                                                            <div class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 text-slate-900"
                                                                @click="select(p.id)">
                                                                <span class="block truncate text-xs" x-text="p.label"
                                                                    :class="{ 'font-semibold': item.part_id == p.id, 'font-normal': item.part_id != p.id }"></span>

                                                                <span x-show="item.part_id == p.id"
                                                                    class="text-indigo-600 absolute inset-y-0 right-0 flex items-center pr-4">
                                                                    <svg class="h-5 w-5"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L8.586 13.586l7.293-7.293a1 1 0 011.414 0z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                </span>
                                                            </div>
                                                        </template>

                                                        <div x-show="filteredParts.length === 0"
                                                            class="cursor-default select-none relative py-2 pl-3 pr-9 text-slate-500 italic text-xs">
                                                            No part found
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" :name="`items[${index}][size]`" x-model="item.size"
                                                class="w-40 rounded-xl border-slate-200 uppercase"
                                                placeholder="0.7 X 530 X C">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <input type="number" :name="`items[${index}][qty_goods]`" min="0"
                                                    step="1" x-model="item.qty_goods"
                                                    class="w-24 text-right rounded-xl border-slate-200" required>
                                                <select :name="`items[${index}][unit_goods]`" x-model="item.unit_goods"
                                                    class="w-24 rounded-xl border-slate-200" required>
                                                    <option value="PCS">PCS</option>
                                                    <option value="COIL">COIL</option>
                                                    <option value="SHEET">SHEET</option>
                                                    <option value="SET">SET</option>
                                                    <option value="EA">EA</option>
                                                    <option value="ROLL">ROLL</option>
                                                    <option value="KGM">KGM</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" :name="`items[${index}][price]`" step="0.01" min="0"
                                                x-model="item.price" class="w-32 text-right rounded-xl border-slate-200"
                                                placeholder="0">
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" class="text-red-600 hover:text-red-800 font-semibold"
                                                @click="removeItem(index)" :disabled="items.length <= 1"
                                                :class="{'opacity-50 cursor-not-allowed': items.length <= 1}">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-200">
                    <a href="{{ route('local-pos.index') }}"
                        class="px-5 py-2.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors text-sm font-medium">Cancel</a>
                    <button type="submit"
                        class="px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        Update Local PO
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function localPoEdit() {
            return {
                vendor_id: @js(old('vendor_id', $arrival->vendor_id)),
                parts: @js($partsPayload),
                items: @js($initialItems),

                init() {
                    // Ensure at least one item
                    if (this.items.length === 0) this.addItem();
                },

                addItem() {
                    this.items.push({
                        id: null,
                        part_id: '',
                        size: '',
                        qty_goods: 0,
                        unit_goods: 'PCS',
                        price: 0
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                onPartChange(item) {
                    const part = this.parts.find(p => String(p.id) === String(item.part_id));
                    if (part) {
                        item.size = part.size ? part.size.toUpperCase() : '';
                        item.price = part.price || 0;
                        if (part.uom) {
                            item.unit_goods = part.uom.toUpperCase();
                        }
                    }
                }
            }
        }
    </script>
</x-app-layout>