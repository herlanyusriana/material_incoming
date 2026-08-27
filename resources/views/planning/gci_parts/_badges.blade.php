@php
    $classColors = ['FG' => 'bg-blue-100 text-blue-800 border-blue-200', 'WIP' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'RM' => 'bg-green-100 text-green-800 border-green-200'];
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border {{ $classColors[$p->classification] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
    {{ $p->classification }}
</span>
