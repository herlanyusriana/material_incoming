<x-app-layout>
    <x-slot name="header">
        Create Production Order
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white border rounded-lg shadow-sm p-6">
            <form action="{{ route('production.orders.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Production Order Number</label>
                    <input type="text" name="production_order_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Part (FG/WIP)</label>
                    <select id="part_select" name="gci_part_id" data-remote="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required></select>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const sel = document.getElementById('part_select');
                        if (window.initRemoteTomSelect) {
                            window.initRemoteTomSelect(sel, "{{ route('gci-parts.search') }}", {
                                placeholder: 'Search for part number or name...'
                            });
                        }
                    });
                </script>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Process</label>
                        <input type="text" name="process_name" value="{{ old('process_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="ex: PRESS / ASSY">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Machine</label>
                        <input type="text" name="machine_name" value="{{ old('machine_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="ex: LINE-01 / MACHINE-A">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Dies</label>
                    <input type="text" name="die_name" value="{{ old('die_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="ex: DIES-01">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Planned Quantity</label>
                    <input type="number" name="qty_planned" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan Date</label>
                    <input type="date" name="plan_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required value="{{ date('Y-m-d') }}">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('production.orders.index') }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
