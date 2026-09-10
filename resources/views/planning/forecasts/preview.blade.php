<x-app-layout>
    <x-slot name="header">
        {{ __('planning.forecasts.preview.header') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-slate-900">{{ __('planning.forecasts.preview.title', ['no' => $document->document_no]) }}</div>
                        <div class="text-sm text-slate-500">
                            {{ __('planning.forecasts.preview.meta_period') }} {{ $document->period_start }} — {{ $document->period_end }}
                            &middot; {{ __('planning.forecasts.preview.meta_source') }} {{ $document->source }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('planning.forecasts.index') }}"
                            class="px-4 py-2 rounded-xl font-semibold border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">
                            {{ __('planning.forecasts.preview.back') }}
                        </a>
                        @if ($document->status === 'preview')
                            <form method="POST" action="{{ route('planning.forecasts.confirm-plan', $document) }}"
                                onsubmit='return confirm(@js(__("planning.forecasts.preview.confirm_commit")));'>
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl font-semibold bg-slate-900 text-white hover:bg-slate-800">
                                    ✅ {{ __('planning.forecasts.preview.commit') }}
                                </button>
                            </form>
                        @else
                            <span class="px-4 py-2 rounded-xl font-semibold bg-slate-100 text-slate-500">{{ __('planning.forecasts.preview.committed') }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="rounded-xl border border-slate-200 px-4 py-2">
                        <span class="text-slate-500">{{ __('planning.forecasts.preview.total_rows') }}</span>
                        <span class="ml-2 font-semibold">{{ $document->total_rows }}</span>
                    </div>
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-2">
                        <span class="text-green-700">✅ {{ __('planning.forecasts.preview.mapped') }}</span>
                        <span class="ml-2 font-semibold text-green-800">{{ $document->mapped_rows }}</span>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2">
                        <span class="text-amber-700">⚠️ {{ __('planning.forecasts.preview.unmapped') }}</span>
                        <span class="ml-2 font-semibold text-amber-800">{{ $document->unmapped_rows }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">{{ __('planning.forecasts.preview.th_row') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('planning.forecasts.preview.th_customer') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('planning.forecasts.preview.th_gci') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('planning.forecasts.preview.th_status') }}</th>
                                @foreach ($periods as $period)
                                    <th class="px-4 py-3 text-right font-semibold">{{ $period }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($document->rows as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $row->row_no }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $row->customer_part_no }}</td>
                                    <td class="px-4 py-3">
                                        @if ($row->gciPart)
                                            <span class="font-mono text-xs">{{ $row->gciPart->part_no }}</span>
                                            <div class="text-xs text-slate-500">{{ $row->gciPart->part_name }}</div>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('planning.forecasts.preview.unmapped_row') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($row->mapping_status === 'mapped')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">✅ {{ __('planning.forecasts.preview.badge_mapped') }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">⚠️ {{ __('planning.forecasts.preview.badge_unmapped') }}</span>
                                        @endif
                                    </td>
                                    @foreach ($periods as $period)
                                        <td class="px-4 py-3 text-right font-mono text-xs">
                                            {{ isset($row->quantities[$period]) ? formatNumber($row->quantities[$period]) : '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + count($periods) }}" class="px-4 py-8 text-center text-slate-500">
                                        {{ __('planning.forecasts.preview.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
