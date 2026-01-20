<?php
use App\Models\Mps;
use App\Models\GciPart;

$orphans = Mps::whereDoesntHave('part')->count();
echo "Orphaned MPS records count: " . $orphans . "\n";

if ($orphans > 0) {
    $firstOrphan = Mps::whereDoesntHave('part')->first();
    echo "Sample Orphan - ID: " . $firstOrphan->id . ", Part ID: " . $firstOrphan->part_id . "\n";
}
