<!-- Dashboard -->
<div class="space-y-1">
    <x-sidebar.link href="{{ route('dashboard') }}" icon="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" active="{{ request()->routeIs('dashboard') }}">
        Dashboard
    </x-sidebar.link>
</div>

<!-- Master Data -->
@can('manage_planning')
<x-sidebar.section label="Master Data">
    <x-sidebar.group label="Vendor" icon="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75" active="{{ request()->routeIs('vendors.*') }}">
        <x-sidebar.sub-link href="{{ route('vendors.create') }}" active="{{ request()->routeIs('vendors.create') }}">Create Vendor</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('vendors.index') }}" active="{{ request()->routeIs('vendors.index') || request()->routeIs('vendors.edit') }}">Vendor List</x-sidebar.sub-link>
    </x-sidebar.group>

    <x-sidebar.link href="{{ route('parts.index') }}" icon="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z M3.29 7L12 12l8.71-5 M12 22V12" active="{{ request()->routeIs('parts.*') }}">
        Parts Master
    </x-sidebar.link>

    <x-sidebar.link href="{{ route('planning.customers.index') }}" icon="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20v-2a4 4 0 0 1 3-3.87m0 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-4 6a4 4 0 1 1 8 0" active="{{ request()->routeIs('planning.customers.*') }}">
        Customers
    </x-sidebar.link>

    <x-sidebar.link href="{{ route('planning.customer-parts.index') }}" icon="M9 12h6m-6 4h6m-6-8h6M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" active="{{ request()->routeIs('planning.customer-parts.*') }}">
        Customer Part Mapping
    </x-sidebar.link>

    <x-sidebar.group label="Pricing Master" icon="M12 8c-1.657 0-3 1.12-3 2.5S10.343 13 12 13s3 1.12 3 2.5S13.657 18 12 18m0-10V6m0 12v-2" active="{{ request()->routeIs('pricing.*') || request()->routeIs('contract-numbers.*') }}">
        <x-sidebar.sub-link href="{{ route('pricing.create') }}" active="{{ request()->routeIs('pricing.create') }}">Add New Price</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('pricing.index') }}" active="{{ request()->routeIs('pricing.index') }}">Price List</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('contract-numbers.index') }}" active="{{ request()->routeIs('contract-numbers.*') }}">Contract Numbers</x-sidebar.sub-link>
    </x-sidebar.group>

    <x-sidebar.link href="{{ route('planning.boms.index') }}" icon="M9 12h6m-6 4h6m-8 5h10a2 2 0 0 0 2-2V7.414a2 2 0 0 0-.586-1.414l-2.414-2.414A2 2 0 0 0 14.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" active="{{ request()->routeIs('planning.boms.*') }}">
        BOM Master
    </x-sidebar.link>

    <x-sidebar.link href="{{ route('truckings.index') }}" icon="M3 7h11v10H3V7Z M14 10h4l3 3v4h-7v-7Z M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" active="{{ request()->routeIs('truckings.*') }}">
        Truckings
    </x-sidebar.link>

    <x-sidebar.link href="{{ route('machines.index') }}" icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" active="{{ request()->routeIs('machines.*') }}">
        Machines
    </x-sidebar.link>
</x-sidebar.section>
@endcan

<!-- Planning -->
@can('view_planning')
<x-sidebar.section label="Planning">
    <x-sidebar.group label="Planning Module" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" active="{{ request()->routeIs('planning.*') && !request()->routeIs('planning.boms.*') && !request()->routeIs('planning.customers.*') && !request()->routeIs('planning.customer-parts.*') }}">
        <x-sidebar.sub-link href="{{ route('planning.forecasts.index') }}" active="{{ request()->routeIs('planning.forecasts.*') }}">Forecasts</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('planning.mrp.index') }}" active="{{ request()->routeIs('planning.mrp.*') && !request()->routeIs('planning.mrp.integration-dashboard') }}">Material Requirement Planning (MRP)</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('planning.mrp.integration-dashboard') }}" active="{{ request()->routeIs('planning.mrp.integration-dashboard') }}">MRP vs ERP GCI Integration</x-sidebar.sub-link>
    </x-sidebar.group>
</x-sidebar.section>
@endcan

<!-- Purchasing -->
@can('manage_purchasing')
<x-sidebar.section label="Purchasing">
    <x-sidebar.group label="Purchasing" icon="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" active="{{ request()->routeIs('purchasing.*') }}">
        <x-sidebar.sub-link href="{{ route('purchasing.purchase-requests.index') }}" active="{{ request()->routeIs('purchasing.purchase-requests.*') }}">Purchase Requests</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('purchasing.purchase-orders.index') }}" active="{{ request()->routeIs('purchasing.purchase-orders.*') && !request('changed') }}">Purchase Orders</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('purchasing.purchase-orders.index', ['changed' => 1]) }}" active="{{ request()->routeIs('purchasing.purchase-orders.index') && request('changed') }}" badge="{{ ($changedPoCount ?? 0) > 0 ? $changedPoCount : '' }}" badgeColor="rose">Changed PO</x-sidebar.sub-link>
    </x-sidebar.group>
