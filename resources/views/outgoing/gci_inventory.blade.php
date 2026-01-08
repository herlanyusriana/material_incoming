@extends('outgoing.layout')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="text-lg font-semibold text-slate-900">GCI Inventory</div>
        <div class="mt-2 text-sm text-slate-600">
            Inventory on hand berasal dari receive (Good). Halaman ini mengarahkan ke modul inventory yang sudah ada.
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Buka Inventory Summary
            </a>
            <a href="{{ route('inventory.receives') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Buka Inventory Receives (Tag)
            </a>
        </div>
    </div>
@endsection

