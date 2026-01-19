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

            <!-- Workflow Actions -->
            <div class="bg-white border rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Workflow Actions</h3>
                
                @if($order->status == 'planned' || $order->status == 'material_hold')
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
                                    Finish Production
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
