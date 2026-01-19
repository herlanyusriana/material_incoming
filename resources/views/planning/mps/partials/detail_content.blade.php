<div class="space-y-6">
    <!-- Header info -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-5">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">{{ $part->part_no }}</h3>
                <p class="text-sm text-slate-500">{{ $part->part_name }}</p>
            </div>
            <div class="text-right">
                <span class="block text-xs uppercase tracking-wider text-slate-400 font-semibold">{{ preg_match('/^\d{4}-\d{2}$/', $minggu) ? 'Month' : 'Week' }}</span>
                <span class="block text-lg font-mono font-bold text-indigo-600">
                    {{ preg_match('/^\d{4}-\d{2}$/', $minggu) ? \Carbon\Carbon::parse($minggu.'-01')->format('F Y') : $minggu }}
                </span>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
             <div class="bg-white p-3 rounded border border-slate-100">
                 <span class="block text-xs text-slate-400">Forecast Qty</span>
                 <span class="block font-mono font-semibold text-slate-700">{{ number_format($mps->forecast_qty) }}</span>
             </div>
             <div class="bg-white p-3 rounded border border-slate-100">
                 <span class="block text-xs text-slate-400">Current Status</span>
                 <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold mt-1 
                     {{ $mps->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                     {{ strtoupper($mps->status) }}
                 </span>
             </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white border rounded-lg shadow-sm p-6 relative">
        <h4 class="text-sm font-semibold text-slate-900 mb-4 uppercase tracking-wider">Production Plan</h4>
        
        <form action="{{ route('planning.mps.upsert') }}" method="POST">
            @csrf
            <input type="hidden" name="part_id" value="{{ $part->id }}">
            <input type="hidden" name="minggu" value="{{ $minggu }}">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Planned Quantity</label>
                <div class="relative rounded-md shadow-sm">
                    <input type="number" name="planned_qty" value="{{ $mps->planned_qty }}" 
                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-slate-300 rounded-md disabled:bg-slate-100 disabled:text-slate-500"
                        @disabled($mps->status === 'approved') step="1" min="0">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-slate-500 sm:text-sm">pcs</span>
                    </div>
                </div>
                @if($mps->status === 'approved')
                    <p class="mt-2 text-xs text-amber-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Plan is approved and locked.
                    </p>
                @elseif(preg_match('/^\d{4}-\d{2}$/', $minggu))
                    <p class="mt-2 text-xs text-indigo-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Value will be distributed evenly to weeks.
                    </p>
                @endif
            </div>

            @if($mps->status !== 'approved')
                <div class="flex gap-3">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ $mps->exists ? 'Update Plan' : 'Save Plan' }}
                    </button>
                    
                    @if($mps->exists)
                        <!-- If strictly approval needed, maybe another button or logic -->
                    @endif
                </div>
            @endif
        </form>
    </div>

    <!-- BOM Preview -->
    @php
        // Get active BOM
        $bom = $part->boms->where('status', 'active')->first();
    @endphp
    <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b bg-slate-50 flex justify-between items-center">
            <h4 class="text-sm font-semibold text-slate-900">BOM Requirements</h4>
            @if($bom)
                <span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Rev: {{ $bom->revision }}</span>
            @else
                <span class="text-xs text-red-500 font-medium">No Active BOM</span>
            @endif
        </div>
        
        @if($bom && $bom->items->count() > 0)
            <div class="max-h-60 overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-white sticky top-0">
                        <tr>
                            <th class="px-5 py-2 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Component</th>
                            <th class="px-5 py-2 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Usage</th>
                            <th class="px-5 py-2 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Est. Req</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($bom->items as $item)
                        <tr>
                            <td class="px-5 py-2 whitespace-nowrap text-sm text-slate-700">
                                {{ $item->componentPart->part_no }}
                            </td>
                            <td class="px-5 py-2 whitespace-nowrap text-sm text-right text-slate-500">
                                {{ floatval($item->usage_qty) }}
                            </td>
                            <td class="px-5 py-2 whitespace-nowrap text-sm text-right font-mono text-indigo-600 font-semibold">
                                {{ number_format($mps->planned_qty * $item->usage_qty) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-5 text-center text-sm text-slate-400 italic">
                No BOM items found or BOM not active. 
                <br>MRP will not generate requirements for this part.
            </div>
        @endif
    </div>
</div>
