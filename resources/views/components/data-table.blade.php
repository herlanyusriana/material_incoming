@props([
    'emptyMessage' => 'Tidak ada data ditemukan.',
    'emptyIcon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
    'searchable' => false,
    'searchPlaceholder' => 'Cari...',
    'searchName' => 'search',
    'searchValue' => '',
])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden']) }}>
    {{-- Table toolbar --}}
    @if($searchable || isset($toolbar))
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                @if($searchable)
                    <div class="relative max-w-xs w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="{{ $searchName }}"
                            value="{{ $searchValue }}"
                            placeholder="{{ $searchPlaceholder }}"
                            class="block w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors"
                        />
                    </div>
                @endif
                @if(isset($toolbar))
                    <div class="flex items-center gap-2">
                        {{ $toolbar }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Table content --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            @if(isset($head))
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                    <tr class="text-slate-600 text-xs uppercase tracking-wider">
                        {{ $head }}
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-slate-100 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- Empty state --}}
    @if(isset($empty))
        {{ $empty }}
    @endif

    {{-- Pagination --}}
    @if(isset($pagination))
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $pagination }}
        </div>
    @endif
</div>
