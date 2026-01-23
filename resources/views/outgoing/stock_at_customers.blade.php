@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center text-white font-black">
                        SC
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Stock at Customers</div>
                        <div class="mt-1 text-sm text-slate-600">Consignment / stock record per customer & part (kolom tanggal 1..31)</div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end">
                    <form method="GET" action="{{ route('outgoing.stock-at-customers') }}" class="flex items-end gap-2">
                        <div>
                            <div class="text-xs font-semibold text-slate-500 mb-1">Period</div>
                            <input type="month" name="period" value="{{ $period }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                        </div>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('outgoing.stock-at-customers.template', ['period' => $period]) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Template
                        </a>
                        <a href="{{ route('outgoing.stock-at-customers.export', ['period' => $period]) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Export
                        </a>
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
                    <div class="text-lg font-black text-slate-900">Upload / Import</div>
                    <div class="mt-1 text-sm text-slate-600">Format kolom: customer, part_no, part_name, model, status, 1..31</div>
                </div>

                <form method="POST" action="{{ route('outgoing.stock-at-customers.import') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">File</div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                        Upload
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-700">
                    Data period <span class="font-black text-slate-900">{{ $period }}</span>
                </div>
                <div class="text-sm text-slate-500">
                    {{ $records->total() }} rows
                </div>
            </div>

            <div class="overflow-auto">
                <table class="min-w-max w-full text-sm">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Customer</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Part No</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Part Name</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Model</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Status</th>
                            @foreach($days as $d)
                                <th class="px-3 py-3 text-right font-bold text-slate-700 w-16">{{ $d }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $rec)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ $rec->customer?->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-black text-slate-900 whitespace-nowrap">{{ $rec->part_no }}</td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $rec->part_name ?: ($rec->part?->part_name ?? '') }}</td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $rec->model ?: ($rec->part?->model ?? '') }}</td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $rec->status ?? '' }}</td>
                                @foreach($days as $d)
                                    @php($v = (float) ($rec->{'day_'.$d} ?? 0))
                                    <td class="px-3 py-3 text-right text-slate-700 {{ $v > 0 ? 'font-semibold' : 'text-slate-400' }}">
                                        {{ $v > 0 ? number_format($v, 0) : '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + count($days) }}" class="px-6 py-12 text-center text-slate-500 italic">Belum ada data untuk period ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
