@extends('outgoing.layout')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="text-lg font-semibold text-slate-900">Customers Daily Planning</div>
        <div class="mt-2 text-sm text-slate-600">
            Input planning customer via Excel (staging) dan lakukan mapping sebelum generate forecast.
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('planning.planning-imports.index') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Buka Customer Planning (Excel)
            </a>
        </div>
    </div>
@endsection

