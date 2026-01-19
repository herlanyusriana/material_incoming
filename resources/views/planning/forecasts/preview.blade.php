<x-app-layout>
    <x-slot name="header">
        Planning • Select Forecast Sources
    </x-slot>

    <div class="py-6" x-data="{ selectedPos: [], selectedPlanning: [], selectAllPos: false, selectAllPlanning: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Select All Data Sources</h2>
                    <p class="text-sm text-slate-600 mt-1">Choose which Customer POs and Planning rows to include in forecast across all weeks</p>
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
                            <h3 class="font-semibold text-blue-900">Customer Purchase Orders (OPEN)</h3>
                            <p class="text-xs text-blue-700 mt-1">{{ $customerPos->count() }} open POs found</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectAllPos" @change="selectedPos = selectAllPos ? {{ $customerPos->pluck('id')->toJson() }} : []" class="rounded border-blue-300 text-blue-600">
                            <span class="text-sm font-semibold text-blue-900">Select All</span>
                        </label>
                    </div>

                    @if($customerPos->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500">
                            No Open Customer POs found
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-12"></th>
                                        <th class="px-6 py-3 text-left font-semibold">Week</th>
                                        <th class="px-6 py-3 text-left font-semibold">Customer</th>
                                        <th class="px-6 py-3 text-left font-semibold">GCI Part</th>
                                        <th class="px-6 py-3 text-right font-semibold">Qty</th>
                                        <th class="px-6 py-3 text-center font-semibold">PO #ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($customerPos->groupBy('minggu') as $week => $pos)
                                        <tr class="bg-slate-50 border-y">
                                            <td colspan="6" class="px-6 py-2 font-bold text-slate-700">WEEK: {{ $week }}</td>
                                        </tr>
                                        @foreach($pos as $po)
                                            <tr class="hover:bg-blue-50" :class="selectedPos.includes({{ $po->id }}) ? 'bg-blue-50' : ''">
                                                <td class="px-6 py-4">
                                                    <input 
                                                        type="checkbox" 
                                                        name="selected_pos[]" 
                                                        value="{{ $po->id }}"
                                                        x-model.number="selectedPos"
                                                        class="rounded border-slate-300 text-blue-600"
                                                    >
                                                </td>
                                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600">{{ $po->minggu }}</td>
                                                <td class="px-6 py-4 text-slate-700">{{ $po->customer->name ?? '-' }}</td>
                                                <td class="px-6 py-4">
                                                    <div class="font-semibold text-slate-900">{{ $po->customerPart?->gciPart?->part_no ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $po->customerPart?->gciPart?->part_name ?? '-' }}</div>
                                                </td>
                                                <td class="px-6 py-4 text-right font-mono font-semibold text-blue-700">{{ formatNumber($po->qty) }}</td>
                                                <td class="px-6 py-4 text-center font-mono text-xs text-slate-400">#{{ $po->id }}</td>
                                            </tr>
                                        @endforeach
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
                            <h3 class="font-semibold text-green-900">Customer Planning Rows (ACCEPTED)</h3>
                            <p class="text-xs text-green-700 mt-1">{{ $planningRows->count() }} planning rows found</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectAllPlanning" @change="selectedPlanning = selectAllPlanning ? {{ $planningRows->pluck('id')->toJson() }} : []" class="rounded border-green-300 text-green-600">
                            <span class="text-sm font-semibold text-green-900">Select All</span>
                        </label>
                    </div>

                    @if($planningRows->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500">
                            No Accepted Planning Rows found
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-12"></th>
                                        <th class="px-6 py-3 text-left font-semibold">Week</th>
                                        <th class="px-6 py-3 text-left font-semibold">Customer</th>
                                        <th class="px-6 py-3 text-left font-semibold">Components</th>
                                        <th class="px-6 py-3 text-right font-semibold">Qty</th>
                                        <th class="px-6 py-3 text-center font-semibold">ROW #ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($planningRows->groupBy('minggu') as $week => $rows)
                                        <tr class="bg-slate-50 border-y">
                                            <td colspan="6" class="px-6 py-2 font-bold text-slate-700">WEEK: {{ $week }}</td>
                                        </tr>
                                        @foreach($rows as $row)
                                            <tr class="hover:bg-green-50" :class="selectedPlanning.includes({{ $row->id }}) ? 'bg-green-50' : ''">
                                                <td class="px-6 py-4">
                                                    <input 
                                                        type="checkbox" 
                                                        name="selected_planning[]" 
                                                        value="{{ $row->id }}"
                                                        x-model.number="selectedPlanning"
                                                        class="rounded border-slate-300 text-green-600"
                                                    >
                                                </td>
                                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600">{{ $row->minggu }}</td>
                                                <td class="px-6 py-4 text-slate-700">{{ $row->import?->customer?->name ?? '-' }}</td>
                                                <td class="px-6 py-4">
                                                    @if($row->customerPart && $row->customerPart->components->isNotEmpty())
                                                        @foreach($row->customerPart->components as $comp)
                                                            <div class="text-xs">
                                                                <span class="font-semibold text-slate-900">{{ $comp->part->part_no ?? '-' }}</span>
                                                                <span class="text-slate-500">({{ (float)$comp->usage_qty }}x)</span>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <span class="text-slate-400 text-xs">No components</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-right font-mono font-semibold text-green-700">{{ formatNumber($row->qty) }}</td>
                                                <td class="px-6 py-4 text-center font-mono text-xs text-slate-400">#{{ $row->id }}</td>
                                            </tr>
                                        @endforeach
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
                            <span class="font-semibold text-blue-700" x-text="selectedPos.length"></span> PO(s) + 
                            <span class="font-semibold text-green-700" x-text="selectedPlanning.length"></span> Planning row(s) selected
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
                                ✓ Generate Forecast from Selected Items
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
