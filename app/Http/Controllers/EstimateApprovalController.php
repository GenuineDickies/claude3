<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\ServiceLog;
use Illuminate\Http\Request;

class EstimateApprovalController extends Controller
{
    /**
     * GET /estimates/approve/{token}
     * Public page: display estimate details + signature pad.
     */
    public function show(string $token)
    {
        $estimate = Estimate::where('approval_token', $token)
            ->with(['items', 'serviceRequest.customer'])
            ->firstOrFail();

        if (! $estimate->isApprovalOpen()) {
            return view('estimates.approval-closed', [
                'estimate' => $estimate,
            ]);
        }

        $companyName = \App\Models\Setting::getValue('company_name', config('app.name'));

        return view('estimates.approve', [
            'estimate'    => $estimate,
            'companyName' => $companyName,
        ]);
    }

    /**
     * POST /estimates/approve/{token}
     * Process the customer's approval/rejection decision.
     */
    public function store(Request $request, string $token)
    {
        $estimate = Estimate::where('approval_token', $token)
            ->with('serviceRequest')
            ->firstOrFail();

        if (! $estimate->isApprovalOpen()) {
            return redirect()->route('estimate-approval.show', $token)
                ->with('error', 'This approval link has expired or already been used.');
        }

        $validated = $request->validate([
            'decision'       => 'required|string|in:accepted,declined',
            'signer_name'    => 'required|string|max:200',
            'signature_data' => 'nullable|string|max:100000',
        ]);

        $isAccepted = $validated['decision'] === 'accepted';

        $estimate->update([
            'status'              => $isAccepted ? 'accepted' : 'declined',
            'signer_name'         => $validated['signer_name'],
            'signature_data'      => $validated['signature_data'] ?? null,
            'approved_at'         => now(),
            'approved_total'      => $isAccepted ? $estimate->total : null,
            'approval_ip_address' => $request->ip(),
            'approval_user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        if ($isAccepted) {
            $estimate->lock();
        }

        ServiceLog::log(
            $estimate->serviceRequest,
            $isAccepted ? 'estimate_approved' : 'estimate_declined',
            [
                'estimate_id'     => $estimate->id,
                'estimate_number' => $estimate->displayNumber(),
                'signer_name'     => $validated['signer_name'],
                'total'           => $estimate->total,
            ],
        );

        $statusLabel = $isAccepted ? 'approved' : 'declined';

        return view('estimates.approval-complete', [
            'estimate' => $estimate,
            'decision' => $statusLabel,
        ]);
    }
}
