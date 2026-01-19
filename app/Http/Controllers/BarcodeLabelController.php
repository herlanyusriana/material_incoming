<?php

namespace App\Http\Controllers;

use App\Models\GciPart;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeLabelController extends Controller
{
    /**
     * Print label for a single part
     */
    public function printPartLabel(GciPart $part)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcode = $part->generateBarcode();
        
        // Generate barcode image (Code 128)
        $barcodeImage = base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128));
        
        return view('warehouse.labels.part_label', compact('part', 'barcodeImage', 'barcode'));
    }

    /**
     * Print bulk labels
     */
    public function printBulkLabels(Request $request)
    {
        $validated = $request->validate([
            'part_ids' => 'required|array',
            'part_ids.*' => 'exists:gci_parts,id',
        ]);

        $parts = GciPart::whereIn('id', $validated['part_ids'])->get();
        
        $generator = new BarcodeGeneratorPNG();
        $labels = [];

        foreach ($parts as $part) {
            $barcode = $part->generateBarcode();
            $labels[] = [
                'part' => $part,
                'barcode' => $barcode,
                'barcodeImage' => base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128)),
            ];
        }

        return view('warehouse.labels.bulk_labels', compact('labels'));
    }

    /**
     * Show label printing interface
     */
    public function index(Request $request)
    {
        $query = GciPart::query()->where('status', 'active');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('part_no', 'like', '%' . $search . '%')
                  ->orWhere('part_name', 'like', '%' . $search . '%');
            });
        }

        $parts = $query->orderBy('part_no')->paginate(50);

        return view('warehouse.labels.index', compact('parts'));
    }
}
