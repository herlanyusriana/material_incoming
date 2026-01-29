@extends('outgoing.layout')

@section('content')
    <div class="space-y-6" x-data="whereUsedApp()">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-lg font-semibold text-slate-900">Customer Product Mapping</div>
            <div class="mt-2 text-sm text-slate-600">
                Mapping customer part → Part GCI (bisa lebih dari 1 komponen), dipakai untuk translasi planning dan customer
                PO.
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('planning.customer-parts.index') }}"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Buka Customer Part Mapping
                </a>
            </div>
        </div>

        <div id="where-used" class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-900">🔍 Where-Used (BOM)</h3>
                    <p class="text-sm text-slate-600">Cari 1 component part number → ketahuan dipakai di FG mana + customer
                        product mana.</p>
                </div>
                <div class="text-xs text-slate-500">
                    Tips: input RM / WIP / part apapun.
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <div class="flex-1">
                    <input type="text" x-model="searchQuery" @keyup.enter="search()"
                        class="w-full rounded-xl border-slate-200 text-base" placeholder="Enter part number (e.g., RM-001)">
                </div>
                <button type="button" @click="search()" :disabled="!searchQuery || isLoading"
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white rounded-xl font-semibold transition-colors"
                    :class="{ 'opacity-50 cursor-not-allowed': isLoading }">
                    <span x-show="!isLoading">Search</span>
                    <span x-show="isLoading">Searching...</span>
                </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span class="text-xs text-slate-500 font-semibold">Quick search:</span>
                @foreach(($recentParts ?? []) as $part)
                    <button type="button" @click="searchQuery = '{{ $part->part_no }}'; search()"
                        class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        {{ $part->part_no }}
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="hasSearched" x-cloak class="space-y-4">
            <div x-show="results.length === 0 && !isLoading"
                class="bg-yellow-50 border border-yellow-200 rounded-2xl p-8 text-center">
                <div class="text-4xl mb-3">📭</div>
                <h4 class="text-lg font-bold text-yellow-900 mb-2">No Usage Found</h4>
                <p class="text-yellow-700">
                    Component <span class="font-mono font-bold" x-text="lastSearchQuery"></span> tidak dipakai di BOM mana
                    pun.
                </p>
            </div>

            <div x-show="results.length > 0" class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">
                            Where-Used Results for <span class="font-mono text-emerald-700" x-text="lastSearchQuery"></span>
                        </h4>
                        <p class="text-sm text-slate-600 mt-1">
                            Found in <span class="font-bold" x-text="results.length"></span> parent assembly/assemblies
                        </p>
                    </div>
                    <button type="button" @click="hasSearched = false; results = []; searchQuery = ''"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold text-sm">
                        New Search
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-xs uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-3 text-left font-bold">FG Part No</th>
                                <th class="px-4 py-3 text-left font-bold">FG Part Name</th>
                                <th class="px-4 py-3 text-left font-bold w-24">Revision</th>
                                <th class="px-4 py-3 text-left font-bold w-24">Status</th>
                                <th class="px-4 py-3 text-left font-bold">Customer Products</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="bom in results" :key="bom.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono font-semibold text-slate-800" x-text="bom.fg_part_no">
                                    </td>
                                    <td class="px-4 py-3 text-slate-700" x-text="bom.fg_part_name"></td>
                                    <td class="px-4 py-3 text-slate-700" x-text="bom.revision || '-'"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold"
                                            :class="bom.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'">
                                            <span x-text="bom.status"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <template x-if="(bom.customer_products || []).length === 0">
                                            <div class="text-xs text-slate-500 italic">No customer product mapping.</div>
                                        </template>
                                        <template x-if="(bom.customer_products || []).length > 0">
                                            <div class="space-y-2">
                                                <template x-for="cp in bom.customer_products"
                                                    :key="cp.customer_part_no + '|' + cp.customer_name">
                                                    <div class="rounded-lg border border-slate-200 bg-white p-2">
                                                        <div
                                                            class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="text-sm font-semibold text-slate-800">
                                                                <span class="font-mono" x-text="cp.customer_part_no"></span>
                                                                <span class="text-slate-500">•</span>
                                                                <span x-text="cp.customer_part_name"></span>
                                                            </div>
                                                            <div class="text-xs text-slate-600">
                                                                <span class="font-semibold"
                                                                    x-text="cp.customer_name"></span>
                                                                <span class="text-slate-400">•</span>
                                                                <span>Usage:</span>
                                                                <span class="font-bold" x-text="cp.usage_qty"></span>
                                                                <template x-if="cp.line">
                                                                    <span> • Line: <span
                                                                            class="font-semibold text-slate-700"
                                                                            x-text="cp.line"></span></span>
                                                                </template>
                                                                <template x-if="cp.case_name">
                                                                    <span> • Case: <span
                                                                            class="font-semibold text-slate-700"
                                                                            x-text="cp.case_name"></span></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function whereUsedApp() {
            return {
                searchQuery: '',
                lastSearchQuery: '',
                results: [],
                isLoading: false,
                hasSearched: false,

                async search() {
                    if (!this.searchQuery || this.searchQuery.trim() === '') {
                        return;
                    }

                    this.isLoading = true;
                    this.lastSearchQuery = this.searchQuery.trim().toUpperCase();

                    try {
                        const response = await fetch(`{{ route('outgoing.where-used') }}?part_no=${encodeURIComponent(this.lastSearchQuery)}`);
                        const data = await response.json();
                        this.results = data.used_in || [];
                        this.hasSearched = true;
                    } catch (error) {
                        console.error('Error searching:', error);
                        alert('Error searching. Please try again.');
                    } finally {
                        this.isLoading = false;
                    }
                }
            }
        }
    </script>
@endsection