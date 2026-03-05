<x-app-layout>
    <x-slot name="header">WO Detail - {{ $workOrder->wo_no }}</x-slot>

    <div class="space-y-4" x-data="{ tab: 'bom' }">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-xl border bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">WO NUMBER</p>
                    <p class="text-lg font-bold text-slate-800">{{ $workOrder->wo_no }}</p>
                    @php($woLines = collect(data_get($workOrder->source_payload_json, 'lines', [])))
                    @if($woLines->count() > 1)
                        <p class="text-sm text-indigo-700 font-semibold">Multi FG ({{ $woLines->count() }} lines)</p>
                    @endif
                    <p class="text-sm text-slate-600">
                        {{ $workOrder->fgPart?->part_no }} - {{ $workOrder->fgPart?->part_name }}
                    </p>
                </div>
                <div class="text-right text-sm text-slate-600">
                    <p>Source: <span class="font-semibold">{{ strtoupper(str_replace('_', ' ', $workOrder->source_type)) }}</span></p>
                    <p>Status: <span class="font-semibold">{{ strtoupper(str_replace('_', ' ', $workOrder->status)) }}</span></p>
                    <p>Plan: <span class="font-semibold">{{ optional($workOrder->plan_date)->format('Y-m-d') }}</span> / {{ number_format((float) $workOrder->qty_plan, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border bg-white p-4 lg:col-span-2">
                <h3 class="text-sm font-semibold text-slate-700">Edit WO (Allowed + Audit Logged)</h3>
                <form method="POST" action="{{ route('production.work-orders.update', $workOrder) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">FG Part</label>
                            <select name="fg_part_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                                @foreach ($fgParts as $fgPart)
                                    <option value="{{ $fgPart->id }}" @selected((int)$workOrder->fg_part_id === (int)$fgPart->id)>
                                        {{ $fgPart->part_no }} - {{ $fgPart->part_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Plan Date</label>
                            <input type="date" name="plan_date" value="{{ optional($workOrder->plan_date)->format('Y-m-d') }}"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Qty Plan</label>
                            <input type="number" step="0.0001" min="0.0001" name="qty_plan" value="{{ (float) $workOrder->qty_plan }}"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Priority</label>
                            <select name="priority" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                                @foreach ([1,2,3,4,5] as $n)
                                    <option value="{{ $n }}" @selected((int)$workOrder->priority === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Remarks</label>
                        <input type="text" name="remarks" value="{{ $workOrder->remarks }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Routing JSON</label>
                        <textarea name="routing_json" rows="3" class="mt-1 w-full rounded-lg border-slate-200 font-mono text-xs">{{ $workOrder->routing_json ? json_encode($workOrder->routing_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Schedule JSON</label>
                        <textarea name="schedule_json" rows="3" class="mt-1 w-full rounded-lg border-slate-200 font-mono text-xs">{{ $workOrder->schedule_json ? json_encode($workOrder->schedule_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea>
                    </div>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Save WO Edit</button>
                </form>
            </div>

            <div class="rounded-xl border bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-700">Status Flow</h3>
                <p class="mt-1 text-xs text-slate-500">Open -> In Progress -> QC -> Closed</p>
                <form method="POST" action="{{ route('production.work-orders.status', $workOrder) }}" class="mt-3 space-y-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="w-full rounded-lg border-slate-200 text-sm">
                        @foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'qc' => 'QC', 'closed' => 'Closed'] as $k => $v)
                            <option value="{{ $k }}" @selected($workOrder->status === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="remarks" placeholder="Status change note (optional)" class="w-full rounded-lg border-slate-200 text-sm">
                    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Update Status</button>
                </form>
                <a href="{{ route('production.work-orders.index') }}" class="mt-2 inline-block text-xs font-semibold text-slate-600 hover:text-slate-800">Back to List</a>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <div class="flex flex-wrap gap-2 border-b pb-3">
                <button type="button" @click="tab='bom'" :class="tab==='bom' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">BOM Snapshot</button>
                <button type="button" @click="tab='req'" :class="tab==='req' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">Material Requirement</button>
                <button type="button" @click="tab='routing'" :class="tab==='routing' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">Routing</button>
                <button type="button" @click="tab='schedule'" :class="tab==='schedule' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">Schedule</button>
                <button type="button" @click="tab='history'" :class="tab==='history' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">History</button>
            </div>

            <div x-show="tab==='bom'" class="pt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-2 py-2 text-left">FG</th>
                            <th class="px-2 py-2 text-left">Line</th>
                            <th class="px-2 py-2 text-left">Component</th>
                            <th class="px-2 py-2 text-left">Net/FG</th>
                            <th class="px-2 py-2 text-left">Process</th>
                            <th class="px-2 py-2 text-left">Substitutes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($workOrder->bomSnapshots as $r)
                            <tr>
                                <td class="px-2 py-2">
                                    <div class="font-medium">{{ $r->fg_part_no ?: $workOrder->fgPart?->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $r->fg_part_name ?: $workOrder->fgPart?->part_name }}</div>
                                </td>
                                <td class="px-2 py-2">{{ $r->line_no }}</td>
                                <td class="px-2 py-2">
                                    <div class="font-medium">{{ $r->component_part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $r->component_part_name }}</div>
                                </td>
                                <td class="px-2 py-2 font-mono">{{ number_format((float) $r->net_required_per_fg, 6) }} {{ $r->consumption_uom }}</td>
                                <td class="px-2 py-2">{{ $r->process_name ?: '-' }}</td>
                                <td class="px-2 py-2 text-xs">
                                    @if (is_array($r->substitutes_json) && count($r->substitutes_json))
                                        {{ collect($r->substitutes_json)->pluck('part_no')->filter()->join(', ') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-2 py-8 text-center text-slate-500">No BOM snapshot.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div x-show="tab==='req'" class="pt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-2 py-2 text-left">FG</th>
                            <th class="px-2 py-2 text-left">Component</th>
                            <th class="px-2 py-2 text-left">Qty/FG</th>
                            <th class="px-2 py-2 text-left">Qty Requirement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($workOrder->requirementSnapshots as $r)
                            <tr>
                                <td class="px-2 py-2">
                                    <div class="font-medium">{{ $r->fg_part_no ?: $workOrder->fgPart?->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $r->fg_part_name ?: $workOrder->fgPart?->part_name }}</div>
                                </td>
                                <td class="px-2 py-2">
                                    <div class="font-medium">{{ $r->component_part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $r->component_part_name }}</div>
                                </td>
                                <td class="px-2 py-2 font-mono">{{ number_format((float) $r->qty_per_fg, 6) }} {{ $r->uom }}</td>
                                <td class="px-2 py-2 font-mono">{{ number_format((float) $r->qty_requirement, 6) }} {{ $r->uom }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-2 py-8 text-center text-slate-500">No requirement snapshot.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div x-show="tab==='routing'" class="pt-3">
                <pre class="overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{ $workOrder->routing_json ? json_encode($workOrder->routing_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : "No routing data" }}</pre>
            </div>
            <div x-show="tab==='schedule'" class="pt-3">
                <pre class="overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{ $workOrder->schedule_json ? json_encode($workOrder->schedule_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : "No schedule data" }}</pre>
            </div>
            <div x-show="tab==='history'" class="pt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-2 py-2 text-left">Time</th>
                            <th class="px-2 py-2 text-left">Event</th>
                            <th class="px-2 py-2 text-left">Actor</th>
                            <th class="px-2 py-2 text-left">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($workOrder->histories as $h)
                            <tr>
                                <td class="px-2 py-2 text-xs">{{ $h->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="px-2 py-2 font-semibold">{{ $h->event_type }}</td>
                                <td class="px-2 py-2">{{ $h->actor?->name ?: '-' }}</td>
                                <td class="px-2 py-2 text-xs">{{ $h->remarks ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-2 py-8 text-center text-slate-500">No history yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
