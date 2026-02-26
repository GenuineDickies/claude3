<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\Request;

class ServiceTypeController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::orderBy('sort_order')->get();

        return view('service-types.index', [
            'serviceTypes' => $serviceTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name',
            'default_price' => 'required|numeric|min:0|max:99999.99',
        ]);

        $maxSort = ServiceType::max('sort_order') ?? 0;

        ServiceType::create([
            'name' => $validated['name'],
            'default_price' => $validated['default_price'],
            'sort_order' => $maxSort + 1,
            'is_active' => true,
        ]);

        return redirect()->route('service-types.index')
            ->with('success', 'Service type created.');
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name,' . $serviceType->id,
            'default_price' => 'required|numeric|min:0|max:99999.99',
            'is_active' => 'boolean',
        ]);

        $serviceType->update($validated);

        return redirect()->route('service-types.index')
            ->with('success', 'Service type updated.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:service_types,id',
        ]);

        foreach ($validated['ids'] as $index => $id) {
            ServiceType::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    public function toggle(ServiceType $serviceType)
    {
        $serviceType->update(['is_active' => !$serviceType->is_active]);

        return redirect()->route('service-types.index')
            ->with('success', $serviceType->name . ($serviceType->is_active ? ' activated.' : ' deactivated.'));
    }
}
