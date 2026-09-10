<x-app-layout>
    <x-slot name="header">
        {{ __('planning.mrp.history.header') }}
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">{{ __('planning.mrp.history.title') }}</h2>
            <a href="{{ route('planning.mrp.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                {{ __('planning.mrp.history.back') }}
            </a>
        </div>

        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold">{{ __('planning.mrp.history.th_date') }}</th>
                        <th class="px-6 py-4 text-left font-semibold">{{ __('planning.mrp.history.th_user') }}</th>
                        <th class="px-6 py-4 text-left font-semibold">{{ __('planning.mrp.history.th_action') }}</th>
                        <th class="px-6 py-4 text-left font-semibold">{{ __('planning.mrp.history.th_parts') }}</th>
                        <th class="px-6 py-4 text-left font-semibold">{{ __('planning.mrp.history.th_run') }}</th>
                        <th class="px-6 py-4 text-left font-semibold">{{ __('planning.mrp.history.th_notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($histories as $history)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-900">
                                {{ $history->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $history->user->name ?? __('planning.mrp.history.unknown') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($history->action === 'generate')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                        GENERATE
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                        CLEAR
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-700">
                                {{ formatNumber($history->parts_count) }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $history->mrpRun?->minggu ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $history->notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                {{ __('planning.mrp.history.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-6 py-4 border-t bg-slate-50">
                {{ $histories->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
