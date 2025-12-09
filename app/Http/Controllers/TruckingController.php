<?php

namespace App\Http\Controllers;

use App\Models\Trucking;
use Illuminate\Http\Request;

class TruckingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $status = $request->query('status');

        $truckings = Trucking::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('truckings.index', compact('truckings', 'search', 'status'));
    }

    public function create()
    {
        return view('truckings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
        ]);

        Trucking::create($data + ['status' => 'active']);

        return redirect()->route('truckings.index')->with('status', 'Trucking company created.');
    }

    public function edit(Trucking $trucking)
    {
        return view('truckings.edit', compact('trucking'));
    }

    public function update(Request $request, Trucking $trucking)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $trucking->update($data);

        return redirect()->route('truckings.index')->with('status', 'Trucking company updated.');
    }

    public function destroy(Trucking $trucking)
    {
        $trucking->delete();

        return redirect()->route('truckings.index')->with('status', 'Trucking company deleted.');
    }
}
