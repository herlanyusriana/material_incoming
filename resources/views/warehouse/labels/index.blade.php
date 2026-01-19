<x-app-layout>
    <x-slot name="header">
        Print Barcode Labels
    </x-slot>

    <div class="space-y-6">
        <!-- Search and Filter -->
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <form method="GET" class="flex gap-4">
                <input 
                    type="text" 
                    name="q" 
                    value="{{ request('q') }}" 
                    placeholder="Search part no or name..."
                    class="flex-1 rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                />
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Search
                </button>
            </form>
        </div>

        <!-- Parts List with Checkboxes -->
        <form method="POST" action="{{ route('warehouse.labels.bulk') }}" target="_blank">
            @csrf
            <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Select Parts to Print</h3>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        Print Selected Labels
                    </button>
                </div>

                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300">
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">Part No</th>
                            <th class="px-6 py-4 text-left font-semibold">Barcode</th>
                            <th class="px-6 py-4 text-left font-semibold">Part Name</th>
                            <th class="px-6 py-4 text-left font-semibold">Classification</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($parts as $part)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <input 
                                        type="checkbox" 
                                        name="part_ids[]" 
                                        value="{{ $part->id }}" 
                                        class="part-checkbox rounded border-slate-300"
                                    >
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $part->part_no }}</td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ $part->generateBarcode() }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $part->part_name }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $part->classification === 'FG' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $part->classification === 'WIP' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                        {{ $part->classification === 'RM' ? 'bg-blue-100 text-blue-700' : '' }}
                                    ">
                                        {{ $part->classification }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a 
                                        href="{{ route('warehouse.labels.part', $part) }}" 
                                        target="_blank"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase"
                                    >
                                        Print Single
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                    No parts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t bg-slate-50">
                    {{ $parts->links() }}
                </div>
            </div>
        </form>
    </div>

    <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.part-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</x-app-layout>
