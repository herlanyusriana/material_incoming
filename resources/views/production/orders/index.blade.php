<x-app-layout>
    <x-slot name="header">
        Production Orders
    </x-slot>

    <div x-data="{ 
        slideOverOpen: false, 
        selectedOrder: null,
        isLoading: false,
        slideOverContent: '',
        
        async openSlideOver(url) {
            this.slideOverOpen = true;
            this.isLoading = true;
            this.slideOverContent = ''; // Clear previous content
            
            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const html = await response.text();
                this.slideOverContent = html;
            } catch (error) {
                console.error('Error fetching details:', error);
                this.slideOverContent = '<div class=\'p-4 text-red-500\'>Error loading details.</div>';
            } finally {
                this.isLoading = false;
            }
        }
    }" @keydown.window.escape="slideOverOpen = false" class="relative z-10">
        
        <!-- Main Content -->
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-slate-800">Order List</h2>
                <a href="{{ route('production.orders.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                    New Production Order
                </a>
            </div>
    
            <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Order #</th>
                            <th class="px-6 py-4 font-semibold">Part</th>
                            <th class="px-6 py-4 font-semibold">Plan Date</th>
                            <th class="px-6 py-4 font-semibold">Qty</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Stage</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $order)
                            <tr class="hover:bg-slate-50 cursor-pointer transition-colors" @click="openSlideOver('{{ route('production.orders.show', $order) }}')">
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $order->production_order_number }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $order->part->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->part->part_name }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                                <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($order->qty_planned) }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $colors = [
                                            'planned' => 'bg-slate-100 text-slate-700',
                                            'released' => 'bg-indigo-100 text-indigo-700',
                                            'material_hold' => 'bg-red-100 text-red-700',
                                            'in_production' => 'bg-blue-100 text-blue-700',
                                            'completed' => 'bg-emerald-100 text-emerald-700',
                                            'cancelled' => 'bg-slate-200 text-slate-500', 
                                        ];
                                        $class = $colors[$order->status] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $class }}">
                                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                    {{ strtoupper(str_replace('_', ' ', $order->workflow_stage)) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase tracking-wide" @click.stop="openSlideOver('{{ route('production.orders.show', $order) }}')">
                                        Quick View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                                    No production orders found. Create one to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t bg-slate-50">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

        <!-- Slide-over -->
        <div class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-show="slideOverOpen" style="display: none;">
             <!-- Background backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 x-show="slideOverOpen"
                 x-transition:enter="ease-in-out duration-500"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-500"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="slideOverOpen = false"></div>
        
            <div class="fixed inset-0 overflow-hidden pointer-events-none">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div class="pointer-events-auto w-screen max-w-md"
                             x-show="slideOverOpen"
                             x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                             x-transition:enter-start="translate-x-full"
                             x-transition:enter-end="translate-x-0"
                             x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                             x-transition:leave-start="translate-x-0"
                             x-transition:leave-end="translate-x-full">
                            
                            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                                <div class="px-4 py-6 sm:px-6 bg-slate-50 border-b">
                                    <div class="flex items-start justify-between">
                                        <h2 class="text-lg font-semibold text-slate-900" id="slide-over-title">Order Details</h2>
                                        <div class="ml-3 flex h-7 items-center">
                                            <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="slideOverOpen = false">
                                                <span class="sr-only">Close panel</span>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative flex-1 px-4 py-6 sm:px-6">
                                    <!-- Content -->
                                    <div x-show="isLoading" class="flex justify-center py-12">
                                        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <div x-show="!isLoading" x-html="slideOverContent"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No production orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
