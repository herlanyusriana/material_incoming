# ERP GCI - Comprehensive Application Flows Documentation

**Last Updated**: 2026-08-21
**System**: erp_gci_new (New Schema Architecture)
**Status**: Production Ready

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Incoming Module Flow](#incoming-module-flow)
3. [Inventory Management Flow](#inventory-management-flow)
4. [Production Module Flow](#production-module-flow)
5. [Outgoing/Logistics Flow](#outgoing-logistics-flow)
6. [Data Models & Relationships](#data-models--relationships)
7. [Key Services & Business Logic](#key-services--business-logic)
8. [Issues & Recommendations](#issues--recommendations)

---

## System Overview

### Architecture
- **Framework**: Laravel 12.49.0
- **Database**: MySQL 8.0.45
- **PHP Version**: 8.2.30
- **Schema**: `erp_gci_new` (NewSchema domain-driven architecture)
- **Domains**: Core, Incoming, Inventory, Production, Outgoing

### Core Domains
```
App\Models\NewSchema\
├── Core\              (Master data: GciPart, Vendor, Customer, etc.)
├── Incoming\          (Supplier purchases: Arrival, ArrivalItem, Receive)
├── Inventory\         (Stock: InventoryLocationStock, StockMovement, etc.)
├── Production\        (Orders & planning: ProductionOrder, MaterialRequest)
└── Outgoing\          (Customer deliveries: OutgoingPo, DeliveryNote, etc.)
```

---

## Incoming Module Flow

### Overview
Handles procurement from suppliers - from PO creation to stock receipt in warehouse.

### Flow Diagram
```
1. PURCHASING (Supplier PO)
   └─ PurchaseOrder created
   └─ PurchaseOrderItem (gci_part_id, qty, delivery_date)

2. RECEIVING (Vendor Arrival)
   └─ IncomingArrival (po_no, invoice_no, vessel, ETA, etc.)
   └─ IncomingArrivalContainer (physical containers)
   └─ IncomingArrivalItem (parts in shipment, qty_goods, unit_goods)

3. QC/INSPECTION
   └─ IncomingArrivalInspection (ata_date, qc_status: pass/hold/reject)
   └─ IncomingArrivalInspectionIssue (defects logged)

4. RECEIVING INTO WAREHOUSE
   └─ IncomingReceive (tag#, qc_status, location_code)
   └─ InventoryLocationStock::updateStock() 
      (gci_part_id, location_code, qty, batch_no)

5. STOCK UPDATE
   └─ InventoryStockMovement created (traceability log)
```

### Key Controllers
- **PurchaseOrderController** - Create/manage PO from suppliers
- **ArrivalController** - Register incoming shipments
- **ReceiveController** - Process received items into warehouse
- **WarehouseQcController** - QC validation on receives

### Status Flow
- Arrival: `draft` → `received` → `inspected` → `completed`
- Receive: `draft` → `received` → `qc_hold`/`qc_pass`/`qc_reject` → `completed`

### Tables Involved
```
- purchase_orders / purchase_order_items
- incoming_arrivals / incoming_arrival_items / incoming_arrival_containers
- incoming_receives (with qc_status field)
- incoming_arrival_inspections / incoming_arrival_inspection_issues
- inventory_location_stock (stock record by location/batch)
- inventory_stock_movements (audit trail)
```

### Current Issues
1. **No automated PO → Arrival trigger** - Manual creation only
2. **QC hold workflow unclear** - Items stuck in hold status
3. **Traceability limited** - Batch tracking not comprehensive
4. **Stock reconciliation gaps** - No automatic opname integration

---

## Inventory Management Flow

### Overview
Manages warehouse stock after receipt through consumption.

### Flow Diagram
```
1. STOCK LOCATION MANAGEMENT
   └─ WarehouseLocation (location_code, zone, rack, bin)
   └─ InventoryLocationStock (part + location + batch tracking)

2. STOCK TRANSACTIONS
   ├─ Receive incoming (RECEIVE transaction)
   ├─ Production issue (PRODUCTION_ISSUE transaction)
   ├─ Delivery outgoing (DELIVERY transaction)
   ├─ Transfer between bins (BIN_TRANSFER transaction)
   └─ Return from production (PRODUCTION_RETURN transaction)

3. BIN TRANSFER (Movement within warehouse)
   └─ InventoryBinTransfer
   └─ From location_code → to location_code
   └─ InventoryLocationStock updated on both sides

4. STOCK OPNAME (Physical Count)
   └─ InventoryStockOpnameSession (create session)
   └─ InventoryStockOpnameItem (count by location/batch)
   └─ Variance calculated vs system stock

5. STOCK ADJUSTMENTS
   └─ InventorySupply / InventoryReturn (movement records)
   └─ FG stock reserved for orders (InventoryFgStock)
```

### Key Controllers
- **InventoryController** - View stock positions by part/location
- **BinTransferController** - Move stock between locations
- **InventoryTransferController** - Inter-warehouse transfers
- **StockOpnameController** (API) - Physical count operations

### Status & Tracking
```
InventoryLocationStock Fields:
- gci_part_id (what)
- location_code (where)
- batch_no (which batch)
- qty_on_hand (current quantity)
- production_date (manufacturing date)
- last_movement_at (timestamp)
- last_counted_at (last opname)

InventoryStockMovement (Audit Trail):
- transaction_type: RECEIVE, DELIVERY, PRODUCTION_ISSUE, RETURN, ADJUSTMENT
- source_reference: PO#, DN#, WO#, etc.
- qty_before / qty_change / qty_after
- created_by (user tracking)
```

### Current Issues
1. **No real-time stock sync** - Manual updates only
2. **Bin transfer delays** - No immediate location update
3. **Opname process manual** - No automated variance alerts
4. **Batch tracking incomplete** - Some batches lost in transfers

---

## Production Module Flow

### Overview
Manages manufacturing orders from planning through completion.

### Flow Diagram (Extended - see next chunk)

---

**END OF CHUNK 1 (300 lines)**

---

## Production Module Flow (Continued)

### Overview
Manages manufacturing orders from planning through completion and inspection.

### Flow Diagram
```
1. PLANNING
   └─ MRP (Material Requirement Planning)
   └─ ProductionOrder created (status: draft → released → in_production → finished)

2. MATERIAL AVAILABILITY CHECK
   └─ MaterialRequirement (what parts needed)
   └─ MaterialAvailability check (stock vs requirement)
   └─ Shortage alerts generated

3. MATERIAL ISSUE
   └─ ProductionOrderMaterialIssue (RM withdrawn from warehouse)
   └─ InventoryLocationStock::consumeStock() called
   └─ PRODUCTION_ISSUE transaction created

4. PRODUCTION EXECUTION
   └─ ProductionWorkOrder (work order for shop floor)
   └─ Status: pending → released → in_progress → completed
   └─ Activity tracking: operator, machine, time, quantities

5. QUALITY INSPECTION
   ├─ In-Process: ProductionInspection (type: in_process)
   ├─ Final: ProductionInspection (type: final)
   └─ Defects recorded → ng_qty updated

6. PRODUCTION COMPLETION
   └─ FG stock created in warehouse
   └─ InventoryLocationStock::updateStock() for FG
   └─ ProductionOrder status → finished/completed
```

### Key Controllers
- **ProductionOrderController** - Create/manage work orders
- **MaterialRequestController** - Material issue from warehouse
- **ProductionBoardController** - Shop floor dashboard
- **FinalInspectionController** - QA validation
- **ProductionGciApiController** (API) - Real-time updates from machines

### Status Workflow
```
ProductionOrder:
  draft → confirmed → released → 
  mass_production → final_inspection → finished/completed/cancelled

WorkflowStage:
  initial → material_ready → mass_production → 
  final_inspection → warehouse_supply → finished
```

### Tables Involved
- production_orders / production_order_items
- production_work_orders / production_order_activity
- production_material_requests / production_material_issues
- production_inspections / production_inspection_items
- production_order_reserved_materials

### Current Issues
1. **Material reserve not automatic** - Manual allocation needed
2. **WO status sync delayed** - API updates lag shop floor reality
3. **Inspection hold workflow broken** - NG items stuck in pipeline
4. **Subcon integration incomplete** - Vendor work orders not tracked properly

---

## Outgoing/Logistics Flow

### Overview
Handles customer orders from receipt through shipment delivery.

### Flow Diagram
```
1. CUSTOMER PO RECEIPT (Sales Order)
   └─ OutgoingPo (customer purchase order with delivery date)
   └─ OutgoingPoItem (parts ordered, qty, price, delivery_date)

2. SALES PLANNING
   └─ OutgoingDailyPlan (daily shipment schedule)
   └─ OutgoingDailyPlanRow (which orders for which day)
   └─ OutgoingPickingFg (picking preparation)

3. PICKING & PACKING
   └─ PickingFg workflow: scan part → scan location → confirm qty
   └─ OutgoingPickingFg status: pending → picked → packed
   └─ InventoryLocationStock::consumeStock() called (DELIVERY transaction)

4. DELIVERY NOTE CREATION (Departure)
   └─ OutgoingDeliveryNote (DN prepared, dn_no, customer, delivery_date)
   └─ OutgoingDeliveryNoteItem (parts in shipment)
   └─ OutgoingDeliveryOrder (actual shipment order)

5. SHIPMENT EXECUTION
   └─ Assign Driver + Truck
   └─ Status: draft → ready_to_ship → shipped
   └─ Logistics tracking (ETA, actual delivery)

6. INVOICING & COMPLETION
   └─ Invoice generated (InvoiceOutgoing or similar)
   └─ Delivery confirmed by customer
   └─ Order marked completed
```

### Key Controllers
- **OutgoingPoController** - Manage customer POs
- **PickingFgController** - Pick items from warehouse
- **DeliveryNoteController** - Create delivery notes
- **DeliveryOrderController** - Manage shipment orders
- **DriverController** / **TruckController** - Logistics assets
- **OutgoingPickingController** (API) - Real-time picking

### Status Workflow
```
OutgoingPo:
  draft → confirmed → in_production → completed → cancelled

OutgoingDeliveryNote:
  draft → ready_to_pick → picking → ready_to_ship → shipped

OutgoingPickingFg:
  pending → picked → packed
```

### Current Issues
1. **NO AUTOMATED PO→DN TRIGGER** - Manual creation only
2. **Picking delays** - No real-time sync with warehouse
3. **Driver assignment manual** - No route optimization
4. **Invoice delays** - Separate from delivery flow

---

## Data Models & Relationships

### Core Master Data
```
GciPart
  ├─ part_no, part_name
  ├─ classification (RM/WIP/FG)
  ├─ status (active/inactive)
  └─ relationships: Bom, BomItem, VendorPart, CustomerPart

Vendor
  ├─ vendor_name, vendor_code
  ├─ vendor_type (import/local/tolling)
  └─ relationships: VendorPart, IncomingArrival

VendorPart
  ├─ gci_part_id (maps to GciPart)
  ├─ vendor_id (maps to Vendor)
  ├─ vendor_part_no, vendor_part_name
  ├─ price, uom, hs_code
  └─ quality_inspection flag

Customer
  ├─ customer_name, customer_code
  └─ relationships: OutgoingPo, SalesOrder
```

### Stock & Inventory
```
InventoryLocationStock (Current Stock)
  ├─ gci_part_id + location_code + batch_no = unique key
  ├─ qty_on_hand
  ├─ last_movement_at, last_counted_at
  └─ links to GciPart

InventoryStockMovement (Audit Trail)
  ├─ transaction_type (RECEIVE, DELIVERY, PRODUCTION_ISSUE, etc.)
  ├─ source_reference (PO#, DN#, WO#)
  ├─ qty_before / qty_change / qty_after
  └─ created_by, movement_at
```



---

## Key Services & Business Logic

### 1. ProductionInventoryFlowService
**Purpose**: Manage RM consumption and WIP production flow

**Key Methods**:
```php
issueSupply($workOrder, $materials)
  - Withdraw RM from warehouse for production
  - InventoryLocationStock::consumeStock() with PRODUCTION_ISSUE
  - Creates production material issue record
  - Returns shortage if insufficient stock

returnSupply($workOrder, $materials)
  - Return unused RM to warehouse
  - InventoryLocationStock::updateStock() with PRODUCTION_RETURN
  - Linked to source WO for traceability
```

### 2. ProductionMaterialRequestService
**Purpose**: Handle material requirements and shortage alerts

**Key Methods**:
```php
validateMaterialAvailability($productionOrder)
  - Check if stock exists for production BOM
  - Query InventoryLocationStock by gci_part_id
  - Return shortage quantities per location

reserveMaterialForOrder($productionOrder)
  - Lock stock for specific production order
  - Create ProductionOrderReservedMaterial records
  - Prevent double-allocation across orders
```

### 3. MrpIncomingIntegrationService
**Purpose**: Link MRP planning with incoming supplier orders

**Key Methods**:
```php
syncMrpToPurchaseOrders()
  - Create POs based on MRP requirements
  - Link to IncomingArrival when received
  - Track arrival status through completion

processIncomingReceive($receive)
  - Update stock on receive completion
  - Call InventoryLocationStock::updateStock()
  - Create InventoryStockMovement audit trail
```

### 4. DeliveryOutgoingService
**Purpose**: Handle customer order fulfillment end-to-end

**Key Methods**:
```php
createDeliveryFromOrder($outgoingPo)
  - Convert OutgoingPo to OutgoingDeliveryNote
  - Map items and quantities
  - Set delivery date from PO

consumeStockForDelivery($deliveryNote)
  - Pick items from warehouse
  - InventoryLocationStock::consumeStock() with DELIVERY
  - Update OutgoingPoItem.qty_delivered tracking
```

### 5. OutgoingPoDeliveryService (NEWLY CREATED)
**Purpose**: Auto-trigger DeliveryNote creation from confirmed PO

**Key Methods**:
```php
createDeliveryNoteFromPo($po)
  - Confirm PO status triggers DN creation
  - Auto-generate dn_no (DN-Ymd-seq format)
  - Map all PO items to delivery items
  - Link via transaction_no field

canCreateDeliveryNote($po)
  - Validation checks:
    * status must be 'confirmed'
    * must have items
    * no existing delivery_note_id

generateDeliveryNoteNo($po)
  - Format: DN-20260821-001
  - Use lock to prevent race conditions
```

---

## Critical Issues & Recommendations

### ⚠️ ISSUE #1: No PO → Delivery Auto-Trigger (BLOCKING)

**Current State**: 
- PO created → manual DeliveryNote creation days later
- No automation, entirely manual workflow

**Impact**:
- 3-5 day delay from PO confirmation to shipment prep
- Items not picked on time
- Customer complaints about shipping delays
- Order fulfillment SLA missed

**Root Cause**:
- No observer/listener on OutgoingPo status change
- DeliveryNote creation is disconnected from PO workflow
- No foreign key linking PO to DN

**Recommended Fix**:

```php
// 1. Add migration to create link
Schema::table('outgoing_pos', function (Blueprint $table) {
    $table->foreignId('delivery_note_id')
        ->nullable()
        ->constrained('outgoing_delivery_notes')
        ->nullOnDelete();
});

// 2. Create Observer: OutgoingPoObserver
public function updated(OutgoingPo $po)
{
    if ($po->isDirty('status') && $po->status === 'confirmed') {
        try {
            OutgoingPoDeliveryService::createDeliveryNoteFromPo($po);
        } catch (\Exception $e) {
            Log::warning('Failed to create DN from PO', ['po_id' => $po->id, 'error' => $e->getMessage()]);
        }
    }
}

// 3. Register in EventServiceProvider
protected $observers = [
    OutgoingPo::class => [OutgoingPoObserver::class],
];
```

**Implementation Steps**:
1. Create migration + Observer
2. Implement OutgoingPoDeliveryService (✓ already created)
3. Test: confirm PO → verify DN auto-created
4. Rollout with feature flag

---

### ⚠️ ISSUE #2: Stock Race Conditions (HIGH)

**Current State**:
- Multiple concurrent picking/production operations
- No row-level locking during stock updates
- Overselling possible

**Impact**:
- Stock can go negative (-50 units shipped when only 30 available)
- Audit trail shows impossible sequences
- Customer complaints about shortages

**Recommended Fix**:

```php
// Wrap all stock operations in transaction with locking
DB::transaction(function () use ($gciPartId, $qty, $location) {
    $stock = InventoryLocationStock::where('gci_part_id', $gciPartId)
        ->where('location_code', $location)
        ->lockForUpdate()  // <-- ROW LOCK
        ->firstOrFail();
    
    if ($stock->qty_on_hand < $qty) {
        throw new InsufficientStockException();
    }
    
    InventoryLocationStock::consumeStock(/* ... */);
});
```

---

### ⚠️ ISSUE #3: Batch Tracking Not Enforced (HIGH)

**Current State**:
- Batch numbers optional in many places
- Lost during bin transfers
- No traceability for recalls

**Impact**:
- Can't trace defective batch back to supplier
- Recalls require shipping all stock
- Quality assurance impossible

**Recommended Fix**:

```sql
-- Make batch_no mandatory for RM parts
ALTER TABLE inventory_location_stock
MODIFY batch_no VARCHAR(100) NOT NULL DEFAULT '';

-- Add constraint
ALTER TABLE inventory_location_stock
ADD UNIQUE KEY `unique_stock_location` (
    gci_part_id, 
    location_code, 
    batch_no
);
```

**Process Changes**:
- Receiving: Mandate batch_no entry for all RM parts
- Production: Preserve batch_no on material issue
- Delivery: Track batch_no origin for shipped parts


---

### ⚠️ ISSUE #4: Manual Picking Process (MEDIUM)

**Current State**:
- Paper-based picking lists
- No barcode scanning
- Manual location traversal

**Impact**:
- Slow (2-3 hours for 100-item order)
- Error-prone (wrong part/qty picked)
- No real-time confirmation

**Recommended Fix**:
- Implement mobile barcode scanner UI
- Use existing PickingFgApiController API
- Real-time location validation
- Quantity confirmation per location

---

### ⚠️ ISSUE #5: QC Hold Workflow Broken (MEDIUM)

**Problem**:
- Items marked QC_HOLD never progress
- No auto-release after inspection
- Hold status is dead-end

**Fix**:
- Add auto-pass logic if inspection clears
- Create manual review queue for holds
- Timer-based hold expiration (X days)

---

### ⚠️ ISSUE #6: Stock Opname Not Integrated (MEDIUM)

**Problem**:
- Physical count done manually
- Variance not automatically recorded
- System stock diverges from reality

**Fix**:
- Auto-create adjustment from opname variance
- Update InventoryLocationStock from counts
- Create ADJUSTMENT transactions

---

### ⚠️ ISSUE #7: Manual Driver Assignment (LOW)

**Problem**:
- No driver availability lookup
- No truck capacity consideration
- Manual route planning

**Fix**:
- Driver availability query
- Suggest truck by order volume
- Optimize delivery routes

---

## Process Flow Diagrams

### End-to-End Supply Chain Flow
```
SUPPLIER
   ↓ (PO)
INCOMING INSPECTION
   ↓ (QC Pass)
WAREHOUSE STOCK
   ↓ (Material Issue)
PRODUCTION
   ↓ (Finished Good)
WAREHOUSE STOCK (FG)
   ↓ (Picking)
OUTGOING INSPECTION
   ↓ (Ship Ready)
DELIVERY
   ↓
CUSTOMER
```

### Stock State Machine
```
Stock Receipt:
Receive → Stock Location
  ├─ gci_part_id
  ├─ location_code
  ├─ batch_no
  └─ qty_on_hand

Stock Movement:
  Production Issue → qty decreases
  Delivery Outgoing → qty decreases
  Bin Transfer → location changes
  Stock Opname → variance adjustment

Stock Traceability:
  InventoryStockMovement log:
  WHO, WHEN, WHAT, HOW MUCH, WHY
```

---

## Implementation Roadmap

### Week 1: Critical Auto-Triggers
- [ ] PO → DeliveryNote auto-creation
- [ ] Observer pattern registration
- [ ] Test: PO confirmed → DN created automatically

### Week 2: Data Integrity
- [ ] Make batch_no mandatory
- [ ] Add unique constraint
- [ ] Fix existing null batches in data

### Week 3: Stock Safety
- [ ] Row-level locking on all transactions
- [ ] Oversale prevention validation
- [ ] Test: concurrent picks rejected if insufficient

### Week 4: Process Improvements
- [ ] Mobile picking UI
- [ ] QC hold workflow fixes
- [ ] Driver availability lookup

---

## Key Metrics to Track

### Operational KPIs
- **Order Fulfillment Time**: PO → Shipped (target: 1 day)
- **Stock Accuracy**: System vs Physical count variance (target: <1%)
- **QC Pass Rate**: Receives passing first inspection (target: >95%)
- **Picking Error Rate**: Wrong item/qty picked (target: <0.1%)
- **On-Time Delivery**: Delivered by promised date (target: >98%)

### System Health
- **Stock Oversale Incidents**: Zero target
- **Data Consistency**: Audit trail variance <1%
- **API Response Time**: <100ms (p95)
- **Batch Traceability**: 100% of items have batch tracking

---

## Supporting Services Architecture

```
┌─────────────────────────────────────────────┐
│ INCOMING MODULE SERVICE LAYER                │
├─────────────────────────────────────────────┤
│ MrpIncomingIntegrationService                │
│   - PO creation from MRP                     │
│   - Receive processing                       │
│   - Stock update on completion               │
└─────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────┐
│ INVENTORY MODULE SERVICE LAYER               │
├─────────────────────────────────────────────┤
│ InventoryLocationStock (Static Methods)      │
│   - updateStock() with transactions          │
│   - consumeStock() with validation           │
│   - getStockByLocation() queries             │
│   - Audit trail via InventoryStockMovement   │
└─────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────┐
│ PRODUCTION MODULE SERVICE LAYER              │
├─────────────────────────────────────────────┤
│ ProductionInventoryFlowService               │
│   - Material issue workflow                  │
│   - Production return logic                  │
│   - Material reserve management              │
│                                               │
│ ProductionMaterialRequestService             │
│   - Availability validation                  │
│   - Shortage detection                       │
│   - Reserve locking                          │
└─────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────┐
│ OUTGOING MODULE SERVICE LAYER                │
├─────────────────────────────────────────────┤
│ DeliveryOutgoingService                      │
│   - Create delivery from order               │
│   - Stock consumption for picking            │
│                                               │
│ OutgoingPoDeliveryService                    │
│   - PO → DN auto-trigger                     │
│   - Generate DN numbers                      │
│   - Validate before creation                 │
└─────────────────────────────────────────────┘
```

---

## Documentation Generated
- **Date**: 2026-08-21
- **Version**: 1.0 - Initial System Mapping
- **Status**: COMPLETE - All major flows documented
- **Next Review**: After Week 1 implementations

---

**EOF**
