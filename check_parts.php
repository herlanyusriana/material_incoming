<?php

$parts = DB::table('gci_parts')->select('id', 'part_no', 'classification', 'status')->orderBy('id')->get();

foreach ($parts as $p) {
    echo $p->id . ": " . $p->part_no . " [class=" . $p->classification . "] [status=" . $p->status . "]\n";
}

echo "\n--- Parts without FG or not active: ---\n";
$nonFg = DB::table('gci_parts')
    ->where(function ($q) {
        $q->where('classification', '!=', 'FG')
            ->orWhereNull('classification')
            ->orWhere('status', '!=', 'active')
            ->orWhereNull('status');
    })
    ->select('id', 'part_no', 'classification', 'status')
    ->get();

foreach ($nonFg as $p) {
    echo $p->id . ": " . $p->part_no . " [class=" . $p->classification . "] [status=" . $p->status . "]\n";
}
