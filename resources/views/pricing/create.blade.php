@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Add Pricing Master</h1>
            <p class="mt-1 text-sm text-slate-500">Input pricing baru secara terpusat untuk purchasing, OSP, subcon, selling, dan costing.</p>
        </div>
        <a href="{{ route('pricing.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to List</a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="max-w-4xl rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-lg font-bold text-slate-900">New Pricing Entry</h2>
        </div>
        <div class="p-6">
            @include('pricing._form')
        </div>
    </div>
</div>
@endsection
