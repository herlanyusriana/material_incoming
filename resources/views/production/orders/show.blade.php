<x-app-layout>
    <x-slot name="header">
        Order {{ $order->production_order_number }}
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Order Details & Workflow Actions -->
        <div class="space-y-6">
            <div class="bg-white border rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Order Details</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Part Number</dt>
                        <dd class="font-medium">{{ $order->part->part_no }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Name</dt>
                        <dd class="font-medium">{{ $order->part->part_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Process</dt>
                        <dd class="font-medium">{{ $order->process_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Machine</dt>
                        <dd class="font-medium">{{ $order->machine_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dies</dt>
                        <dd class="font-medium">{{ $order->die_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Planned Qty</dt>
                        <dd class="font-medium text-lg">{{ number_format($order->qty_planned) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Plan Date</dt>
                        <dd class="font-medium">{{ \Carbon\Carbon::parse($order->plan_date)->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd>
                            <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100">{{ strtoupper($order->status) }}</span>
                        </dd>
                    </div>
                    </dl>
                </div>

                <!-- Daily Planning Mapping -->
                @if($order->dailyPlanCell)
                <div class="bg-indigo-50 border-2 border-indigo-300 rounded-lg p-6 shadow-sm">
                    <h3 class="text-base font-semibold mb-4 text-indigo-900 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Daily Planning
                    </h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-indigo-600 font-medium">Production Line</dt>
                            <dd class="font-semibold text-indigo-900 text-base">{{ $order->dailyPlanCell->row->production_line }}</dd>
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
                            <dd class="font-semibold text-indigo-900 text-base">{{ $order->dailyPlanCell->qty ? number_format($order->dailyPlanCell->qty) : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-indigo-600 font-medium">Part No</dt>
                            <dd class="font-mono text-xs text-indigo-900 bg-white px-2 py-1 rounded border border-indigo-200">{{ $order->dailyPlanCell->row->part_no }}</dd>
                        </div>
                        @if($order->dailyPlanCell->row->plan)
                        <div class="pt-2 border-t border-indigo-200">
                            <dt class="text-indigo-600 font-medium mb-1">Plan Period</dt>
                            <dd class="text-xs text-indigo-900">
                                {{ \Carbon\Carbon::parse($order->dailyPlanCell->row->plan->date_from)->format('d M Y') }}
                                →
                                {{ \Carbon\Carbon::parse($order->dailyPlanCell->row->plan->date_to)->format('d M Y') }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
                @else
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-amber-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h3 class="text-xs font-semibold text-amber-900">No Daily Planning</h3>
                            <p class="text-xs text-amber-700 mt-1">Not linked to daily planning outgoing.</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($order->mrp_generated)
                    <div class="bg-slate-50 border rounded-lg p-4 space-y-2 mt-3 text-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">MRP Source</div>
                        <div class="text-sm text-slate-700">
                            <span class="font-semibold">Period:</span>
                            {{ $order->mrp_period ?? '—' }}
                        </div>
                        <div class="text-sm text-slate-700">
                            <span class="font-semibold">Run at:</span>
                            {{ $order->mrpRun?->run_at?->format('d M Y H:i') ?? 'Pending' }}
                        </div>
                        <div>
                            <a href="{{ route('planning.mrp.history', ['period' => $order->mrp_period]) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                Lihat riwayat MRP
                            </a>
                        </div>
                    </div>
                @endif

            <!-- Workflow Actions -->
            <div class="bg-white border rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Workflow Actions</h3>

                @if($order->status == 'planned')
                    <div class="p-4 bg-slate-50 rounded border mb-4">
                        <h4 class="font-medium mb-2">0. Work Order &amp; Kanban Release</h4>
                        <p class="text-sm text-gray-600 mb-3">Release work order to Kanban.</p>
                        <form action="{{ route('production.orders.release-kanban', $order) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-slate-900 text-white rounded hover:bg-slate-800">
                                Release Kanban
                            </button>
                        </form>
                    </div>
                @endif

                @if($order->status == 'kanban_released' || $order->status == 'material_hold' || $order->status == 'resource_hold')
                    <div class="p-4 bg-slate-50 rounded border mb-4">
                        <h4 class="font-medium mb-2">1. Material Availability</h4>
                        <p class="text-sm text-gray-600 mb-3">Check if components are available in inventory.</p>
                        <form action="{{ route('production.orders.check-material', $order) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Check Material
                            </button>
                        </form>
                    </div>
                @endif

                @if($order->workflow_stage == 'final_inspection')
                    <div class="p-4 bg-slate-50 rounded border mb-4">
                        <h4 class="font-medium mb-2">4. Final Inspection</h4>
                        <p class="text-sm text-gray-600">Complete Final Inspection, then do Kanban Update.</p>
                    </div>
                @endif

                @if($order->workflow_stage == 'kanban_update')
                    <div class="p-4 bg-slate-50 rounded border mb-4">
                        <h4 class="font-medium mb-2">5. Kanban Update → Inventory</h4>
                        <form action="{{ route('production.orders.kanban-update', $order) }}" method="POST" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Qty Good</label>
                                    <input type="number" step="0.0001" min="0" name="qty_good" value="{{ old('qty_good', $order->qty_actual > 0 ? $order->qty_actual : $order->qty_planned) }}" class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Qty NG</label>
                                    <input type="number" step="0.0001" min="0" name="qty_ng" value="{{ old('qty_ng', $order->qty_ng ?? 0) }}" class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Post Kanban &amp; Update Inventory
                            </button>
                        </form>
                    </div>
                @endif

                @if($order->status == 'released')
                    <div class="p-4 bg-slate-50 rounded border mb-4">
                        <h4 class="font-medium mb-2">2. Start Production</h4>
                        <p class="text-sm text-gray-600 mb-3">Materials Reserved. Determine to start production.</p>
                        <form action="{{ route('production.orders.start', $order) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Start Production
                            </button>
                        </form>
                    </div>
                @endif

                @if($order->status == 'in_production')
                     <div class="p-4 bg-slate-50 rounded border mb-4">
                         <h4 class="font-medium mb-2">3. Production Active</h4>
                         <p class="text-sm text-gray-600 mb-2">Current Stage: <strong>{{ strtoupper(str_replace('_', ' ', $order->workflow_stage)) }}</strong></p>
                         
                         <div class="space-y-2">
                             <p class="text-xs text-gray-500">Complete all inspections before finishing.</p>
                             <form action="{{ route('production.orders.finish', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="return confirm('Ensure all inspections are passed. Continue?');">
                                    Finish Production (Go to Final Inspection)
                                </button>
                            </form>
                         </div>
                     </div>
                @endif
                
                @if($order->status == 'completed')
                    <div class="p-4 bg-green-50 rounded border border-green-200">
                         <h4 class="font-medium text-green-800 mb-2">Production Completed</h4>
                         <p class="text-sm text-green-600">Finished at {{ $order->end_time }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Inspections & History -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Inspections -->
            <div class="bg-white border rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Inspections</h3>
                    @if($order->status == 'in_production')
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                + Add Inspection
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20 border">
                                <form action="{{ route('production.inspections.store', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" name="type" value="first_article" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100">First Article</button>
                                    <button type="submit" name="type" value="in_process" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100">In-Process</button>
                                    <button type="submit" name="type" value="final" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100">Final Inspection</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="divide-y">
                    @forelse($order->inspections as $inspection)
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-gray-100 border text-gray-700 mb-1">
                                        {{ strtoupper(str_replace('_', ' ', $inspection->type)) }}
                                    </span>
                                    <div class="text-xs text-gray-500">
                                        Created: {{ $inspection->created_at->format('d M H:i') }}
                                    </div>
                                </div>
                                <div>
                                    @if($inspection->status == 'pending')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-bold">PENDING</span>
                                    @elseif($inspection->status == 'pass')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-bold">PASS</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-bold">FAIL</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($inspection->status == 'pending')
                                <form action="{{ route('production.inspections.update', $inspection) }}" method="POST" class="bg-gray-50 p-3 rounded border">
                                    @csrf
                                    @method('PUT')
                                    <p class="text-sm font-medium mb-2">Record Result</p>
                                    <input type="text" name="remarks" placeholder="Remarks / Measurements" class="w-full text-sm rounded border-gray-300 mb-2">
                                    <div class="flex gap-2">
                                        <button type="submit" name="status" value="pass" class="flex-1 bg-green-600 text-white text-xs py-2 rounded hover:bg-green-700">Pass</button>
                                        <button type="submit" name="status" value="fail" class="flex-1 bg-red-600 text-white text-xs py-2 rounded hover:bg-red-700">Fail</button>
                                    </div>
                                </form>
                            @else
                                <div class="text-sm space-y-1">
                                    <p><span class="text-gray-500">Inspector:</span> {{ $inspection->inspector->name ?? 'Unknown' }}</p>
                                    <p><span class="text-gray-500">Remarks:</span> {{ $inspection->remarks ?: '-' }}</p>
                                    <p><span class="text-gray-500">Time:</span> {{ $inspection->inspected_at->format('d M H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-center text-gray-500 italic">No inspections recorded yet.</div>
                    @endforelse
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>
