# Production Plan Three-Day Board Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a calendar-based Production Plan board where daily quantities for D through D+2 create WOs on the correct dates.

**Architecture:** Keep `ProductionPlanningSession` as the per-date aggregate and `ProductionPlanningLine` as the per-part daily record. Add a focused board service that reads a multi-session date window, upserts daily quantities, reorders parts, and deletes rows across that window; the controller remains the HTTP boundary and the Blade page consumes a presentation-ready row collection.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent, Blade, Alpine.js, Tailwind CSS, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-15-production-plan-three-day-board.md`

## Global Constraints

- Do not add, restore, or modify sidebar navigation.
- D/D+1/D+2 are calendar dates, never aliases for Shift 1/2/3.
- Preserve existing shift columns and legacy WO generation compatibility.
- Do not delete Part Master, BOM, or generated WO data when removing a plan row.
- Preserve unrelated dirty-worktree changes.

---

### Task 1: Calendar window persistence

**Files:**
- Create: `app/Services/ProductionPlanningBoardService.php`
- Create: `tests/Feature/Production/ProductionPlanningBoardTest.php`

**Interfaces:**
- Produces: `setDailyQuantity(int $partId, CarbonInterface|string $date, float $qty, ?int $userId): ProductionPlanningLine`
- Produces: `rows(CarbonInterface|string $startDate, int $days = 3): Collection`

- [ ] Write a failing test proving quantities for D and D+1 create separate sessions and lines with the correct dates.
- [ ] Run `php artisan test tests/Feature/Production/ProductionPlanningBoardTest.php` and verify the failure is caused by the missing service.
- [ ] Implement the smallest session/line upsert and three-day row projection.
- [ ] Re-run the focused test and verify it passes.

### Task 2: Window ordering and safe removal

**Files:**
- Modify: `app/Services/ProductionPlanningBoardService.php`
- Modify: `tests/Feature/Production/ProductionPlanningBoardTest.php`

**Interfaces:**
- Produces: `movePart(int $partId, CarbonInterface|string $startDate, int $days, string $direction): Collection`
- Produces: `removePart(int $partId, CarbonInterface|string $startDate, int $days): int`

- [ ] Write failing tests proving reorder is reflected across the date window and removal leaves generated WOs and master data intact.
- [ ] Run the focused tests and verify the expected failures.
- [ ] Implement normalized cross-window sort order and planning-line-only deletion.
- [ ] Re-run the focused tests and verify they pass.

### Task 3: HTTP endpoints and correct-date WO generation

**Files:**
- Modify: `routes/modules/production.php`
- Modify: `app/Http/Controllers/Production/ProductionPlanningController.php`
- Modify: `tests/Feature/Production/ProductionPlanningBoardTest.php`

**Interfaces:**
- Adds JSON endpoints for daily quantity, reorder, and window removal.
- Adds a POST endpoint that generates missing WOs for all non-zero daily lines belonging to one part in the visible window.

- [ ] Write failing feature tests for validation, JSON persistence feedback, and WO `plan_date` values for D and D+1.
- [ ] Run the focused tests and verify failures are caused by missing routes/actions.
- [ ] Implement controller actions using the board service and existing WO material-sync behavior.
- [ ] Re-run the focused tests and verify they pass.

### Task 4: Full-width responsive board UI

**Files:**
- Modify: `resources/views/production/planning/index.blade.php`
- Modify: `tests/Feature/Production/ProductionPlanningBoardTest.php`

**Interfaces:**
- Consumes controller variables `boardRows`, `boardDates`, `planningDays`, and `planDate`.
- Consumes the new JSON/action routes from Task 3.

- [ ] Write a failing response test for actual date headers, machine/FG/WIP columns, labelled actions, and absence of shift labels.
- [ ] Run the focused test and verify it fails against the old view.
- [ ] Replace the crowded page with the compact desktop table, mobile cards, accessible add-part dialog, inline save states, search, date/horizon controls, WO details, reorder, generation, and delete actions.
- [ ] Re-run the focused test and verify it passes.

### Task 5: Regression and visual verification

**Files:**
- Modify only files already listed if verification exposes defects.

- [ ] Run `php artisan test tests/Feature/Production/ProductionPlanningBoardTest.php`.
- [ ] Run relevant production planning and WO tests discovered in the repository.
- [ ] Compile Blade views and frontend assets.
- [ ] Verify the page at 375, 768, 1024, and 1440 CSS pixels, including keyboard focus, reduced motion, no page-level horizontal overflow, and async error feedback.
- [ ] Review the diff to confirm no sidebar or unrelated files changed.