</x-sidebar.section>
@endcan

<!-- Production -->
@if(auth()->user()->can('manage_planning') || auth()->user()->can('view_production') || auth()->user()->can('manage_final_inspection') || auth()->user()->can('manage_production_supply'))
<x-sidebar.section label="Production">
    <x-sidebar.group label="Production Control" icon="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" active="{{ request()->routeIs('production.*') }}">
        
        @can('manage_planning')
        <x-sidebar.sub-link href="{{ route('production.planning.index') }}" active="{{ request()->routeIs('production.planning.index') }}">Daily Planning</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.machine-load.index') }}" active="{{ request()->routeIs('production.machine-load.*') }}">Machine Load</x-sidebar.sub-link>
        @endcan

        @can('view_production')
        <x-sidebar.sub-link href="{{ route('production.orders.index') }}" active="{{ request()->routeIs('production.orders.*') }}">Production Order (WO)</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.kanban-release.index') }}" active="{{ request()->routeIs('production.kanban-release.*') }}">Kanban Release</x-sidebar.sub-link>
        @endcan

        @can('manage_production_supply')
        <x-sidebar.sub-link href="{{ route('production.warehouse-supply.index') }}" active="{{ request()->routeIs('production.warehouse-supply.*') }}">Warehouse Supply</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.production-supply-wh.index') }}" active="{{ request()->routeIs('production.production-supply-wh.*') }}">Supply Log</x-sidebar.sub-link>
        @endcan

        @can('view_production')
        <x-sidebar.sub-link href="{{ route('production.wo-monitoring.index') }}" active="{{ request()->routeIs('production.wo-monitoring.*') }}">WO Monitoring</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.board.index') }}" active="{{ request()->routeIs('production.board.*') }}">Production Board</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.operator-kpi.index') }}" active="{{ request()->routeIs('production.operator-kpi.*') }}">Operator KPI</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.gci-dashboard.index') }}" active="{{ request()->routeIs('production.gci-dashboard.*') }}">GCI Dashboard</x-sidebar.sub-link>
        @endcan

        @can('manage_final_inspection')
        <x-sidebar.sub-link href="{{ route('production.final-inspection.index') }}" active="{{ request()->routeIs('production.final-inspection.*') || request()->routeIs('production.qc-inspection.*') || request()->routeIs('production.in-process-inspection.*') || request()->routeIs('production.kanban-update.*') }}">Inspections & Kanban</x-sidebar.sub-link>
        @endcan

        @can('view_production')
        <x-sidebar.sub-link href="{{ route('production.downtime-history.index') }}" active="{{ request()->routeIs('production.downtime-history.*') }}">Downtime History</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('production.qdc-history.index') }}" active="{{ request()->routeIs('production.qdc-history.*') }}">QDC History</x-sidebar.sub-link>
        @endcan
    </x-sidebar.group>
</x-sidebar.section>
@endif

<!-- Incoming Material -->
@can('manage_incoming')
<x-sidebar.section label="Incoming & Receive">
    <x-sidebar.group label="Incoming Material" icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" active="{{ request()->routeIs('departures.*') || request()->routeIs('local-pos.*') || request()->routeIs('receives.*') }}">
        <x-sidebar.sub-link href="{{ route('departures.create') }}" active="{{ request()->routeIs('departures.create') }}">Create Import</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('departures.index') }}" active="{{ request()->routeIs('departures.*') && !request()->routeIs('departures.create') }}">Import List</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('local-pos.create') }}" active="{{ request()->routeIs('local-pos.create') }}">Create Local PO</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('local-pos.index') }}" active="{{ request()->routeIs('local-pos.*') && !request()->routeIs('local-pos.create') }}">Local PO List</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('receives.index') }}" active="{{ request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.edit') }}">Receive Material</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('receives.completed') }}" active="{{ request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice') }}">Completed Receives</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('receives.import-documents') }}" active="{{ request()->routeIs('receives.import-documents') }}">Import Documents</x-sidebar.sub-link>
    </x-sidebar.group>
</x-sidebar.section>
@endcan

<!-- Outgoing -->
@can('manage_outgoing')
<x-sidebar.section label="Delivery">
    <x-sidebar.group label="Outgoing Delivery" icon="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" active="{{ request()->routeIs('outgoing.*') }}">
        <x-sidebar.sub-link href="{{ route('outgoing.customer-po.index') }}" active="{{ request()->routeIs('outgoing.customer-po.*') }}">Customer PO</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('outgoing.delivery-orders.index') }}" active="{{ request()->routeIs('outgoing.delivery-orders.*') }}">Delivery Orders</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('delivery.outgoing.index') }}" active="{{ request()->routeIs('delivery.outgoing.index') }}">Delivery Dashboard</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('planning.forecasts.index') }}" active="{{ request()->routeIs('planning.forecasts.index') }}">Forecast Upload</x-sidebar.sub-link>
    </x-sidebar.group>
