@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">Delivery Plan</h1>
                    <p class="mt-1 text-sm text-slate-600">Sheet view (by delivery class + sequence).</p>
                </div>

                <form method="GET" action="{{ route('outgoing.delivery-plan') }}" class="flex items-end gap-2">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Date</div>
                        <input type="date" name="date" value="{{ $selectedDate }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                    </div>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-auto">
                <table class="min-w-max w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200 w-10">No</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">Classification</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">Part Name</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">Part Number</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-20">Plan</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-28">Stock at Customer</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-20">Balance</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200 w-24">Duedate</th>
                            <th colspan="{{ count($sequences) }}" class="px-2 py-3 text-center font-bold text-slate-700 border-r border-slate-200">Sequence</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 w-20">Remain</th>
                        </tr>
                        <tr>
                            @foreach($sequences as $seq)
                                <th class="px-2 py-2 text-center font-bold text-slate-700 border-r border-slate-200 w-10">{{ $seq }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php($no = 0)
                        @forelse(($groups ?? []) as $class => $rows)
                            <tr class="bg-slate-100">
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 font-black text-slate-800 border-r border-slate-200" colspan="1">{{ $class }}</td>
                                <td class="px-2 py-2 border-r border-slate-200" colspan="3"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                @foreach($sequences as $seq)
                                    <td class="px-2 py-2 border-r border-slate-200"></td>
                                @endforeach
                                <td class="px-2 py-2"></td>
                            </tr>
                            @foreach($rows as $r)
                                @php($no++)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-600 font-semibold">{{ $no }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->delivery_class }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->part_name }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 font-mono text-indigo-700 font-bold">{{ $r->part_no }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900">{{ number_format((float) $r->plan_total, 0) }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right text-slate-700">{{ number_format((float) $r->stock_at_customer, 0) }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900">{{ number_format((float) $r->balance, 0) }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->due_date?->format('Y-m-d') }}</td>
                                    @foreach($sequences as $seq)
                                        @php($v = (float) (($r->per_seq[$seq] ?? 0) ?: 0))
                                        <td class="px-2 py-2 border-r border-slate-200 text-center {{ $v > 0 ? 'font-bold text-slate-900' : 'text-slate-400' }}">
                                            {{ $v > 0 ? number_format($v, 0) : '' }}
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 text-right font-bold text-slate-900">{{ number_format((float) $r->remain, 0) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ 9 + count($sequences) }}" class="px-6 py-12 text-center text-slate-500 italic">
                                    No data for selected date.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

