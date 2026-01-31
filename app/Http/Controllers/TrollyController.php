<?php

namespace App\Http\Controllers;

use App\Models\Trolly;
use App\Support\QrSvg;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrollyController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $kind = trim((string) $request->query('kind', ''));

        $trollies = Trolly::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('code', 'like', '%' . strtoupper($search) . '%');
            })
            ->when($type !== '', fn($q) => $q->where('type', strtoupper($type)))
            ->when($kind !== '', fn($q) => $q->where('kind', strtoupper($kind)))
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('warehouse.trollies.index', compact('trollies', 'search', 'type', 'kind'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('trollies', 'code')],
            'type' => ['nullable', 'string', 'max:50'],
            'kind' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $type = strtoupper(trim((string) ($validated['type'] ?? '')));
        $kind = strtoupper(trim((string) ($validated['kind'] ?? '')));

        Trolly::create([
            'code' => $code,
            'type' => $type ?: null,
            'kind' => $kind ?: null,
            'status' => $validated['status'] ?? 'ACTIVE',
            'qr_payload' => Trolly::buildPayload($code, $type, $kind),
        ]);

        return back()->with('success', 'Trolly created successfully.');
    }

    public function update(Request $request, Trolly $trolly)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('trollies', 'code')->ignore($trolly->id)],
            'type' => ['nullable', 'string', 'max:50'],
            'kind' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $type = strtoupper(trim((string) ($validated['type'] ?? '')));
        $kind = strtoupper(trim((string) ($validated['kind'] ?? '')));

        $trolly->update([
            'code' => $code,
            'type' => $type ?: null,
            'kind' => $kind ?: null,
            'status' => $validated['status'] ?? 'ACTIVE',
            'qr_payload' => Trolly::buildPayload($code, $type, $kind),
        ]);

        return back()->with('success', 'Trolly updated successfully.');
    }

    public function destroy(Trolly $trolly)
    {
        $trolly->delete();
        return back()->with('success', 'Trolly deleted.');
    }

    public function export()
    {
        $filename = 'trollies_' . date('Y-m-d_His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\TrolliesExport(), $filename);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\TrolliesImport(), $request->file('file'));

        return back()->with('success', 'Trollies imported successfully.');
    }

    public function printQr(Trolly $trolly)
    {
        $payload = $trolly->qr_payload;
        if (!$payload) {
            $payload = Trolly::buildPayload($trolly->code, $trolly->type, $trolly->kind);
        }
        $qrSvg = QrSvg::make($payload, 260, 0);

        return view('warehouse.trollies.print_qr', compact('trolly', 'qrSvg'));
    }
}
