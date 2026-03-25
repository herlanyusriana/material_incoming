@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">New OSP Outgoing Document</h1>
                    <div class="mt-1 text-sm text-slate-600">Buat dokumen outgoing khusus untuk part OSP yang dipisahkan dari outgoing normal.</div>
                </div>
                <a href="{{ route('outgoing.osp.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back</a>
            </div>
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-900">
            Gunakan dokumen ini jika part yang sama, misalnya `FG A`, keluar melalui jalur OSP. Yang dibedakan adalah jenis dokumennya, bukan master part-nya.
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form action="{{ route('outgoing.osp.store') }}" method="POST" class="space-y-6 max-w-2xl">
                @csrf

                @if (session('error'))
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 font-semibold">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Customer --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Customer <span class="text-red-500">*</span></label>
                    <select name="customer_id" required
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Part --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Part FG <span class="text-red-500">*</span></label>
                    <select name="gci_part_id" required
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Part</option>
                        @foreach ($ospParts as $p)
                            <option value="{{ $p['gci_part_id'] }}" data-bom="{{ $p['bom_item_id'] }}" @selected(old('gci_part_id') == $p['gci_part_id'])>
                                {{ $p['part_no'] }} — {{ $p['part_name'] }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="bom_item_id" id="bom_item_id" value="{{ old('bom_item_id') }}">
                </div>

                {{-- Qty --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">OSP Document Qty <span class="text-red-500">*</span></label>
                    <input type="number" name="qty_received_material" step="0.0001" min="0.0001" required
                        value="{{ old('qty_received_material') }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Document Date <span class="text-red-500">*</span></label>
                        <input type="date" name="received_date" required value="{{ old('received_date', now()->toDateString()) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Target Outgoing Date</label>
                        <input type="date" name="target_ship_date" value="{{ old('target_ship_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                        Create OSP Document
                    </button>
                    <a href="{{ route('outgoing.osp.index') }}"
                        class="rounded-lg bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('select[name="gci_part_id"]').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            document.getElementById('bom_item_id').value = option.dataset.bom || '';
        });
    </script>
@endsection
