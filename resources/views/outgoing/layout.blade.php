<x-app-layout>
    <x-slot name="header">
        Outgoing
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Flow Outgoing</div>
                <div class="mt-1 text-sm text-slate-600">
                    Customer Daily Planning → Customer PO (Reference) → Customer Product Mapping → Delivery Requirements
                    → PO Outgoing (Sales Order) → Delivery Plan &amp; Arrangement → Delivery Notes (Surat Jalan)
                </div>
            </div>

            @yield('content')
        </div>
    </div>
</x-app-layout>