<x-app-layout>
    <x-slot name="header">
        Planning • Select Forecast Sources
    </x-slot>

    <div class="py-6" x-data="{ selectedPos: [], selectedPlanning: [], selectAllPos: false, selectAllPlanning: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Select Forecast Sources</h2>
                    <p class="text-sm text-slate-600 mt-1">Choose individual PO Numbers and Planning IDs to include in forecast</p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ route('planning.forecasts.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                        ← Back
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('planning.forecasts.generate') }}">
                @csrf
                
                <!-- Customer POs Section -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-blue-50 border-b flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold text-blue-900">Customer Purchase Orders</h3>
                            <p class="text-xs text-blue-700 mt-1">{{ $customerPos->count() }} open items found</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectAllPos" @change="selectedPos = selectAllPos ? {{ $customerPos->pluck('id')->toJson() }} : []" class="rounded border-blue-300 text-blue-600">
                            <span class="text-sm font-semibold text-blue-900">Select All</span>
                        </label>
                    </div>

                    @if($customerPos->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 italic">
                            No Open Customer POs available for selection.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-12"></th>
                                        <th class="px-6 py-3 text-left font-semibold">PO NUMBER</th>
                                        <th class="px-6 py-3 text-left font-semibold">GCI PART</th>
                                        <th class="px-6 py-3 text-center font-semibold">WEEK</th>
                                        <th class="px-6 py-3 text-right font-semibold">QTY</th>
                                        <th class="px-6 py-3 text-left font-semibold">CUSTOMER</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($customerPos as $po)
                                        <tr class="hover:bg-blue-50 transition-colors" :class="selectedPos.includes({{ $po->id }}) ? 'bg-blue-50' : ''">
                                            <td class="px-6 py-4">
                                                <input 
                                                    type="checkbox" 
                                                    name="selected_pos[]" 
                                                    value="{{ $po->id }}"
                                                    x-model.number="selectedPos"
                                                    class="rounded border-slate-300 text-blue-600"
                                                >
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-blue-700">{{ $po->po_no ?? 'N/A' }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">ID: #{{ $po->id }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-semibold text-slate-900">{{ $po->part?->part_no ?? '-' }}</div>
                                                <div class="text-xs text-slate-500">{{ $po->part?->part_name ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-0.5 bg-slate-100 rounded-md font-mono text-xs font-semibold text-slate-600">
                                                    {{ $po->minggu }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono font-bold text-blue-700">{{ formatNumber($po->qty) }}</td>
                                            <td class="px-6 py-4 text-slate-600 text-xs">{{ $po->customer->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Customer Planning Rows Section -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden mt-6">
                    <div class="px-6 py-4 bg-green-50 border-b flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold text-green-900">Customer Planning Rows</h3>
                            <p class="text-xs text-green-700 mt-1">{{ $planningRows->count() }} accepted IDs found</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectAllPlanning" @change="selectedPlanning = selectAllPlanning ? {{ $planningRows->pluck('id')->toJson() }} : []" class="rounded border-green-300 text-green-600">
                            <span class="text-sm font-semibold text-green-900">Select All</span>
                        </label>
                    </div>

                    @if($planningRows->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 italic">
                            No Accepted Planning Rows available for selection.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-12"></th>
                                        <th class="px-6 py-3 text-left font-semibold">PLANNING ID</th>
                                        <th class="px-6 py-3 text-left font-semibold">GCI COMPONENTS</th>
                                        <th class="px-6 py-3 text-center font-semibold">WEEK</th>
                                        <th class="px-6 py-3 text-right font-semibold">QTY</th>
                                        <th class="px-6 py-3 text-left font-semibold">CUSTOMER</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($planningRows as $row)
                                        <tr class="hover:bg-green-50 transition-colors" :class="selectedPlanning.includes({{ $row->id }}) ? 'bg-green-50' : ''">
                                            <td class="px-6 py-4">
                                                <input 
                                                    type="checkbox" 
                                                    name="selected_planning[]" 
                                                    value="{{ $row->id }}"
                                                    x-model.number="selectedPlanning"
                                                    class="rounded border-slate-300 text-green-600"
                                                >
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-green-700">#{{ $row->id }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">{{ $row->customer_part_no }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($row->customerPart && $row->customerPart->components->isNotEmpty())
                                                    <div class="space-y-1">
                                                        @foreach($row->customerPart->components as $comp)
                                                            <div class="text-[10px] leading-tight flex justify-between gap-2 bg-white px-1 rounded border border-slate-100">
                                                                <span class="font-medium text-slate-700">{{ $comp->part->part_no ?? '-' }}</span>
                                                                <span class="text-slate-500">{{ (float)$comp->usage_qty }}x</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-xs text-slate-400">GCI: {{ $row->part?->part_no ?? 'Unmapped' }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-0.5 bg-slate-100 rounded-md font-mono text-xs font-semibold text-slate-600">
                                                    {{ $row->minggu }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono font-bold text-green-700">{{ formatNumber($row->qty) }}</td>
                                            <td class="px-6 py-4 text-slate-600 text-xs">{{ $row->planningImport?->customer?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="bg-white border rounded-xl shadow-sm p-6 mt-6 sticky bottom-6 z-10">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-600">
                            <span class="font-semibold text-blue-700" x-text="selectedPos.length"></span> PO item(s) + 
                            <span class="font-semibold text-green-700" x-text="selectedPlanning.length"></span> Planning item(s) selected
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('planning.forecasts.index') }}" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-semibold">
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-sm"
                                :disabled="selectedPos.length === 0 && selectedPlanning.length === 0"
                                :class="(selectedPos.length === 0 && selectedPlanning.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                            >
                                ✓ Generate Forecast from Selected
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
