@props(['title', 'columns' => [], 'rows' => [], 'url' => null, 'urlLabel' => null])

<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
        @if ($url)
            <a href="{{ $url }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline">{{ $urlLabel ?? __('module.view_all') }}</a>
        @endif
    </div>

    @if (count($rows) === 0)
        <p class="px-4 py-6 text-center text-sm text-slate-400">{{ __('module.empty') }}</p>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($rows as $row)
                <li class="flex items-center gap-3 px-4 py-2.5">
                    @foreach ($columns as $i => $col)
                        @php($cell = $row[$col['key'] ?? $i] ?? null)
                        <span @if ($i === 0) class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700"
                            @elseif(($col['type'] ?? '') === 'badge')
                                class="shrink-0"
                            @else
                                class="shrink-0 text-right text-xs tabular-nums text-slate-500 sm:text-sm"
                            @endif>
                            @if (is_array($cell))
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $cell['class'] ?? 'bg-slate-100 text-slate-600' }}">{{ $cell['label'] ?? '—' }}</span>
                            @else
                                {{ $cell ?? '—' }}
                            @endif
                        </span>
                    @endforeach
                </li>
            @endforeach
        </ul>
    @endif
</div>
