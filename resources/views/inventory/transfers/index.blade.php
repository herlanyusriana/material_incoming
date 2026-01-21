<x-app-layout>
    <x-slot name="header">
        Inventory • Transfer History
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-slate-900">Transfer History</h2>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('inventory.transfers.auto-sync') }}" class="inline">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('Auto-sync all matching parts from Logistics to Production?')"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                                🔄 Auto-Sync
                            </button>
                        </form>
                        <a href="{{ route('inventory.transfers.create') }}"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                            + New Transfer
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">From
                                    (Logistics)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">To
                                    (Production)</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">Qty</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-700 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">By</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($transfers as $transfer)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        {{ $transfer->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $transfer->part->part_no }}
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $transfer->part->part_name_gci }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $transfer->gciPart->part_no }}
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $transfer->gciPart->part_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="text-sm font-bold text-indigo-600">{{ formatNumber($transfer->qty) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $transfer->transfer_type === 'auto' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ ucfirst($transfer->transfer_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $transfer->creator->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $transfer->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                        No transfers yet. Create your first transfer to bridge inventory.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transfers->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>