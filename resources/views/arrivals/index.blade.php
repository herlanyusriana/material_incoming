<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Arrivals</h2>
                <p class="text-sm text-gray-500">Inbound shipments with pricing breakdowns.</p>
            </div>
            <a href="{{ route('arrivals.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 focus:outline-none transition">+ New Arrival</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($arrivals as $arrival)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $arrival->arrival_no }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $arrival->invoice_no }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $arrival->vendor->vendor_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $arrival->creator->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $arrival->invoice_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('arrivals.show', $arrival) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">No arrivals recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $arrivals->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
