@php
    $policyLabels = [
        'direct_issue'         => [__('planning.gci_parts.policy.direct_issue'), 'bg-slate-100 text-slate-600 border-slate-200'],
        'backflush_return'     => [__('planning.gci_parts.policy.backflush_return'), 'bg-orange-100 text-orange-800 border-orange-200'],
        'backflush_line_stock' => [__('planning.gci_parts.policy.backflush_line_stock'), 'bg-emerald-100 text-emerald-800 border-emerald-200'],
    ];
    [$policyLabel, $policyClass] = $policyLabels[$policy] ?? [__('planning.gci_parts.policy.unset'), 'bg-red-100 text-red-700 border-red-200'];
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $policyClass }}">
    {{ $policyLabel }}
</span>
