<?php

namespace App\Http\Controllers;

use App\Models\GciPart;
use App\Support\QrSvg;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeLabelController extends Controller
{
    /**
     * Print label for a single part
     */
    public function printPartLabel(GciPart $part, Request $request)
    {
        $batch = strtoupper($request->query('batch', ''));
        $generator = new BarcodeGeneratorPNG();
        $barcode = $part->generateBarcode();

        // Generate barcode image (Code 128)
        $barcodeImage = base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128));

        // Use simplified payload (Just the Barcode) for compatibility
        $payloadString = (string) $barcode;
        $qrSvg = QrSvg::make($payloadString, 320, 0);

        return view('warehouse.labels.part_label', compact('part', 'barcodeImage', 'barcode', 'qrSvg', 'batch'));
    }

    public function printLineStockLabel(GciPart $part, Request $request)
    {
        $location = strtoupper(trim((string) $request->query('location', $part->default_location ?: 'LINE-STOCK')));
        if ($location === '') {
            $location = 'LINE-STOCK';
        }

        $payload = [
            'type' => 'LINE_STOCK',
            'gci_part_id' => (int) $part->id,
            'part_no' => (string) $part->part_no,
            'location' => $location,
            'policy' => 'backflush_line_stock',
        ];

        $qrSvg = QrSvg::make(json_encode($payload, JSON_UNESCAPED_SLASHES), 360, 0);

        return view('warehouse.labels.line_stock_label', compact('part', 'location', 'qrSvg', 'payload'));
    }

    /**
     * Print bulk labels
     */
    public function printBulkLabels(Request $request)
    {
        $validated = $request->validate([
            'part_ids' => 'required|array',
            'part_ids.*' => 'exists:gci_parts,id',
            'batch' => 'nullable|string|max:50',
        ]);

        $batch = strtoupper($request->input('batch', ''));
        $parts = GciPart::whereIn('id', $validated['part_ids'])->get();

        $generator = new BarcodeGeneratorPNG();
        $labels = [];

        foreach ($parts as $part) {
            /** @var GciPart $part */
            $barcode = $part->generateBarcode();
            // Use simplified payload (Just the Barcode)
            $payloadString = (string) $barcode;
            $labels[] = [
                'part' => $part,
                'barcode' => $barcode,
                'batch' => $batch,
                'barcodeImage' => base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128)),
                'qrSvg' => QrSvg::make($payloadString, 320, 0),
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
        $policy = trim((string) $request->query('policy', ''));

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('part_no', 'like', '%' . $search . '%')
                    ->orWhere('part_name', 'like', '%' . $search . '%');
            });
        }

        if ($policy === 'line_stock') {
            $query->where('consumption_policy', 'backflush_line_stock');
        }

        $parts = $query->orderBy('part_no')->paginate(50);

        return view('warehouse.labels.index', compact('parts', 'policy'));
    }
}
