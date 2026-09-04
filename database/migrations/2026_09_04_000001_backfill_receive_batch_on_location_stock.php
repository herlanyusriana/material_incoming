<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Backfill the receive identity onto existing inventory_location_stock rows.
     *
     * Before the identity fix (commit "fix(receive): write receive tag as
     * batch_no on putaway/issue"), putaway/issue wrote the physical label tag
     * only into the movement log and left inventory_location_stock.batch_no
     * blank (''). Production FEFO and resolveIncomingTraceability() key on
     * batch_no, so existing on-hand stock was untraceable to its source receive.
     *
     * This migration repairs that historic data. The gci_part_id is not stored
     * directly on the receive, so we resolve it transitively:
     *
     *   incoming_receives (tag, location_code, arrival_item_id, qc_status)
     *     JOIN incoming_arrival_items (gci_part_id, id = arrival_item_id)
     *
     * and match to inventory_location_stock on gci_part_id + location_code,
     * writing batch_no = receive.tag. Only blank-batch, non-deleted rows that
     * resolve to a single pass receive are touched; where a part+location maps to
     * multiple pass receives or none at all, the row is left untouched rather
     * than guessed. (Janus: no such ambiguous case exists today, but the guard
     * keeps the migration safe if that changes.)
     */
    public function up(): void
    {
        foreach (['incoming_receives', 'incoming_arrival_items', 'inventory_location_stock'] as $t) {
            if (!Schema::hasTable($t)) {
                return;
            }
        }

        // @prophet apply via single UPDATE-join
        DB::statement(
            "UPDATE inventory_location_stock s
                JOIN (
                    SELECT r.location_code,
                           ai.gci_part_id,
                           MAX(r.tag) AS tag,
                           COUNT(DISTINCT r.tag) AS tags
                    FROM incoming_receives r
                    JOIN incoming_arrival_items ai ON ai.id = r.arrival_item_id
                    WHERE r.qc_status = 'pass'
                      AND (r.tag IS NOT NULL AND r.tag <> '')
                      AND r.deleted_at IS NULL
                    GROUP BY r.location_code, ai.gci_part_id
                    HAVING COUNT(DISTINCT r.id) = 1 AND COUNT(DISTINCT r.tag) = 1
                ) t
                   ON t.location_code = s.location_code
                  AND t.gci_part_id = s.gci_part_id
               SET s.batch_no = t.tag
             WHERE (s.batch_no IS NULL OR s.batch_no = '')
               AND s.deleted_at IS NULL"
        );
    }

    public function down(): void
    {
        // Intentionally no-op: batch_no is an identity for on-hand stock; there is
        // no safe reverse mapping back to a blank value without risking data loss.
    }
};