<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Exports\MachinesExport;
use App\Imports\MachinesImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MachineController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $machines = Machine::query()
            ->search($search)
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('machines.index', compact('machines', 'search', 'status'));
    }

    public function create()
    {
        return view('machines.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:machines,code'],
            'name' => ['required', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Machine::create($data);

        return redirect()->route('machines.index')->with('success', 'Machine berhasil ditambahkan.');
    }

    public function edit(Machine $machine)
    {
        return view('machines.edit', compact('machine'));
    }

    public function update(Request $request, Machine $machine)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:machines,code,' . $machine->id],
            'name' => ['required', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $machine->update($data);

        return redirect()->route('machines.index')->with('success', 'Machine berhasil diupdate.');
    }

    public function destroy(Machine $machine)
    {
        $machine->delete();

        return redirect()->route('machines.index')->with('success', 'Machine berhasil dihapus.');
    }

    public function export()
    {
        return Excel::download(new MachinesExport, 'machines_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Excel::import(new MachinesImport, $request->file('file'));
            });
            return back()->with('success', 'Machines imported successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
