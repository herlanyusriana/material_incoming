<x-app-layout>
    <x-slot name="header">
        Machine Management
    </x-slot>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Machine List</h3>
                        <p class="text-sm text-slate-600 mt-1">Manage production machines.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <input type="text" name="q" value="{{ $search }}" placeholder="Search machines..."
                                    class="w-full pl-9 pr-3 py-2 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                <span class="absolute left-3 top-2.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z" />
                                    </svg>
                                </span>
                            </div>
                            <select name="status" class="py-2 px-4 w-full sm:w-44 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">All Status</option>
                                <option value="active" @selected($status === 'active')>Active</option>
                                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                            </select>
                            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">Filter</button>
                        </form>
                        <a href="{{ route('machines.export') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                            </svg>
                            Export
                        </a>
                        <button type="button" onclick="document.getElementById('import-machine-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-6m0 0 3 3m-3-3-3 3m8-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Import
                        </button>
                        <a href="{{ route('machines.create') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Machine
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Code</th>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Group</th>
                                <th class="px-4 py-3 text-center font-semibold">Sort Order</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($machines as $machine)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 font-mono text-sm font-semibold text-slate-800">{{ $machine->code }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $machine->name }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $machine->group_name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-center text-slate-600">{{ $machine->sort_order }}</td>
                                    <td class="px-4 py-4 text-center">
                                        @if ($machine->is_active)
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-green-200">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('machines.edit', $machine) }}"
                                                class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit
                                            </a>
                                            <form action="{{ route('machines.destroy', $machine) }}" method="POST"
                                                onsubmit="return confirm('Hapus machine {{ $machine->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <p class="text-sm font-medium">No machines found</p>
                                            <p class="text-xs">Click "Add Machine" to create one.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($machines->hasPages())
                    <div class="pt-4">
                        {{ $machines->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-machine-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">Import Machines</h3>
                <button type="button" onclick="document.getElementById('import-machine-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('machines.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Upload Excel File</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-2 text-xs text-slate-500">Accepted formats: .xlsx, .xls (Max: 2MB)</p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-800 font-medium mb-1">Required Columns (exact names):</p>
                    <p class="text-xs text-blue-700">code, name, group_name, sort_order, is_active</p>
                    <p class="text-xs text-blue-600 mt-1">Tip: Export existing data to get the correct format</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('import-machine-modal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
