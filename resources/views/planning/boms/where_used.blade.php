<x-app-layout>
    <x-slot name="header">
        BOM Where-Used Analysis (Implosion)
    </x-slot>

    <div class="py-6" x-data="whereUsedApp()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Search Section --}}
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">🔍 Search Component</h3>
                <p class="text-sm text-slate-600 mb-4">Enter a component part number to see where it's used in BOMs</p>
                
                <div class="flex gap-3">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            x-model="searchQuery"
                            @keyup.enter="search()"
                            class="w-full rounded-xl border-slate-200 text-lg"
                            placeholder="Enter part number (e.g., RM-001, COIL-123)"
                        >
                    </div>
                    <button 
                        @click="search()"
                        :disabled="!searchQuery || isLoading"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white rounded-xl font-semibold transition-colors"
                        :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
                    >
                        <span x-show="!isLoading">Search</span>
                        <span x-show="isLoading">Searching...</span>
                    </button>
                </div>

                {{-- Quick Search Buttons --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-xs text-slate-500 font-semibold">Quick search:</span>
                    @foreach($recentParts ?? [] as $part)
                        <button 
                            @click="searchQuery = '{{ $part->part_no }}'; search()"
                            class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold"
                        >
                            {{ $part->part_no }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Results Section --}}
            <div x-show="hasSearched" x-cloak>
                {{-- No Results --}}
                <div x-show="results.length === 0 && !isLoading" class="bg-yellow-50 border border-yellow-200 rounded-2xl p-8 text-center">
                    <div class="text-4xl mb-3">📭</div>
                    <h4 class="text-lg font-bold text-yellow-900 mb-2">No Usage Found</h4>
                    <p class="text-yellow-700">
                        Component <span class="font-mono font-bold" x-text="lastSearchQuery"></span> is not used in any BOM.
                    </p>
                    <p class="text-sm text-yellow-600 mt-2">This could mean it's a finished good or hasn't been added to any BOM yet.</p>
                </div>

                {{-- Results Table --}}
                <div x-show="results.length > 0" class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">
                                Where-Used Results for <span class="font-mono text-indigo-700" x-text="lastSearchQuery"></span>
                            </h4>
                            <p class="text-sm text-slate-600 mt-1">
                                Found in <span class="font-bold" x-text="results.length"></span> parent assembly/assemblies
                            </p>
                        </div>
                        <button 
                            @click="hasSearched = false; results = []; searchQuery = ''"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold text-sm"
                        >
                            New Search
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">Parent Part (FG/WIP)</th>
                                    <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                    <th class="px-4 py-3 text-center font-semibold">Revision</th>
                                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                                    <th class="px-4 py-3 text-center font-semibold">Customer Products</th>
                                    <th class="px-4 py-3 text-center font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(item, index) in results" :key="item.id">
                                    <template>
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3">
                                                <div class="font-mono font-bold text-slate-900" x-text="item.fg_part_no"></div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700" x-text="item.fg_part_name"></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800" x-text="'REV ' + (item.revision || 'A')"></span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span 
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                                                    :class="item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-700'"
                                                    x-text="item.status.toUpperCase()"
                                                ></span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span 
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
                                                    :class="(item.customer_products && item.customer_products.length > 0) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                                                    x-text="(item.customer_products && item.customer_products.length > 0) ? item.customer_products.length + ' Products' : 'None'"
                                                ></span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a 
                                                        :href="`{{ url('planning/boms') }}/${item.id}/explosion`"
                                                        class="px-3 py-1.5 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-semibold text-xs"
                                                    >
                                                        View Explosion
                                                    </a>
                                                    <a 
                                                        :href="`{{ route('planning.boms.index') }}?gci_part_id=${item.part_id}`"
                                                        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs"
                                                    >
                                                        Edit BOM
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Customer Products Detail Row -->
                                        <tr x-show="item.customer_products && item.customer_products.length > 0" class="bg-emerald-50">
                                            <td colspan="6" class="px-4 py-3">
                                                <div class="text-xs font-semibold text-emerald-900 mb-2">📦 Customer Products using this FG Part:</div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                    <template x-for="cp in item.customer_products" :key="cp.customer_part_no">
                                                        <div class="bg-white rounded-lg p-3 border border-emerald-200">
                                                            <div class="font-mono font-bold text-sm text-slate-900" x-text="cp.customer_part_no"></div>
                                                            <div class="text-xs text-slate-600" x-text="cp.customer_part_name"></div>
                                                            <div class="text-xs text-slate-500 mt-1">
                                                                <span class="font-semibold">Customer:</span> <span x-text="cp.customer_name"></span> | 
                                                                <span class="font-semibold">Usage:</span> <span x-text="cp.usage_qty"></span> pcs
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Impact Analysis --}}
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <h5 class="text-sm font-bold text-blue-900 mb-2">💡 Impact Analysis</h5>
                        <div class="text-sm text-blue-800 space-y-1">
                            <p>
                                If you change or discontinue <span class="font-mono font-bold" x-text="lastSearchQuery"></span>, 
                                it will affect <span class="font-bold" x-text="results.length"></span> parent product(s).
                            </p>
                            <p class="text-xs text-blue-600">
                                Consider checking for substitutes or updating all affected BOMs before making changes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Card --}}
            <div x-show="!hasSearched" class="bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-200 rounded-2xl p-8">
                <div class="max-w-2xl mx-auto text-center">
                    <div class="text-5xl mb-4">🔎</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">BOM Where-Used Analysis</h3>
                    <p class="text-slate-700 mb-4">
                        Quickly find which products use a specific component. Perfect for:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-2xl mb-2">🔄</div>
                            <div class="font-semibold text-slate-900 text-sm">Substitute Planning</div>
                            <div class="text-xs text-slate-600 mt-1">Find all products affected by material changes</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-2xl mb-2">⚠️</div>
                            <div class="font-semibold text-slate-900 text-sm">Impact Assessment</div>
                            <div class="text-xs text-slate-600 mt-1">Evaluate effects of discontinuing a part</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-2xl mb-2">📊</div>
                            <div class="font-semibold text-slate-900 text-sm">Usage Tracking</div>
                            <div class="text-xs text-slate-600 mt-1">See complete usage across all products</div>
                        </div>
                    </div>
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
                        const response = await fetch(`{{ route('planning.boms.where-used') }}?part_no=${encodeURIComponent(this.lastSearchQuery)}`);
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
</x-app-layout>
