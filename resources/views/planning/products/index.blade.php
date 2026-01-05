<x-app-layout>
    <x-slot name="header">
        Planning • Products
    </x-slot>

    <div class="py-6" x-data="planningProducts()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">Validation error</div>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-slate-600">Master product untuk Planning</div>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm"
                    @click="openCreate()"
                >
                    Add Product
                </button>
            </div>

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Code</th>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-left font-semibold">UOM</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($products as $p)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $p->code }}</td>
                                    <td class="px-4 py-3">{{ $p->name }}</td>
                                    <td class="px-4 py-3">{{ $p->uom ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($p->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($p))">Edit</button>
                                        <form action="{{ route('planning.products.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Delete product?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No products</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
            x-show="modalOpen"
            x-cloak
            @keydown.escape.window="close()"
        >
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Product' : 'Edit Product'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Code</label>
                            <input type="text" name="code" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.code" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">UOM</label>
                            <input type="text" name="uom" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.uom" placeholder="PCS / KGM / SHEET">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Name</label>
                        <input type="text" name="name" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.name" required>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.status" required>
                            <option value="active">active</option>
                            <option value="inactive">inactive</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function planningProducts() {
                return {
                    modalOpen: false,
                    mode: 'create',
                    formAction: @js(route('planning.products.store')),
                    form: { id: null, code: '', name: '', uom: '', status: 'active' },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('planning.products.store'));
                        this.form = { id: null, code: '', name: '', uom: '', status: 'active' };
                        this.modalOpen = true;
                    },
                    openEdit(p) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/planning/products')) + '/' + p.id;
                        this.form = { id: p.id, code: p.code ?? '', name: p.name ?? '', uom: p.uom ?? '', status: p.status ?? 'active' };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>

