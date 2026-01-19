<x-app-layout>
    <x-slot name="header">
        Planning • Select Forecast Sources
    </x-slot>

    <div class="py-6" x-data="{ selectedPos: [], selectedImports: [], selectAllPos: false, selectAllImports: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Select Forecast Sources</h2>
                    <p class="text-sm text-slate-600 mt-1">Choose individual PO Numbers and Planning Files to include in forecast</p>
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
                            <h3 class="font-semibold text-blue-900 text-lg">1. Customer Purchase Orders</h3>
                            <p class="text-xs text-blue-700 mt-1">{{ $customerPos->count() }} open items found</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectAllPos" @change="selectedPos = selectAllPos ? {{ $customerPos->pluck('id')->toJson() }} : []" class="rounded border-blue-300 text-blue-600">
                            <span class="text-sm font-semibold text-blue-900">Select All POs</span>
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

                <!-- Customer Planning Files Section -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden mt-6">
                    <div class="px-6 py-4 bg-green-50 border-b flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold text-green-900 text-lg">2. Customer Planning Files</h3>
                            <p class="text-xs text-green-700 mt-1">{{ $planningImports->count() }} accepted files available</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectAllImports" @change="selectedImports = selectAllImports ? {{ $planningImports->pluck('id')->toJson() }} : []" class="rounded border-green-300 text-green-600">
                            <span class="text-sm font-semibold text-green-900">Select All Files</span>
                        </label>
                    </div>

                    @if($planningImports->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 italic">
                            No Accepted Planning Files available for selection.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-12"></th>
                                        <th class="px-6 py-3 text-left font-semibold">FILE NAME</th>
                                        <th class="px-6 py-3 text-left font-semibold">CUSTOMER</th>
                                        <th class="px-6 py-3 text-center font-semibold">ACCEPTED ROWS</th>
                                        <th class="px-6 py-3 text-right font-semibold">TOTAL QTY</th>
                                        <th class="px-6 py-3 text-center font-semibold">UPLOADED AT</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($planningImports as $import)
                                        <tr class="hover:bg-green-50 transition-colors" :class="selectedImports.includes({{ $import->id }}) ? 'bg-green-50' : ''">
                                            <td class="px-6 py-4">
                                                <input 
                                                    type="checkbox" 
                                                    name="selected_imports[]" 
                                                    value="{{ $import->id }}"
                                                    x-model.number="selectedImports"
                                                    class="rounded border-slate-300 text-green-600"
                                                >
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-slate-900">{{ $import->file_name }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono italic">Import ID: #{{ $import->id }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="font-semibold text-slate-700">{{ $import->customer->name ?? '-' }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                                    {{ $import->accepted_rows_count }} Rows
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono font-bold text-green-700">
                                                {{ formatNumber($import->total_accepted_qty) }}
                                            </td>
                                            <td class="px-6 py-4 text-center text-xs text-slate-500">
                                                {{ $import->created_at->format('d M Y H:i') }}
                                            </td>
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
                            <span class="font-semibold text-green-700" x-text="selectedImports.length"></span> Planning file(s) selected
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('planning.forecasts.index') }}" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-semibold">
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-sm"
                                :disabled="selectedPos.length === 0 && selectedImports.length === 0"
                                :class="(selectedPos.length === 0 && selectedImports.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
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
