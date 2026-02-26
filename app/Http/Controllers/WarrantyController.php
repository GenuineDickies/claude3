<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Warranty;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarrantyController extends Controller
{
    /** Standalone warranties index with expiry filter. */
    public function index(Request $request)
    {
        $query = Warranty::with(['serviceRequest.customer', 'serviceRequest.serviceType']);

        if ($filter = $request->input('filter')) {
            $today = today();
            match ($filter) {
                'active'        => $query->where('warranty_expires_at', '>', $today->copy()->addDays(30)),
                'expiring_soon' => $query->where('warranty_expires_at', '>', $today)
                                         ->where('warranty_expires_at', '<=', $today->copy()->addDays(30)),
                'expired'       => $query->where('warranty_expires_at', '<=', $today),
                default         => null,
            };
        }

        $warranties = $query->orderBy('warranty_expires_at')->paginate(25)->withQueryString();

        return view('warranties.index', compact('warranties', 'filter'));
    }

    /** Create form (nested under SR). */
    public function create(ServiceRequest $serviceRequest)
    {
        return view('warranties.create', compact('serviceRequest'));
    }

    /** Store a new warranty. */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'part_name'             => 'required|string|max:255',
            'part_number'           => 'nullable|string|max:100',
            'vendor_name'           => 'nullable|string|max:255',
            'vendor_phone'          => 'nullable|string|max:30',
            'vendor_invoice_number' => 'nullable|string|max:100',
            'install_date'          => 'required|date',
            'warranty_months'       => 'required|integer|min:1|max:600',
            'notes'                 => 'nullable|string|max:5000',
        ]);

        $installDate = Carbon::parse($validated['install_date']);
        $validated['warranty_expires_at'] = $installDate->copy()->addMonths((int) $validated['warranty_months']);
        $validated['service_request_id'] = $serviceRequest->id;
        $validated['created_by'] = Auth::id();

        $warranty = Warranty::create($validated);

        ServiceLog::log($serviceRequest, 'note_added', [
            'action' => 'warranty_added',
            'warranty_id' => $warranty->id,
            'part_name' => $warranty->part_name,
        ], Auth::id());

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Warranty for "' . e($warranty->part_name) . '" added.');
    }

    /** Show warranty detail (standalone). */
    public function show(ServiceRequest $serviceRequest, Warranty $warranty)
    {
        abort_unless($warranty->service_request_id === $serviceRequest->id, 404);

        $warranty->load('documents.uploader', 'serviceRequest.customer');

        return view('warranties.show', compact('serviceRequest', 'warranty'));
    }

    /** Edit form. */
    public function edit(ServiceRequest $serviceRequest, Warranty $warranty)
    {
        abort_unless($warranty->service_request_id === $serviceRequest->id, 404);

        return view('warranties.edit', compact('serviceRequest', 'warranty'));
    }

    /** Update warranty. */
    public function update(Request $request, ServiceRequest $serviceRequest, Warranty $warranty)
    {
        abort_unless($warranty->service_request_id === $serviceRequest->id, 404);

        $validated = $request->validate([
            'part_name'             => 'required|string|max:255',
            'part_number'           => 'nullable|string|max:100',
            'vendor_name'           => 'nullable|string|max:255',
            'vendor_phone'          => 'nullable|string|max:30',
            'vendor_invoice_number' => 'nullable|string|max:100',
            'install_date'          => 'required|date',
            'warranty_months'       => 'required|integer|min:1|max:600',
            'notes'                 => 'nullable|string|max:5000',
        ]);

        $installDate = Carbon::parse($validated['install_date']);
        $validated['warranty_expires_at'] = $installDate->copy()->addMonths((int) $validated['warranty_months']);

        $warranty->update($validated);

        return redirect()->route('warranties.show', [$serviceRequest, $warranty])
            ->with('success', 'Warranty updated.');
    }

    /** Delete warranty. */
    public function destroy(ServiceRequest $serviceRequest, Warranty $warranty)
    {
        abort_unless($warranty->service_request_id === $serviceRequest->id, 404);

        ServiceLog::log($serviceRequest, 'note_added', [
            'action' => 'warranty_deleted',
            'warranty_id' => $warranty->id,
            'part_name' => $warranty->part_name,
        ], Auth::id());

        $warranty->delete();

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Warranty deleted.');
    }
}
