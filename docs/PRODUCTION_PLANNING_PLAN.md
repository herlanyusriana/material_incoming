# Production Module - Revamp Plan

## Overview
Revamp the Production module to match the GCI Production Flow:

### Flow Production:
1. **Daily Planning** (from Outgoing module) → provides production requirements
2. **Production Requirement** → auto-calculated from Daily Planning data
3. **Production Planning** (GCI Planning Produksi) → by Machine, spreadsheet-like
   - Fill qty production/sequence
   - Fill Shift 1, 2 or 3
   - Generate MO/WO
   - Check Material & Dies availability
4. **Start Production** → 1st Quality Inspection (APK mobile)
5. **Mass Production** → In-process Inspection (APK mobile)
6. **Finish Production** → Final Inspection (APK mobile)
7. **MO/WO Completed** → Inventory

### GCI Planning Produksi Table Structure:
- **Grouped by Machine (MESIN)**: e.g., "Back Plate / Plate Rear - PTL Dongshi", "TPL", "Comp Base", etc.
- **Columns**:
  - MESIN (Machine group)
  - PART NAME
  - Stock Finish Good: FG LG, FG GCI
  - Urutan Produksi (Plan GCI): sequence number
  - Production/day quantity
  - FG Stock vs Planning LG: daily columns (dates)
  - Remark (LG Plan / GCI Stock)
- **Calculations**:
  - Row 1: Stock values (starting from FG stock, then daily balance)
  - Row 2: Difference values (stock - plan requirement)
  - Color coding: Red for negative, Yellow for warning

## Database Changes:
1. `production_machines` table - Master data for machines
2. `production_planning_sessions` table - Planning sessions per date
3. `production_planning_lines` table - Each row in the planning table
4. Modify existing `production_orders` to link to planning lines

## Files to Create/Modify:
- Migration: production planning tables
- Model: ProductionMachine, ProductionPlanningSession, ProductionPlanningLine
- Controller: ProductionPlanningController
- View: production/planning/index.blade.php
- Sidebar: Add Production Planning link
- Routes: Add planning routes
