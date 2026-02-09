@extends('layouts.app')

@section('header')
    Substitute Mapping (Alternative Parts)
@endsection

@section('content')
    <div class="space-y-6">
        {{-- Header Actions --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500">Manage alternative component parts for BOMs</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button onclick="document.getElementById('importModal').showModal()"
                    class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import
                </button>
                <a href="{{ route('planning.boms.substitutes.export') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export All
                </a>
                <form action="{{ route('planning.boms.substitutes.truncate') }}" method="POST"
                    onsubmit="return confirm('WARNING: This will delete ALL substitute records. BOM GCI (Main BOM) will NOT be affected. Continue?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-200 hover:bg-red-100 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear All (Reset)
                    </button>
                </form>
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all">
                        Download Template
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false"
                        class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                        x-cloak>
                        <div class="py-1">
                            <a href="{{ route('planning.boms.substitutes.template') }}"
                                class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Standard Template (with
                                FG)</a>
                            <a href="{{ route('planning.boms.substitutes.template-mapping') }}"
                                class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Component-to-Sub Mapping
                                Template</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form action="{{ route('planning.boms.substitutes.index') }}" method="GET"
                class="flex flex-col gap-4 md:flex-row md:items-end">
                <div class="flex-1">
                    <label for="q" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Search
                        Part No</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="q" id="q" value="{{ $q }}"
                            class="block w-full rounded-xl border-slate-200 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search FG No, Component No, or Substitute No...">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-all">
                        Filter
                    </button>
                    @if($q)
                        <a href="{{ route('planning.boms.substitutes.index') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-200 transition-all">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4 text-left">FG Part (BOM)</th>
                            <th class="px-6 py-4 text-left">Primary Component</th>
                            <th class="px-6 py-4 text-left">Substitute Part</th>
                            <th class="px-6 py-4 text-center">Ratio</th>
                            <th class="px-6 py-4 text-center">Priority</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($substitutes as $sub)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-mono text-sm font-bold text-slate-900">{{ $sub->bomItem?->bom?->part?->part_no ?? '-' }}</span>
                                        <span
                                            class="text-xs text-slate-500 truncate max-w-[200px]">{{ $sub->bomItem?->bom?->part?->part_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-mono text-sm font-bold text-slate-700">{{ $sub->bomItem?->componentPart?->part_no ?? $sub->bomItem?->component_part_no ?? '-' }}</span>
                                        <span
                                            class="text-xs text-slate-500 truncate max-w-[200px]">{{ $sub->bomItem?->componentPart?->part_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-mono text-sm font-bold text-indigo-600">{{ $sub->part?->part_no ?? $sub->substitute_part_no }}</span>
                                        <span
                                            class="text-xs text-slate-500 truncate max-w-[200px]">{{ $sub->part?->part_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm font-medium text-slate-600">
                                    {{ number_format($sub->ratio, 4) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">
                                        #{{ $sub->priority ?? 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($sub->status === 'active')
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 text-right">
                                        <form action="{{ route('planning.bom-item-substitutes.destroy', $sub) }}" method="POST"
                                            onsubmit="return confirm('Remove this substitute link? BOM item remains.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="group p-2 text-slate-400 hover:text-red-600 transition-colors"
                                                title="Remove Substitute">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500 italic">
                                    No substitutes found matching search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($substitutes->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $substitutes->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Import Modal --}}
    <dialog id="importModal" class="rounded-2xl border-none shadow-2xl p-0 transition-all duration-300 w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-slate-900">Import Substitutes</h3>
                <button onclick="this.closest('dialog').close()"
                    class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-xs text-amber-800">
                        <p class="font-bold">Important Notice</p>
                        <p class="mt-1">This import <strong>will NOT update</strong> existing substitute records to protect
                            BOM integrity. It only adds new substitute links.</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('planning.boms.substitutes.import') }}" method="POST" enctype="multipart/form-data"
                class="space-y-4 shadow-none">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Select Excel
                        File</label>
                    <input type="file" name="file" required
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 shadow-none border border-slate-200 rounded-xl p-1">
                </div>

                <div class="flex items-center gap-2 px-1">
                    <input type="checkbox" name="auto_create_parts" id="auto_create_parts" value="1"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="auto_create_parts" class="text-sm text-slate-600">Auto-register missing substitute
                        parts</label>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="button" onclick="this.closest('dialog').close()"
                        class="flex-1 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all">
                        Start Import
                    </button>
                </div>
            </form>
        </div>
    </dialog>
@endsection