</x-sidebar.section>
@endcan

<!-- Subcount -->
@can('manage_subcounts')
<x-sidebar.section label="Subcount">
    <x-sidebar.group label="Subcount Management" icon="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" active="{{ request()->routeIs('subcounts.*') || request()->routeIs('subcon.*') }}">
        <x-sidebar.sub-link href="{{ route('subcounts.index') }}" active="{{ request()->routeIs('subcounts.*') }}">Subcount Process</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('subcon.index') }}" active="{{ request()->routeIs('subcon.*') }}">Subcon Order</x-sidebar.sub-link>
    </x-sidebar.group>
</x-sidebar.section>
@endcan

<!-- Inventory -->
@can('manage_inventory')
<x-sidebar.section label="Inventory">
    <x-sidebar.group label="Stock Ledger" icon="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" active="{{ request()->routeIs('inventory.*') || request()->routeIs('warehouse.*') || request()->routeIs('stock-card.*') }}">
        <x-sidebar.sub-link href="{{ route('stock-card.index') }}" active="{{ request()->routeIs('stock-card.*') }}">Stock Card (Saldo)</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('warehouse.stock.index') }}" active="{{ request()->routeIs('warehouse.stock.index') }}">Stock by Location</x-sidebar.sub-link>
    </x-sidebar.group>

    <x-sidebar.group label="Lokasi" icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" active="{{ request()->routeIs('inventory.locations.*') }}">
        <x-sidebar.sub-link href="{{ route('inventory.locations.index') }}" active="{{ request()->routeIs('inventory.locations.index') }}">Warehouse Locations</x-sidebar.sub-link>
    </x-sidebar.group>

    <x-sidebar.group label="Operasional" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" active="{{ request()->routeIs('warehouse.bin-transfers.*') || request()->routeIs('warehouse.batch-transfers.*') || request()->routeIs('warehouse.stock-adjustments.*') || request()->routeIs('warehouse.stock-opname.*') || request()->routeIs('warehouse.stock.reconcile') || request()->routeIs('warehouse.qc.*') || request()->routeIs('warehouse.putaway.*') }}">
        <x-sidebar.sub-link href="{{ route('warehouse.bin-transfers.index') }}" active="{{ request()->routeIs('warehouse.bin-transfers.*') || request()->routeIs('warehouse.batch-transfers.*') }}">Transfer (Bin &amp; Batch)</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('warehouse.stock-adjustments.index') }}" active="{{ request()->routeIs('warehouse.stock-adjustments.*') }}">Stock Adjustments</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('warehouse.stock-opname.index') }}" active="{{ request()->routeIs('warehouse.stock-opname.*') }}">Stock Opname</x-sidebar.sub-link>
        <x-sidebar.sub-link href="{{ route('warehouse.stock.reconcile') }}" active="{{ request()->routeIs('warehouse.stock.reconcile') }}">Reconcile Stock</x-sidebar.sub-link>
    </x-sidebar.group>

    <x-sidebar.group label="Penerimaan" icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" active="{{ request()->routeIs('inventory.receives.*') }}">
        <x-sidebar.sub-link href="{{ route('receives.index') }}" active="{{ request()->routeIs('inventory.receives.*') || request()->routeIs('receives.index') }}">Inventory Receives</x-sidebar.sub-link>
    </x-sidebar.group>
</x-sidebar.section>
@endcan

<!-- Admin -->
@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin'))
<x-sidebar.section label="Admin">
    <x-sidebar.link href="{{ route('admin.users.index') }}" icon="M17 20h5V4H2v16h5 M9 20v-4a3 3 0 0 1 6 0v4 M9 7a3 3 0 1 0 6 0 3 3 0 0 0-6 0Z" active="{{ request()->routeIs('admin.users.*') }}">
        User Management
    </x-sidebar.link>
    <x-sidebar.link href="{{ route('admin.roles.index') }}" icon="M9 12h6m-6 4h6m-7-8h8a2 2 0 0 1 2 2v8H6v-8a2 2 0 0 1 2-2Z M8 8V6a4 4 0 1 1 8 0v2" active="{{ request()->routeIs('admin.roles.*') }}">
        Role Management
    </x-sidebar.link>
</x-sidebar.section>
@endif
