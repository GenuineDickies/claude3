<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\ServiceSignature;
use App\Models\Setting;
use App\Jobs\SendSmsJob;
use App\Models\MessageTemplate;
use App\Services\SmsServiceInterface;
use App\Services\StatusAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SignatureController extends Controller
{
    /**
     * Request a signature — generates a token and optionally sends an SMS link.
     */
    public function request(Request $request, ServiceRequest $serviceRequest)
    {
        $hours = (int) Setting::getValue('location_link_expiry_hours', 4);

        $signature = ServiceSignature::create([
            'service_request_id' => $serviceRequest->id,
            'signature_data'     => '',
            'signer_name'        => '',
            'signed_at'          => now(),
            'token'              => Str::random(48),
            'token_expires_at'   => now()->addHours($hours ?: 4),
        ]);

        ServiceLog::log($serviceRequest, 'signature_requested', [
            'signature_id' => $signature->id,
        ], Auth::id());

        // Send SMS if customer has consented and wants signature notifications
        if ($request->boolean('send_sms')
            && $serviceRequest->customer?->hasSmsConsent()
            && $serviceRequest->customer->wantsNotification('signature_requests')) {
            $signUrl = route('signature.show', $signature->token);
            $companyName = Setting::getValue('company_name', config('app.name'));

            $sms = app(SmsServiceInterface::class);
            $sms->sendRaw(
                $serviceRequest->customer->phone,
                $companyName . ': Please sign to confirm service completion: ' . $signUrl . ' Reply STOP to opt out.',
            );
        }

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Signature request created. ' .
                ($request->boolean('send_sms') ? 'SMS sent to customer.' : 'Share the signing link with the customer.'));
    }

    /**
     * Public: show the signing pad (token-based, no auth).
     */
    public function show(string $token)
    {
        $signature = ServiceSignature::where('token', $token)->firstOrFail();

        if (! empty($signature->signature_data)) {
            return response()->view('signatures.already-signed', [
                'signature' => $signature,
            ], 410);
        }

        if ($signature->token_expires_at && $signature->token_expires_at->isPast()) {
            return response()->view('signatures.expired', [], 410);
        }

        $serviceRequest = $signature->serviceRequest()->with(['customer', 'serviceType'])->first();
        $companyName = Setting::getValue('company_name', config('app.name'));

        return view('signatures.sign', compact('signature', 'serviceRequest', 'companyName'));
    }

    /**
     * Public: save the signature (token-based, no auth).
     */
    public function store(Request $request, string $token)
    {
        $signature = ServiceSignature::where('token', $token)->firstOrFail();

        if (! empty($signature->signature_data)) {
            abort(410, 'Already signed.');
        }

        if ($signature->token_expires_at && $signature->token_expires_at->isPast()) {
            abort(410, 'Signing link has expired.');
        }

        $request->validate([
            'signature_data' => 'required|string',
            'signer_name'    => 'required|string|max:200',
        ]);

        // Validate that signature_data is a valid base64 data URL
        if (! preg_match('/^data:image\/(png|jpeg|svg\+xml);base64,[A-Za-z0-9+\/=]+$/', $request->input('signature_data'))) {
            return back()->withErrors(['signature_data' => 'Invalid signature data.']);
        }

        $signature->update([
            'signature_data' => $request->input('signature_data'),
            'signer_name'    => $request->input('signer_name'),
            'ip_address'     => $request->ip(),
            'user_agent'     => substr($request->userAgent() ?? '', 0, 500),
            'signed_at'      => now(),
        ]);

        ServiceLog::log(
            $signature->serviceRequest,
            'signature_captured',
            [
                'signature_id' => $signature->id,
                'signer_name'  => $signature->signer_name,
                'ip_address'   => $signature->ip_address,
            ],
        );

        app(StatusAutomationService::class)->handle($signature->serviceRequest, 'signature_captured');

        return view('signatures.thank-you', [
            'companyName' => Setting::getValue('company_name', config('app.name')),
        ]);
    }
}
