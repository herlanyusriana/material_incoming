<?php

$file = 'c:\\Users\\HYPE AMD\\Project\\material_incoming\\app\\Http\\Controllers\\ProductionOrderController.php';
$content = file_get_contents($file);

// checkMaterial replacement
$content = preg_replace(
    '/(if \(!\$part\?->id \|\| \$needed <= 0\) \{\s+continue;\s+\})\s+(\$inventory = GciInventory::firstOrCreate\()/s',
    "$1\n\n                if (isset(\$part->is_backflush) && !\$part->is_backflush) {\n                    continue;\n                }\n\n                $2",
    $content
);

// kanbanUpdate legacy fallback replacement
$content = preg_replace(
    '/(if \(\$mob === \'free_issue\'\) \{\s+continue;\s+\})\s+(\$consumedQty = \(float\) \(\$item->net_required \?\? \$item->usage_qty \?\? 0\) \* \$qtyGood;)/s',
    "$1\n\n                        \$isBackflush = \$item->componentPart->is_backflush ?? true;\n                        if (!\$isBackflush) {\n                            continue;\n                        }\n\n                        $2",
    $content
);

// kanbanUpdate bom loadMissing replacement
$content = preg_replace(
    '/(if \(\$bom\) \{)\s+(foreach \(\$bom->items as \$item\) \{)/s',
    "$1\n                    \$bom->loadMissing('items.componentPart');\n                    $2",
    $content
);

file_put_contents($file, $content);
echo "Replaced.";
