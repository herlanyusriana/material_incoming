@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Standard Packing</h1>
            <p class="mt-1 text-sm text-slate-500">Master Data for Outgoing Packing Configuration</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('outgoing.standard-packings.create') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 transition-all bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Upload Page
            </a>
            <!-- Import Button -->
            <button type="button" 
                onclick="document.getElementById('importModal').showModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white transition-all bg-emerald-600 border border-transparent rounded-lg shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Excel
            </button>
             <!-- Export Button -->
            <a href="{{ route('outgoing.standard-packings.export') }}" 
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 transition-all bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 bg-white border shadow-sm rounded-xl border-slate-200">
        <form action="{{ route('outgoing.standard-packings.index') }}" method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="block mb-1 text-xs font-medium text-slate-500 uppercase">Search Part</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                        class="block w-full rounded-lg border-slate-200 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500" 
                        placeholder="Search Part No / Name...">
                </div>
            </div>
            <div class="w-full sm:w-64">
                <label for="customer_id" class="block mb-1 text-xs font-medium text-slate-500 uppercase">Customer</label>
                <select name="customer_id" id="customer_id" 
                    class="block w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Customers</option>
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>
                            {{ $cust->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Filter
            </button>
             @if(request()->anyFilled(['search', 'customer_id']))
                <a href="{{ route('outgoing.standard-packings.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-left uppercase text-slate-500">Customer</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-left uppercase text-slate-500">Part Number</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-left uppercase text-slate-500">Part Name</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-left uppercase text-slate-500">Model</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-left uppercase text-slate-500">Del Class</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-right uppercase text-slate-500">Packing Qty</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-left uppercase text-slate-500">UOM</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-center uppercase text-slate-500">BS Type</th>
                         <th scope="col" class="px-6 py-3 text-xs font-semibold tracking-wider text-center uppercase text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($packings as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $item->part->customer->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $item->part->part_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $item->part->part_name }}</td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $item->part->model }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $item->delivery_class }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900 text-right">{{ $item->packing_qty + 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $item->uom }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 text-center">{{ $item->trolley_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <form action="{{ route('outgoing.standard-packings.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <p class="text-base font-medium text-slate-900">No standard packing found</p>
                                    <p class="mt-1 text-sm text-slate-500">Please import data or adjust your filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $packings->render() }}
        </div>
    </div>
</div>

<!-- Import Modal -->
<dialog id="importModal" class="modal p-0 rounded-2xl shadow-2xl backdrop:bg-slate-900/50 w-full max-w-md">
    <div class="bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Import Standard Packing</h3>
            <button onclick="document.getElementById('importModal').close()" class="p-2 transition-colors rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <form action="{{ route('outgoing.standard-packings.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <div class="space-y-4">
                <div class="text-sm rounded-lg bg-blue-50 text-blue-700 p-4 border border-blue-100">
                    <strong class="block mb-1 font-semibold">Instructions:</strong>
                    <ul class="list-disc list-inside space-y-1 text-blue-600">
                        <li>Ensure Part No exists in system</li>
                        <li>Format: <code>customer</code>, <code>part_no</code>, <code>qty</code>, <code>del_class</code>, <code>trolley_type</code></li>
                        <li>
                            Download template:
                            <a href="{{ route('outgoing.standard-packings.template') }}" class="underline font-semibold">standard_packings_template.xlsx</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">Select Excel File</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                        class="block w-full text-sm text-slate-500
                            file:mr-4 file:py-2.5 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100
                            transition-all
                        "/>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('importModal').close()" 
                    class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 focus:ring-4 focus:ring-slate-100 transition-all">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-xl hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-100 shadow-sm transition-all">
                    Start Import
                </button>
            </div>
        </form>
    </div>
</dialog>
@endsection
