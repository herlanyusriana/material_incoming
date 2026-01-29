<?php
echo "PO Count: " . App\Models\CustomerPo::where('period', '2026-W02')->count() . PHP_EOL;
echo "Mapping Count: " . App\Models\CustomerPart::where('customer_part_no', 'PN-002')->count() . PHP_EOL;
exit();
