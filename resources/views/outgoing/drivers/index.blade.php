@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-2xl md:text-3xl font-black text-slate-900">Drivers</div>
                    <div class="mt-1 text-sm text-slate-600">Master driver untuk Delivery Plan</div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <form method="GET" action="{{ route('outgoing.drivers.index') }}" class="flex items-end gap-2">
                        <div>
                            <div class="text-xs font-semibold text-slate-500 mb-1">Search</div>
                            <input name="q" value="{{ $q }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700" placeholder="name/phone/license">
                        </div>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Search</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('outgoing.drivers.template') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Template</a>
                        <a href="{{ route('outgoing.drivers.export') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Export</a>
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
                    <div class="text-lg font-black text-slate-900">Add Driver</div>
                    <div class="mt-1 text-sm text-slate-600">Status: available / on-delivery / off</div>
                </div>

                <form method="POST" action="{{ route('outgoing.drivers.import') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    @csrf
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Import file</div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Import</button>
                </form>
            </div>

            <form method="POST" action="{{ route('outgoing.drivers.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                @csrf
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Name</div>
                    <input name="name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required placeholder="Nama Driver">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Phone</div>
                    <input name="phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="08xxxx">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">License</div>
                    <input name="license_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="SIM B1">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Status</div>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                        <option value="available">available</option>
                        <option value="on-delivery">on-delivery</option>
                        <option value="off">off</option>
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
                    {{ $drivers->total() }} drivers
                </div>
            </div>

            <div class="overflow-auto">
                <table class="min-w-max w-full text-sm">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Name</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Phone</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">License</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($drivers as $driver)
                            <tr class="hover:bg-slate-50">
                                <form method="POST" action="{{ route('outgoing.drivers.update', $driver) }}">
                                    @csrf
                                    @method('PUT')
                                    <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">
                                        <input name="name" value="{{ $driver->name }}" class="w-48 rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="phone" value="{{ $driver->phone }}" class="w-40 rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="license_type" value="{{ $driver->license_type }}" class="w-28 rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="status" class="rounded-lg border border-slate-200 px-2 py-1 text-sm bg-white">
                                            <option value="available" @selected($driver->status === 'available')>available</option>
                                            <option value="on-delivery" @selected($driver->status === 'on-delivery')>on-delivery</option>
                                            <option value="off" @selected($driver->status === 'off')>off</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">Update</button>
                                </form>
                                <form method="POST" action="{{ route('outgoing.drivers.destroy', $driver) }}" class="inline" onsubmit="return confirm('Delete driver {{ $driver->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                                    </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No drivers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($drivers->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $drivers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

