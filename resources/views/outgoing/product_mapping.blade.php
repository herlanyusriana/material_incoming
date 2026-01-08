@extends('outgoing.layout')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="text-lg font-semibold text-slate-900">Customer Product Mapping</div>
        <div class="mt-2 text-sm text-slate-600">
            Mapping customer part → Part GCI (bisa lebih dari 1 komponen), dipakai untuk translasi planning dan customer PO.
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('planning.customer-parts.index') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Buka Customer Part Mapping
            </a>
        </div>
    </div>
@endsection

