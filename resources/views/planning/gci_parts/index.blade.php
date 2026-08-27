<x-app-layout>
    <x-slot name="header">
        Planning &mdash; Part GCI
    </x-slot>

    <div class="space-y-4" x-data="partMaster()">

        {{-- Toolbar: tabs + search + actions --}}
        <div class="flex flex-wrap items-center gap-3 justify-between">
            <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl p-1">
                @foreach (['' => 'Semua', 'RM' => 'RM', 'WIP' => 'WIP', 'FG' => 'FG'] as $tabValue => $tabLabel)
                    @php $active = ($classification ?? '') === $tabValue; @endphp
                    <a href="{{ route('planning.gci-parts.index', array_filter(['classification' => $tabValue ?: null, 'q' => $qParam ?: null, 'status' => $status ?: null])) }}"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors {{ $active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $tabLabel }}
                        <span class="ml-1 text-xs {{ $active ? 'text-slate-300' : 'text-slate-400' }}">{{ $tabValue === '' ? array_sum($classCounts ?? []) : ($classCounts[$tabValue] ?? 0) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('planning.gci-parts.index') }}" class="flex items-center gap-2">
                    @if ($classification)
                        <input type="hidden" name="classification" value="{{ $classification }}">
                    @endif
                    <select name="status" class="rounded-xl border-slate-200 text-sm">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    </select>
                    <div class="relative">
                        <input type="search" name="q" value="{{ $qParam }}" placeholder="Cari part no / nama / model..."
                            class="w-56 rounded-xl border-slate-200 text-sm pl-9">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" d="m21 21-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                </form>
                <a href="{{ route('planning.gci-parts.export') }}"
                    class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">Export</a>
                <button type="button" @click="openImport()"
                    class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">Import</button>
                <button type="button" @click="openCreate()"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">+ Part</button>
            </div>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- List --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Part</th>
                            <th class="px-4 py-3 text-left font-semibold">Tipe</th>
                            <th class="px-4 py-3 text-left font-semibold">Model</th>
                            <th class="px-4 py-3 text-left font-semibold">Customer</th>
                            <th class="px-4 py-3 text-left font-semibold">Policy</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($parts as $p)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-semibold text-slate-900">{{ $p->part_no }}</span>
                                    <span class="block text-xs text-slate-500">{{ $p->size }}</span>
                                </td>
                                <td class="px-4 py-3">@include('planning.gci_parts._badges', ['p' => $p])</td>
                                <td class="px-4 py-3 text-slate-600">{{ $p->model ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($p->customers->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($p->customers as $cust)
                                                <span class="inline-flex px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-[11px] font-semibold text-indigo-700">{{ $cust->code }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-300">&ndash;</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">@include('planning.gci_parts._policy_badge', ['policy' => $p->consumption_policy ?: (($p->is_backflush ?? true) ? 'backflush_return' : 'direct_issue')])</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center w-2 h-2 rounded-full {{ $p->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                    <span class="ml-1.5 text-xs {{ $p->status === 'active' ? 'text-slate-700' : 'text-slate-400' }}">{{ $p->status }}</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800" @click="openEdit(@js($p))">Edit</button>
                                    <form action="{{ route('planning.gci-parts.destroy', $p) }}" method="POST" class="inline ml-3"
                                        onsubmit="return confirm('Hapus part {{ $p->part_no }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="font-semibold text-red-600 hover:text-red-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">Tidak ada part{{ $classification ? ' ' . $classification : '' }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $parts->links() }}
            </div>
        </div>

        @include('planning.gci_parts._part_form')
        @include('planning.gci_parts._import_modal')
    </div>

    @push('scripts')
        <script>
            window.__PART_MASTER__ = {
                routes: {
                    store: @js(route('planning.gci-parts.store')),
                    parts: @js(url('/planning/gci-parts')),
                    substitutes: @js(url('/planning/gci-part-substitutes')),
                },
                defaultClassification: @js($classification ?? 'FG'),
                duplicateWarning: @js(session('duplicate_warning_data')),
                maps: {
                    rmFg: @js($rmFgMap ?? []),
                    partVendor: @js($partVendorMap ?? []),
                    substitutesFor: @js($partSubstitutesMap ?? []),
                    asSubstitute: @js($partAsSubstituteMap ?? []),
                },
            };
        </script>
        <script src="{{ asset('js/planning/gci-parts.js') }}"></script>
    @endpush
</x-app-layout>
