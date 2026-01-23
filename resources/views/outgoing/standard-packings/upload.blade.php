@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('outgoing.standard-packings.index') }}"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Back
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Upload Standard Packing</h1>
            </div>
            <p class="mt-2 text-sm text-slate-500">Import Standard Packing data from Excel/CSV.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('outgoing.standard-packings.template') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 transition-all bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Template
            </a>
            <a href="{{ route('outgoing.standard-packings.export') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 transition-all bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Current
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Import File</h2>
                    <p class="mt-1 text-sm text-slate-500">Accepted: <span class="font-mono">.xlsx</span>, <span class="font-mono">.xls</span>, <span class="font-mono">.csv</span></p>
                </div>

                <form action="{{ route('outgoing.standard-packings.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <label class="block mb-2 text-sm font-medium text-slate-700">Select File</label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                            class="block w-full text-sm text-slate-500
                                file:mr-4 file:py-2.5 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100
                                transition-all
                            "/>
                        @error('file')
                            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('outgoing.standard-packings.index') }}"
                            class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 focus:ring-4 focus:ring-slate-100 transition-all">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-xl hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-100 shadow-sm transition-all">
                            Start Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Column Format</h2>
                    <p class="mt-1 text-sm text-slate-500">Use the template to avoid errors.</p>
                </div>
                <div class="p-6">
                    <div class="text-sm rounded-lg bg-blue-50 text-blue-700 p-4 border border-blue-100">
                        <div class="font-semibold mb-2">Required</div>
                        <ul class="list-disc list-inside space-y-1">
                            <li><span class="font-mono">part_no</span></li>
                            <li><span class="font-mono">qty</span></li>
                        </ul>
                        <div class="font-semibold mt-4 mb-2">Optional</div>
                        <ul class="list-disc list-inside space-y-1">
                            <li><span class="font-mono">customer</span> (recommended kalau part_no duplicate antar customer)</li>
                            <li><span class="font-mono">del_class</span> (default: <span class="font-mono">Main</span>)</li>
                            <li><span class="font-mono">trolley_type</span></li>
                            <li><span class="font-mono">uom</span> (default: <span class="font-mono">PCS</span>)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Notes</h2>
                </div>
                <div class="p-6 text-sm text-slate-600 space-y-2">
                    <div>- <span class="font-semibold">Part No</span> harus sudah ada di sistem (GCI Part).</div>
                    <div>- Import akan <span class="font-semibold">update/replace</span> data yang sama (<span class="font-mono">part_no</span> + <span class="font-mono">del_class</span>).</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

