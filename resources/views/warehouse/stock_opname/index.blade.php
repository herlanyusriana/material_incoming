@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border border-slate-200">
                <div class="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Stock Opname Sessions</h1>
                        <p class="text-slate-500 mt-1">Manage warehouse counting sessions and stock reconciliation.</p>
                    </div>
                    <div>
                        <button onclick="document.getElementById('createSessionModal').showModal()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 transition ease-in-out duration-150 shadow-sm shadow-indigo-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Start New Session
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Session Info</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Started At</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Created By</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($sessions as $session)
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900">{{ $session->session_no }}</span>
                                            <span class="text-xs text-slate-500">{{ $session->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                'OPEN' => 'bg-green-100 text-green-700 border-green-200',
                                                'CLOSED' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'ADJUSTED' => 'bg-blue-100 text-blue-700 border-blue-200',
                                            ];
                                        @endphp
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$session->status] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $session->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $session->start_date?->format('d M Y H:i') ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $session->creator?->name ?: 'System' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('warehouse.stock-opname.show', $session) }}"
                                                class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                                                title="View Details">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>

                                            @if($session->status !== 'ADJUSTED')
                                                <form action="{{ route('warehouse.stock-opname.destroy', $session) }}" method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete this session?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                                        title="Delete">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-lg font-medium text-slate-600 text-center">No Stock Opname sessions
                                                found.</p>
                                            <p class="text-sm text-slate-400 mt-1">Click "Start New Session" to begin counting.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($sessions->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                        {{ $sessions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Create Session Modal -->
    <dialog id="createSessionModal" class="modal p-0 rounded-xl shadow-2xl border-0">
        <div class="bg-white w-[400px]">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900">New Stock Opname</h3>
                <button onclick="document.getElementById('createSessionModal').close()"
                    class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('warehouse.stock-opname.store') }}" method="POST" class="p-6">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Session Name</label>
                    <input type="text" name="name" required placeholder="e.g. Monthly Raw Material Oct"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
                    <p class="text-xs text-slate-400 mt-2 italic">A Session Number will be automatically generated.</p>
                </div>
                <div class="flex gap-3 mt-8">
                    <button type="button" onclick="document.getElementById('createSessionModal').close()"
                        class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow-sm transition-all">
                        Create Session
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <style>
        .modal::backdrop {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
        }
    </style>
@endsection