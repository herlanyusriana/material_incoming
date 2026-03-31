@extends('outgoing.layout')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <div
                    class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center text-white font-black">
                    SC
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-black text-slate-900">Stock at Customers</div>
                    <div class="mt-1 text-sm text-slate-600">Consignment / stock record per customer & part (7 hari)
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end">
                <form method="GET" action="{{ route('outgoing.stock-at-customers') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Start Date</div>
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Customer</div>
                        <select name="customer_id"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 min-w-[200px]">
                            <option value="">All Customer</option>
                            @foreach(($customers ?? collect()) as $customer)
                                <option value="{{ $customer->id }}" @selected((int) $customerId === (int) $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Search</div>
                        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Part no / part name / model / customer"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 min-w-[280px]">
                    </div>
                    <button
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
                    <a href="{{ route('outgoing.stock-at-customers') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
                </form>

                <div class="flex items-center gap-2">
                    <a href="{{ route('outgoing.stock-at-customers.template', ['start_date' => $startDate->format('Y-m-d')]) }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Template
                    </a>
                    <a href="{{ route('outgoing.stock-at-customers.export', ['start_date' => $startDate->format('Y-m-d')]) }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Qty</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format((float) ($totalQty ?? 0), 0) }}</div>
            <div class="mt-1 text-sm text-slate-500">Akumulasi stock customer pada range 7 hari</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Customers</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format((int) ($activeCustomers ?? 0)) }}</div>
            <div class="mt-1 text-sm text-slate-500">Customer yang punya data pada filter saat ini</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tracked Parts</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format((int) ($trackedParts ?? 0)) }}</div>
            <div class="mt-1 text-sm text-slate-500">Part yang terinput dari APK / import</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="text-lg font-black text-slate-900">Recap by Customer</div>
                <div class="mt-1 text-sm text-slate-500">Top customer berdasarkan total qty pada filter saat ini</div>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse(($recapByCustomer ?? collect()) as $row)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $row['customer_name'] }}</div>
                            <div class="text-xs text-slate-500">{{ number_format((int) $row['part_count']) }} part tracked</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-slate-900">{{ number_format((float) $row['total_qty'], 0) }}</div>
                            <div class="text-xs text-slate-400">qty total</div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-sm italic text-slate-500">Belum ada data recap customer.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="text-lg font-black text-slate-900">Recap by Part</div>
                <div class="mt-1 text-sm text-slate-500">Top part berdasarkan total qty pada filter saat ini</div>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse(($recapByPart ?? collect()) as $row)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <div class="font-mono font-bold text-slate-900">{{ $row['part_no'] }}</div>
                            <div class="text-sm text-slate-600">{{ $row['part_name'] ?: '-' }}</div>
                            <div class="text-xs text-slate-500">{{ number_format((int) $row['customer_count']) }} customer</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-slate-900">{{ number_format((float) $row['total_qty'], 0) }}</div>
                            <div class="text-xs text-slate-400">qty total</div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-sm italic text-slate-500">Belum ada data recap part.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-lg font-black text-slate-900">Upload / Import</div>
                <div class="mt-1 text-sm text-slate-600">Format kolom: customer, part_no, part_name, model, status,
                    tanggal1..tanggal7</div>
            </div>

            <form method="POST" action="{{ route('outgoing.stock-at-customers.import') }}" enctype="multipart/form-data"
                class="flex flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">File</div>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv"
                        class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
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
                Data <span class="font-black text-slate-900">{{ $startDate->format('d M Y') }}</span>
                &mdash;
                <span class="font-black text-slate-900">{{ $endDate->format('d M Y') }}</span>
            </div>
            <div class="text-sm text-slate-500">
                {{ $records->total() }} rows
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="sticky top-0 bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 min-w-[120px]">Customer</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 min-w-[90px]">Part No</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 min-w-[140px]">Part Name</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 min-w-[100px]">Model</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 min-w-[80px]">Status</th>
                        @foreach($days as $dateKey => $dateLabel)
                            <th class="px-3 py-3 text-right font-bold text-slate-700 min-w-[60px]">{{ $dateLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $rec)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">
                            {{ $rec->customer?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 font-black text-slate-900 whitespace-nowrap">{{ $rec->part_no }}</td>
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                            {{ $rec->part_name ?: ($rec->part?->part_name ?? '') }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $rec->model ?: ($rec->part?->model ?? '') }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $rec->status ?? '' }}</td>
                        @foreach($days as $dateKey => $dateLabel)
                        @php($v = (float) ($rec->{$dateKey} ?? 0))
                        <td
                            class="px-3 py-3 text-right text-slate-700 {{ $v > 0 ? 'font-semibold' : 'text-slate-400' }}">
                            {{ $v > 0 ? number_format($v, 0) : '-' }}
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 5 + count($days) }}" class="px-6 py-12 text-center text-slate-500 italic">Belum
                            ada data untuk periode ini.</td>
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
