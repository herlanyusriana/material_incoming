<x-app-layout>
    <x-slot name="header">
        Final Inspection - {{ $inspection->productionOrder->production_order_number }}
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Production Order Details</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Order Number</label>
                    <p class="text-sm font-medium">{{ $inspection->productionOrder->production_order_number }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Part</label>
                    <p class="text-sm font-medium">{{ $inspection->productionOrder->part->part_no }}</p>
                    <p class="text-xs text-slate-500">{{ $inspection->productionOrder->part->part_name }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Qty Produced</label>
                    <p class="text-sm font-medium">{{ number_format($inspection->productionOrder->qty_actual) }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Workflow Stage</label>
                    <p class="text-sm font-medium">{{ strtoupper(str_replace('_', ' ', $inspection->productionOrder->workflow_stage)) }}</p>
                </div>
            </div>
        </div>

        <!-- Final Inspection Form -->
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Final Inspection</h3>
            <form method="POST" action="{{ route('production.final-inspection.update', $inspection) }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Inspection Result *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="status" value="pass" {{ $inspection->status === 'pass' ? 'checked' : '' }} class="mr-2" required>
                                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-semibold">PASS</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="status" value="fail" {{ $inspection->status === 'fail' ? 'checked' : '' }} class="mr-2" required>
                                <span class="px-4 py-2 bg-red-100 text-red-700 rounded-lg font-semibold">FAIL</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="status" value="pending" {{ $inspection->status === 'pending' ? 'checked' : '' }} class="mr-2" required>
                                <span class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg font-semibold">PENDING</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Inspected Quantity</label>
                        <input type="number" name="inspected_qty" value="{{ $inspection->inspected_qty }}" step="0.01" class="w-full rounded-lg border-slate-200">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Notes / Remarks</label>
                        <textarea name="notes" rows="4" class="w-full rounded-lg border-slate-200" placeholder="Enter final inspection notes...">{{ $inspection->notes }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm font-semibold">
                            Save Inspection Result
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Kanban Update Form (Only if inspection passed) -->
        @if($inspection->status === 'pass')
            <div class="bg-white border rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Kanban Update & Inventory Posting</h3>
                
                @if($inspection->productionOrder->kanban_updated_at)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            <strong>✓ Kanban Updated</strong><br>
                            Updated at: {{ $inspection->productionOrder->kanban_updated_at->format('d M Y H:i') }}<br>
                            Good Qty: {{ number_format($inspection->productionOrder->qty_actual) }}<br>
                            NG Qty: {{ number_format($inspection->productionOrder->qty_ng ?? 0) }}
                        </p>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-yellow-800">
                            <strong>Important:</strong> This will update FG inventory and backflush component materials based on BOM.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('production.final-inspection.kanban-update', $inspection->productionOrder) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Good Quantity *</label>
                                <input type="number" name="qty_good" value="{{ $inspection->productionOrder->qty_actual }}" step="0.01" min="0" class="w-full rounded-lg border-slate-200" required>
                                <p class="text-xs text-slate-500 mt-1">Quantity to add to FG inventory</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">NG Quantity</label>
                                <input type="number" name="qty_ng" value="{{ $inspection->productionOrder->qty_ng ?? 0 }}" step="0.01" min="0" class="w-full rounded-lg border-slate-200">
                                <p class="text-xs text-slate-500 mt-1">Rejected/defective quantity</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-between items-center">
                            <a href="{{ route('production.final-inspection.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                                Back to List
                            </a>
                            <button type="submit" onclick="return confirm('This will update inventory. Are you sure?')" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-sm font-semibold">
                                Update Kanban & Post Inventory
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @else
            <div class="bg-slate-50 border rounded-xl shadow-sm p-6">
                <p class="text-sm text-slate-600">
                    Kanban Update is only available after Final Inspection is marked as <strong>PASS</strong>.
                </p>
            </div>
        @endif

        @if($inspection->inspected_at)
            <div class="bg-slate-50 border rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-600 mb-2">Inspection History</h3>
                <p class="text-sm">Inspected by: <strong>{{ $inspection->inspector?->name ?? 'Unknown' }}</strong></p>
                <p class="text-sm">Inspected at: <strong>{{ $inspection->inspected_at->format('d M Y H:i') }}</strong></p>
            </div>
        @endif
    </div>
</x-app-layout>
