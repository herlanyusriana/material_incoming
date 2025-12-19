<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Exports\VendorsExport;
use App\Imports\VendorsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $vendors = Vendor::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('vendor_name', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->select([
                'id',
                'vendor_name',
                'country_code',
                'contact_person',
                'email',
                'phone',
                'status',
                'address',
                'bank_account',
            ])
            ->orderBy('vendor_name')
            ->paginate(15)
            ->withQueryString();

        return view('vendors.index', compact('vendors', 'search', 'status'));
    }

    public function export()
    {
        return Excel::download(new VendorsExport, 'vendors_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Excel::import(new VendorsImport, $request->file('file'));
            });
            return back()->with('status', 'Vendors imported successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'address' => ['nullable', 'string'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
            'signature' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['country_code'] = strtoupper($data['country_code']);

        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('signatures', 'public');
        }
        unset($data['signature']);

        Vendor::create($data);

        return redirect()->route('vendors.index')->with('status', 'Vendor created.');
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'address' => ['nullable', 'string'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
            'signature' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['country_code'] = strtoupper($data['country_code']);

        if ($request->hasFile('signature')) {
            // Delete old signature if exists
            if ($vendor->signature_path) {
                \Storage::disk('public')->delete($vendor->signature_path);
            }
            $data['signature_path'] = $request->file('signature')->store('signatures', 'public');
        }
        unset($data['signature']);

        $vendor->update($data);

        return redirect()->route('vendors.index')->with('status', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')->with('status', 'Vendor archived.');
    }
}
