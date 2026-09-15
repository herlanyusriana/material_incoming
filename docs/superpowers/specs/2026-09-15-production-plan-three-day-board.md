# Production Plan Three-Day Board Specification

## Goal

Replace the current shift-oriented Production Planning page with a compact, full-width board based on calendar days.

## Approved behavior

- The page stays inside the existing Production module and does not add or enable a sidebar.
- `D`, `D+1`, and `D+2` are calendar dates starting from the selected Start Date.
- Each daily quantity is persisted in the planning session and planning line for that actual date.
- A generated WO uses the date belonging to the daily quantity, not the currently viewed date and not a renamed shift field.
- One visible row represents one FG part. Machine and WIP information come from the active BOM routing.
- Rows are grouped and ordered by BOM machine, then by the user-controlled production order.
- Up/down actions change the production priority consistently across the visible date window.
- The detail action exposes existing WOs for that row and links to the normal WO detail page.
- The add-WO action generates missing WOs for the row's non-zero daily quantities.
- Delete removes the part from the visible planning window only; it never deletes Part Master, BOM, or generated WOs.
- Quantity fields save on blur or Enter and show saving, saved, and error feedback without a full-page reload.
- Desktop uses a full-width compact table. Small screens use stacked part cards and never force page-level horizontal scrolling.
- Existing shift quantity columns remain in the database for backward compatibility, but the new board does not present them as days.

## Visual direction

- Minimal industrial operations UI: white surfaces, slate text, blue primary action, restrained borders, no gradients or decorative cards.
- One primary CTA (`+ WO Baru`); filters and row actions remain visually secondary.
- Icon-only controls have tooltips, accessible names, visible focus states, and usable pointer targets.
- Destructive actions require confirmation and are spatially separated from create/view actions.
