@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-2xl md:text-3xl font-black text-slate-900">Trucks</div>
                    <div class="mt-1 text-sm text-slate-600">Master truck untuk Delivery Plan</div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <form method="GET" action="{{ route('outgoing.trucks.index') }}" class="flex items-end gap-2">
                        <div>
                            <div class="text-xs font-semibold text-slate-500 mb-1">Search</div>
                            <input name="q" value="{{ $q }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700" placeholder="plate/type/capacity">
                        </div>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Search</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('outgoing.trucks.template') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Template</a>
                        <a href="{{ route('outgoing.trucks.export') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Export</a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {!! session('error') !!}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-lg font-black text-slate-900">Add Truck</div>
                    <div class="mt-1 text-sm text-slate-600">Status: available / in-use / maintenance</div>
                </div>

                <form method="POST" action="{{ route('outgoing.trucks.import') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    @csrf
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Import file</div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Import</button>
                </form>
            </div>

            <form method="POST" action="{{ route('outgoing.trucks.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                @csrf
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Plate No</div>
                    <input name="plate_no" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required placeholder="B 1234 XX">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Type</div>
                    <input name="type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Box Truck">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Capacity</div>
                    <input name="capacity" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="5 Ton">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Status</div>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                        <option value="available">available</option>
                        <option value="in-use">in-use</option>
                        <option value="maintenance">maintenance</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Save</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-700">
                    {{ $trucks->total() }} trucks
                </div>
            </div>

            <div class="overflow-auto">
                <table class="min-w-max w-full text-sm">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Plate No</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Type</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Capacity</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($trucks as $truck)
                            <tr class="hover:bg-slate-50">
                                <form method="POST" action="{{ route('outgoing.trucks.update', $truck) }}">
                                    @csrf
                                    @method('PUT')
                                    <td class="px-4 py-3 font-black text-slate-900 whitespace-nowrap">
                                        <input name="plate_no" value="{{ $truck->plate_no }}" class="w-40 rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="type" value="{{ $truck->type }}" class="w-44 rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="capacity" value="{{ $truck->capacity }}" class="w-28 rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="status" class="rounded-lg border border-slate-200 px-2 py-1 text-sm bg-white">
                                            <option value="available" @selected($truck->status === 'available')>available</option>
                                            <option value="in-use" @selected($truck->status === 'in-use')>in-use</option>
                                            <option value="maintenance" @selected($truck->status === 'maintenance')>maintenance</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">Update</button>
                                </form>
                                <form method="POST" action="{{ route('outgoing.trucks.destroy', $truck) }}" class="inline" onsubmit="return confirm('Delete truck {{ $truck->plate_no }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                                    </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No trucks.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($trucks->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $trucks->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

