<x-app-layout>
    <x-slot name="header">
        Planning • Customer Part Mapping
    </x-slot>

    <div class="py-6" x-data="planningCustomerParts()">
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
                    <ul class="mt-1 list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Customer</label>
                            <select name="customer_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected((string) $customerId === (string) $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                        Add Customer Part
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse ($customerParts as $cp)
                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-slate-500">{{ $cp->customer->code ?? '-' }} • {{ $cp->customer->name ?? '-' }}</div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $cp->customer_part_no }}</div>
                                    <div class="text-sm text-slate-600">{{ $cp->customer_part_name ?? '-' }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $cp->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ strtoupper($cp->status) }}
                                    </span>
                                    <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($cp))">Edit</button>
                                    <form action="{{ route('planning.customer-parts.destroy', $cp) }}" method="POST" onsubmit="return confirm('Delete mapping?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Mapped Part GCI</div>
                                <div class="mt-2 overflow-x-auto border border-slate-200 rounded-xl">
                                    <table class="min-w-full text-sm divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                                <th class="px-3 py-2 text-left font-semibold">Part No</th>
                                                <th class="px-3 py-2 text-left font-semibold">Part Name</th>
                                                <th class="px-3 py-2 text-right font-semibold">Consumption</th>
                                                <th class="px-3 py-2 text-right font-semibold">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($cp->components as $comp)
                                                <tr>
                                                    <td class="px-3 py-2 font-semibold">{{ $comp->part->part_no ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $comp->part->part_name ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float) $comp->usage_qty, 3) }}</td>
                                                    <td class="px-3 py-2 text-right">
                                                        <form action="{{ route('planning.customer-parts.components.destroy', $comp) }}" method="POST" onsubmit="return confirm('Remove component?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="text-red-600 hover:text-red-800 font-semibold" type="submit">Remove</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-3 py-4 text-center text-slate-500">No components mapped</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <form action="{{ route('planning.customer-parts.components.store', $cp) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div class="min-w-[240px]">
                                        <label class="text-xs font-semibold text-slate-600">Part GCI</label>
                                        <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                                            <option value="" disabled selected>Select part</option>
                                            @foreach ($parts as $p)
                                                <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Consumption</label>
                                        <input type="number" step="any" min="0" name="usage_qty" class="mt-1 rounded-xl border-slate-200" required>
                                    </div>
                                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white font-semibold">Add / Update</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-500">No customer part mapping</div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $customerParts->links() }}
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Customer Part' : 'Edit Customer Part'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer</label>
                        <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.customer_id">
                            <option value="" disabled>Select customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer Part No</label>
                        <input name="customer_part_no" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.customer_part_no">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer Part Name</label>
                        <input name="customer_part_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.customer_part_name">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
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
            function planningCustomerParts() {
                return {
                    modalOpen: false,
                    mode: 'create',
                    formAction: @js(route('planning.customer-parts.store')),
                    form: { id: null, customer_id: '', customer_part_no: '', customer_part_name: '', status: 'active' },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('planning.customer-parts.store'));
                        this.form = { id: null, customer_id: '', customer_part_no: '', customer_part_name: '', status: 'active' };
                        this.modalOpen = true;
                    },
                    openEdit(cp) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/planning/customer-parts')) + '/' + cp.id;
                        this.form = {
                            id: cp.id,
                            customer_id: cp.customer_id,
                            customer_part_no: cp.customer_part_no,
                            customer_part_name: cp.customer_part_name,
                            status: cp.status,
                        };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>
