<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceLogController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'event'   => 'required|string|max:50',
            'details' => 'nullable|string|max:2000',
        ]);

        ServiceLog::log(
            $serviceRequest,
            $request->input('event', 'note_added'),
            ['note' => $request->input('details')],
            Auth::id(),
        );

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Log entry added.');
    }
}
