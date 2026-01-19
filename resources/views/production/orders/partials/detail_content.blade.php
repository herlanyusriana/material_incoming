<div class="grid grid-cols-1 gap-6">
    <!-- Order Details -->
    <div class="space-y-6">
        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold mb-4 text-gray-900">Order Details</h3>
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

        <!-- Workflow Actions -->
        @if($order->status != 'completed' && $order->status != 'cancelled')
        <div class="bg-white border rounded-lg shadow-sm p-6 relative overflow-hidden">
             
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            
            @if($order->status == 'planned' || $order->status == 'material_hold')
                <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                    <h4 class="font-medium text-indigo-900 mb-2">Material Check Required</h4>
                    <p class="text-sm text-indigo-700 mb-4">Validate BOM materials against inventory before releasing.</p>
                    <form action="{{ route('production.orders.check-material', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Check Availability
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
