@php
    $materialRequestLines = collect($order->material_request_lines ?? []);
    $materialShortageLines = $materialRequestLines->where('shortage_qty', '>', 0)->values();
    $materialShortageCount = $materialShortageLines->count();
    $holdReason = null;
    if ($order->status === 'material_hold') {
        $holdReason = 'Material shortage';
    } elseif ($order->status === 'resource_hold') {
        $holdReason = 'Resource / capacity issue';
    }
@endphp

<div class="grid grid-cols-1 gap-6">
    <!-- Order Details -->
    <div class="space-y-6">
        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <div class="mb-4 flex items-start justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-900">Order Details</h3>
                @if(!in_array($order->status, ['completed', 'cancelled'], true))
                    <div class="flex items-center gap-2">
                        <a href="{{ route('production.orders.edit', $order) }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Update
                        </a>
                        <form action="{{ route('production.orders.cancel', $order) }}" method="POST"
                            onsubmit="return confirm('Cancel WO ini? Material reserve akan dikembalikan.');">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">Order Number</dt>
                    <dd class="font-medium text-gray-900">{{ $order->production_order_number }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Part Number</dt>
                    <dd class="font-medium text-gray-900">{{ $order->part->part_no }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Part Name</dt>
                    <dd class="font-medium text-gray-900">{{ $order->part->part_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Process</dt>
                    <dd class="font-medium text-gray-900">{{ $order->process_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Machine</dt>
                    <dd class="font-medium text-gray-900">{{ $order->machine?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Dies</dt>
                    <dd class="font-medium text-gray-900">{{ $order->die_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Planned Qty</dt>
                    <dd class="font-medium text-lg text-gray-900">{{ number_format($order->qty_planned) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Plan Date</dt>
                    <dd class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($order->plan_date)->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                            @if($order->status == 'completed') bg-green-100 text-green-800
                            @elseif($order->status == 'in_production') bg-blue-100 text-blue-800
                            @elseif($order->status == 'released') bg-indigo-100 text-indigo-800
                            @elseif($order->status == 'material_hold') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Stage</dt>
                    <dd class="font-medium text-gray-900">{{ strtoupper(str_replace('_', ' ', $order->workflow_stage)) }}</dd>
                </div>
            </dl>
        </div>

        @if($holdReason)
            <div class="bg-white border rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-red-700">Hold Reason</h3>
                        <p class="text-sm text-slate-500">Alasan kenapa WO ini masih hold.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">
                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>

                @if($order->status === 'material_hold')
                    @if($materialShortageCount > 0)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="mb-3 font-semibold text-red-800">Material yang menyebabkan hold</div>
                            <div class="space-y-2">
                                @foreach($materialShortageLines->take(5) as $line)
                                    <div class="rounded border border-red-200 bg-white px-3 py-2">
                                        <div class="text-sm font-semibold text-slate-900">{{ $line['component_part_no'] ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $line['component_part_name'] ?? '-' }}</div>
                                        <div class="mt-1 text-xs text-red-700">
                                            Required {{ number_format((float) ($line['required_qty'] ?? 0), 4) }}
                                            • Allocated {{ number_format((float) ($line['available_qty'] ?? 0), 4) }}
                                            • Shortage {{ number_format((float) ($line['shortage_qty'] ?? 0), 4) }}
                                        </div>
                                    </div>
                                @endforeach
                                @if($materialShortageCount > 5)
                                    <div class="text-xs text-red-700">+ {{ $materialShortageCount - 5 }} item shortage lainnya.</div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            WO berstatus material hold, tapi detail shortage belum tersimpan di material request.
                        </div>
                    @endif
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        WO berstatus resource hold. Detail alasan resource belum tersimpan sebagai daftar item di sistem saat ini.
                    </div>
                @endif
            </div>
        @endif

        <!-- Daily Planning Mapping -->
        @if($order->dailyPlanCell)
        <div class="bg-indigo-50 rounded-lg p-6 border border-indigo-200">
            <h3 class="text-lg font-semibold mb-4 text-indigo-900 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Daily Planning Mapping
            </h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                <div>
                    <dt class="text-indigo-600 font-medium">Production Line</dt>
                    <dd class="font-semibold text-indigo-900 text-lg">{{ $order->dailyPlanCell->row->production_line }}</dd>
                </div>
                <div>
                    <dt class="text-indigo-600 font-medium">Plan Date</dt>
                    <dd class="font-semibold text-indigo-900">{{ \Carbon\Carbon::parse($order->dailyPlanCell->plan_date)->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-indigo-600 font-medium">Sequence</dt>
                    <dd class="font-semibold text-indigo-900">{{ $order->dailyPlanCell->seq ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-indigo-600 font-medium">Planned Qty</dt>
                    <dd class="font-semibold text-indigo-900 text-lg">{{ $order->dailyPlanCell->qty ? number_format($order->dailyPlanCell->qty) : '-' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-indigo-600 font-medium mb-1">Part No (from Daily Plan)</dt>
                    <dd class="font-mono text-sm text-indigo-900 bg-white px-3 py-2 rounded border border-indigo-200">{{ $order->dailyPlanCell->row->part_no }}</dd>
                </div>
                @if($order->dailyPlanCell->row->plan)
                <div class="col-span-2 pt-2 border-t border-indigo-200">
                    <dt class="text-indigo-600 font-medium mb-1">Plan Period</dt>
                    <dd class="text-indigo-900">
                        {{ \Carbon\Carbon::parse($order->dailyPlanCell->row->plan->date_from)->format('d M Y') }}
                        <span class="mx-2">→</span>
                        {{ \Carbon\Carbon::parse($order->dailyPlanCell->row->plan->date_to)->format('d M Y') }}
                    </dd>
                </div>
                @endif
            </dl>
        </div>
        @else
        <div class="bg-amber-50 rounded-lg p-6 border border-amber-200">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-amber-900">No Daily Planning Mapping</h3>
                    <p class="text-xs text-amber-700 mt-1">This production order is not yet linked to any daily planning outgoing cell.</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Workflow Actions -->
        @if($order->status != 'completed' && $order->status != 'cancelled')
        <div class="bg-white border rounded-lg shadow-sm p-6 relative overflow-hidden">
             
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            
            @if($order->status == 'planned' || $order->status == 'material_hold' || $order->status == 'released')
                <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                    <h4 class="font-medium text-indigo-900 mb-2 font-black uppercase tracking-wide text-xs">Material Check</h4>
                    
                    @if(session('material_check'))
                        <div class="mb-4 overflow-hidden border border-indigo-200 rounded-lg bg-white">
                            <table class="min-w-full divide-y divide-gray-200 text-[10px]">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-1.5 text-left font-bold text-gray-500 uppercase">Part No</th>
                                        <th class="px-2 py-1.5 text-right font-bold text-gray-500 uppercase">Needed</th>
                                        <th class="px-2 py-1.5 text-right font-bold text-gray-500 uppercase">Stock</th>
                                        <th class="px-2 py-1.5 text-center font-bold text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach(session('material_check') as $item)
                                        <tr>
                                            <td class="px-2 py-1.5 font-medium text-gray-900">
                                                {{ $item['part_no'] }}
                                                <div class="text-[8px] text-gray-400 truncate w-24">{{ $item['part_name'] }}</div>
                                            </td>
                                            <td class="px-2 py-1.5 text-right font-semibold">{{ number_format($item['needed'], 2) }}</td>
                                            <td class="px-2 py-1.5 text-right {{ $item['sufficient'] ? 'text-emerald-600' : 'text-rose-600 font-bold' }}">
                                                {{ number_format($item['on_hand'], 2) }}
                                            </td>
                                            <td class="px-2 py-1.5 text-center">
                                                @if($item['sufficient'])
                                                    <span class="text-emerald-500 font-black">✓</span>
                                                @else
                                                    <span class="text-rose-500 font-black">⚠</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-indigo-700 mb-4">Validate BOM materials against inventory before releasing.</p>
                    @endif

                    <form action="{{ route('production.orders.check-material', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex justify-center items-center px-4 py-2 border border-indigo-200 rounded-md shadow-sm text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            {{ $order->status == 'material_hold' ? 'Re-check Material' : 'Check Availability' }}
                        </button>
                    </form>
                </div>
            @endif

            @if($order->status == 'released')
                <div class="p-4 bg-teal-50 rounded-lg border border-teal-100">
                    <h4 class="font-medium text-teal-900 mb-2">Ready to Start</h4>
                    <p class="text-sm text-teal-700 mb-4">Materials are reserved. Begin production workflow.</p>
                    <form action="{{ route('production.orders.start', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            Start Production
                        </button>
                    </form>
                </div>
            @endif

            @if($order->status == 'in_production')
                 <div class="space-y-4">
                     <div class="p-3 bg-blue-50 rounded border border-blue-100 flex justify-between items-center">
                         <span class="text-sm font-medium text-blue-900">Current Stage</span>
                         <span class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded-full font-bold">{{ strtoupper($order->workflow_stage) }}</span>
                     </div>
                     
                    <form action="{{ route('production.orders.finish', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="return confirm('Ensure all inspections are passed. Continue?');">
                            Finish Production
                        </button>
                    </form>
                 </div>
            @endif
        </div>
        @endif

        <!-- Inspections Brief -->
        <div class="bg-white border rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-md font-semibold text-gray-900">Recent Inspections</h3>
                <a href="{{ route('production.orders.show', $order) }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">View Full Details &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($order->inspections->take(3) as $inspection)
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-semibold text-gray-700">{{ strtoupper(str_replace('_', ' ', $inspection->type)) }}</span>
                            <span class="text-[10px] text-gray-400">{{ $inspection->created_at->diffForHumans() }}</span>
                        </div>
                        <div>
                             @if($inspection->status == 'pending')
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full text-[10px] font-bold">PENDING</span>
                            @elseif($inspection->status == 'pass')
                                <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-[10px] font-bold">PASS</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-[10px] font-bold">FAIL</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-4 text-center text-sm text-gray-500 italic">No inspections needed yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
