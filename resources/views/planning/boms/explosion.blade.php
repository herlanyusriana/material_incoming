<x-app-layout>
    <x-slot name="header">
        BOM Explosion • {{ $bom->part->part_no }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Header Info --}}
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900">{{ $bom->part->part_no }}</h3>
                        <p class="text-slate-600 mt-1">{{ $bom->part->part_name }}</p>
                        <div class="flex items-center gap-4 mt-2 text-sm">
                            <span class="px-2 py-1 rounded-full bg-indigo-100 text-indigo-800 font-semibold">{{ $bom->part->classification }}</span>
                            <span class="text-slate-500">Revision: <span class="font-semibold">{{ $bom->revision ?? 'A' }}</span></span>
                            <span class="text-slate-500">Status: <span class="font-semibold {{ $bom->status === 'active' ? 'text-green-600' : 'text-slate-400' }}">{{ strtoupper($bom->status) }}</span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="GET" class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-slate-700">Quantity:</label>
                            <input type="number" name="qty" value="{{ $quantity }}" min="1" step="1" class="w-24 rounded-lg border-slate-200 text-sm">
                            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm">Recalculate</button>
                        </form>
                        <a href="{{ route('planning.boms.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold text-sm">Back to BOM List</a>
                    </div>
                </div>
            </div>

            {{-- BOM Explosion Tree --}}
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                <h4 class="text-lg font-bold text-slate-900 mb-4">📋 BOM Structure (Multi-Level Explosion)</h4>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Level</th>
                                <th class="px-4 py-3 text-left font-semibold">Line</th>
                                <th class="px-4 py-3 text-left font-semibold">Component Part</th>
                                <th class="px-4 py-3 text-left font-semibold">Process / Machine</th>
                                <th class="px-4 py-3 text-left font-semibold">WIP Output</th>
                                <th class="px-4 py-3 text-right font-semibold">Usage Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Total Qty</th>
                                <th class="px-4 py-3 text-left font-semibold">UOM</th>
                                <th class="px-4 py-3 text-center font-semibold">Make/Buy</th>
                                <th class="px-4 py-3 text-left font-semibold">Material Spec</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($explosion as $item)
                                @php
                                    $indent = $item['level'] * 2;
                                    $levelColors = [
                                        0 => 'bg-blue-50 border-l-4 border-blue-500',
                                        1 => 'bg-green-50 border-l-4 border-green-500',
                                        2 => 'bg-yellow-50 border-l-4 border-yellow-500',
                                        3 => 'bg-purple-50 border-l-4 border-purple-500',
                                    ];
                                    $rowClass = $levelColors[$item['level']] ?? 'bg-slate-50 border-l-4 border-slate-300';
                                @endphp
                                <tr class="hover:bg-slate-50 {{ $rowClass }}">
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-900 text-white text-xs font-bold">
                                            {{ $item['level'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-xs text-slate-500">{{ $item['line_no'] ?? '-' }}</td>
                                    <td class="px-4 py-3" style="padding-left: {{ 1 + $indent * 0.5 }}rem;">
                                        <div class="flex items-center gap-2">
                                            @for($i = 0; $i < $item['level']; $i++)
                                                <span class="text-slate-300">└─</span>
                                            @endfor
                                            <div>
                                                <div class="font-bold text-slate-900">{{ $item['component_part_no'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $item['component_part']->part_name ?? '-' }}</div>
                                                @if($item['material_name'])
                                                    <div class="text-xs text-slate-400 italic">{{ $item['material_name'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item['process_name'])
                                            <div class="font-semibold text-indigo-700">🔧 {{ $item['process_name'] }}</div>
                                        @endif
                                        @if($item['machine_name'])
                                            <div class="text-xs text-slate-500">⚙️ {{ $item['machine_name'] }}</div>
                                        @endif
                                        @if(!$item['process_name'] && !$item['machine_name'])
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item['wip_part_no'])
                                            <div class="text-sm font-semibold text-green-700">{{ $item['wip_part_no'] }}</div>
                                            @if($item['wip_part_name'])
                                                <div class="text-xs text-slate-500">{{ $item['wip_part_name'] }}</div>
                                            @endif
                                            @if($item['wip_qty'])
                                                <div class="text-xs text-slate-400">{{ number_format($item['wip_qty'], 2) }} {{ $item['wip_uom'] ?? '' }}</div>
                                            @endif
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm">
                                        {{ number_format($item['usage_qty'], 4) }}
                                        @if($item['scrap_factor'] > 0 || $item['yield_factor'] != 1)
                                            <div class="text-xs text-orange-600">
                                                @if($item['scrap_factor'] > 0)
                                                    Scrap: {{ number_format($item['scrap_factor'] * 100, 1) }}%
                                                @endif
                                                @if($item['yield_factor'] != 1)
                                                    Yield: {{ number_format($item['yield_factor'] * 100, 1) }}%
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm font-bold text-slate-900">
                                        {{ number_format($item['total_qty'], 4) }}
                                    </td>
                                    <td class="px-4 py-3 text-left text-xs font-semibold text-slate-600">
                                        {{ $item['consumption_uom']->code ?? $item['consumption_uom'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['make_or_buy'] === 'make')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">MAKE</span>
                                        @elseif($item['make_or_buy'] === 'buy')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">BUY</span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item['material_spec'] || $item['material_size'])
                                            <div class="text-xs">
                                                @if($item['material_spec'])
                                                    <div class="font-semibold text-slate-700">{{ $item['material_spec'] }}</div>
                                                @endif
                                                @if($item['material_size'])
                                                    <div class="text-slate-500">{{ $item['material_size'] }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-slate-500">No components in this BOM</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Material Summary --}}
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                <h4 class="text-lg font-bold text-slate-900 mb-4">📦 Total Material Requirements Summary</h4>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-emerald-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Part Number</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                <th class="px-4 py-3 text-right font-semibold">Total Quantity</th>
                                <th class="px-4 py-3 text-left font-semibold">UOM</th>
                                <th class="px-4 py-3 text-center font-semibold">Make/Buy</th>
                                <th class="px-4 py-3 text-left font-semibold">Material Spec</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($materials as $mat)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $mat['part_no'] }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $mat['part']->part_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-lg font-bold text-indigo-700">{{ number_format($mat['total_qty'], 4) }}</td>
                                    <td class="px-4 py-3 text-left text-xs font-semibold text-slate-600">{{ $mat['uom']->code ?? $mat['uom'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($mat['make_or_buy'] === 'make')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">MAKE</span>
                                        @elseif($mat['make_or_buy'] === 'buy')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">BUY</span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $mat['material_spec'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">No materials</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Legend --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <h5 class="text-sm font-bold text-slate-700 mb-2">📖 Legend</h5>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-blue-500"></div>
                        <span>Level 0 (Direct Components)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-green-500"></div>
                        <span>Level 1 (Sub-components)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-yellow-500"></div>
                        <span>Level 2 (Sub-sub-components)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-purple-500"></div>
                        <span>Level 3+</span>
                    </div>
                </div>
                <div class="mt-3 text-xs text-slate-600">
                    <strong>Process:</strong> Stamping operations (Blanking, Piercing, Bending, etc.) | 
                    <strong>WIP:</strong> Work-in-progress parts from stamping | 
                    <strong>Total Qty:</strong> Calculated with scrap & yield factors
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
