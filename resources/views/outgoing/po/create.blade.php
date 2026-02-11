@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-start gap-3">
                <div
                    class="h-12 w-12 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-lg">
                    PO
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-black text-slate-900">Create Customer PO</div>
                    <div class="mt-1 text-sm text-slate-500">Input PO dari customer untuk outgoing delivery</div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('outgoing.customer-po.store') }}" id="poForm">
            @csrf

            {{-- PO Header --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    PO Information
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">
                            Vendor / Customer <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" id="customerId" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none">
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id') == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">
                            PO No. <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="po_no" value="{{ old('po_no') }}" required
                            placeholder="e.g. 0093-POC/II/2026"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none">
                        @error('po_no')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">
                            PO Release Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="po_release_date" value="{{ old('po_release_date', date('Y-m-d')) }}"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none">
                        @error('po_release_date')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- PO Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-6">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        PO Items
                    </div>
                    <button type="button" onclick="addRow()"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs" id="itemsTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 w-12">
                                    No</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 min-w-[200px]">
                                    Vendor Part Name
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[160px]">
                                    Part Name</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[80px]">
                                    Model</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[120px]">
                                    Part No</th>
                                <th
                                    class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[80px]">
                                    Qty</th>
                                <th
                                    class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[100px]">
                                    Harga PO</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[130px]">
                                    Delivery Date</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 w-14">
                                </th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            {{-- rows added by JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm text-slate-500">
                            <span class="font-semibold">Tip:</span> Ketik di Vendor Part Name untuk auto-search parts dari
                            master data.
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('outgoing.customer-po.index') }}"
                                class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                Cancel
                            </a>
                            <button type="submit"
                                class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 flex items-center gap-2 shadow-md shadow-indigo-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Save PO
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if ($errors->any())
        <div class="fixed bottom-4 right-4 bg-red-50 border border-red-200 rounded-xl p-4 shadow-lg max-w-sm z-50">
            <div class="text-sm font-bold text-red-800 mb-1">Validation Errors</div>
            <ul class="text-xs text-red-600 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <style>
        .search-dropdown {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 220px;
            overflow-y: auto;
        }

        .search-dropdown .dd-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s;
        }

        .search-dropdown .dd-item:hover {
            background: #f8fafc;
        }

        .search-dropdown .dd-item:last-child {
            border-bottom: 0;
        }

        .item-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .item-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .item-input.readonly {
            background: #f8fafc;
            color: #475569;
            cursor: default;
        }

        .item-input.vendor-input {
            background: #fffbeb;
            border-color: #fcd34d;
        }

        .item-input.vendor-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .item-input-num {
            text-align: right;
            -moz-appearance: textfield;
        }

        .item-input-num::-webkit-outer-spin-button,
        .item-input-num::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    <script>
        const SEARCH_URL = '{{ route("outgoing.customer-po.search-parts") }}';
        let rowCount = 0;
        let searchTimeout = null;

        function addRow(data = null) {
            rowCount++;
            const tbody = document.getElementById('itemsBody');
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50/50 transition-colors';
            tr.id = `row-${rowCount}`;

            const vendorPartName = data?.vendor_part_name || '';
            const partName = data?.part_name || '';
            const model = data?.model || '';
            const partNo = data?.part_no || '';
            const gciPartId = data?.gci_part_id || '';
            const qty = data?.qty || '';
            const price = data?.price || '';
            const deliveryDate = data?.delivery_date || '';

            tr.innerHTML = `
                    <td class="px-3 py-2 text-center text-slate-500 font-semibold row-no">${rowCount}</td>
                    <td class="px-3 py-2 bg-amber-50/30 relative">
                        <input type="text" name="items[${rowCount}][vendor_part_name]" value="${vendorPartName}" required
                            class="item-input vendor-input" placeholder="Search vendor part..."
                            oninput="searchPart(this, ${rowCount})" onfocus="searchPart(this, ${rowCount})"
                            autocomplete="off" id="vendor-${rowCount}">
                        <input type="hidden" name="items[${rowCount}][gci_part_id]" value="${gciPartId}" id="gci-${rowCount}">
                        <div id="dd-${rowCount}" class="search-dropdown hidden"></div>
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" value="${partName}" readonly class="item-input readonly" id="partname-${rowCount}" tabindex="-1">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" value="${model}" readonly class="item-input readonly" id="model-${rowCount}" tabindex="-1">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" value="${partNo}" readonly class="item-input readonly" id="partno-${rowCount}" tabindex="-1">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="items[${rowCount}][qty]" value="${qty}" min="1" required
                            class="item-input item-input-num" placeholder="0">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="items[${rowCount}][price]" value="${price}" min="0" step="0.01" required
                            class="item-input item-input-num" placeholder="0">
                    </td>
                    <td class="px-3 py-2">
                        <input type="date" name="items[${rowCount}][delivery_date]" value="${deliveryDate}" required
                            class="item-input text-center">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" onclick="removeRow(${rowCount})"
                            class="w-7 h-7 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors flex items-center justify-center mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </td>
                `;
            tbody.appendChild(tr);
            renumberRows();
        }

        function removeRow(id) {
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.remove();
                renumberRows();
            }
        }

        function renumberRows() {
            const rows = document.querySelectorAll('#itemsBody tr');
            rows.forEach((row, idx) => {
                const noCell = row.querySelector('.row-no');
                if (noCell) noCell.textContent = idx + 1;
            });
        }

        function searchPart(input, rowId) {
            clearTimeout(searchTimeout);
            const term = input.value.trim();
            const dd = document.getElementById(`dd-${rowId}`);

            if (term.length < 2) {
                dd.classList.add('hidden');
                return;
            }

            const customerId = document.getElementById('customerId').value;

            searchTimeout = setTimeout(async () => {
                try {
                    const params = new URLSearchParams({ q: term });
                    if (customerId) params.append('customer_id', customerId);

                    const res = await fetch(`${SEARCH_URL}?${params}`);
                    const results = await res.json();

                    if (results.length === 0) {
                        dd.innerHTML = '<div class="p-3 text-xs text-slate-400 italic text-center">No parts found</div>';
                        dd.classList.remove('hidden');
                        return;
                    }

                    dd.innerHTML = results.map(r => `
                            <div class="dd-item" onclick="selectPart(${rowId}, ${JSON.stringify(r).replace(/"/g, '&quot;')})">
                                <div class="font-bold text-xs text-amber-800">${r.vendor_part_name}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    ${r.part_no} • ${r.part_name} • ${r.model}
                                </div>
                            </div>
                        `).join('');
                    dd.classList.remove('hidden');
                } catch (e) {
                    console.error(e);
                }
            }, 300);
        }

        function selectPart(rowId, data) {
            document.getElementById(`vendor-${rowId}`).value = data.vendor_part_name;
            document.getElementById(`gci-${rowId}`).value = data.gci_part_id;
            document.getElementById(`partname-${rowId}`).value = data.part_name;
            document.getElementById(`model-${rowId}`).value = data.model;
            document.getElementById(`partno-${rowId}`).value = data.part_no;
            document.getElementById(`dd-${rowId}`).classList.add('hidden');
        }

        // Close dropdowns on outside click
        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('vendor-input')) {
                document.querySelectorAll('.search-dropdown').forEach(d => d.classList.add('hidden'));
            }
        });

        // Start with 1 empty row
        document.addEventListener('DOMContentLoaded', function () {
            addRow();
        });
    </script>
@endsection