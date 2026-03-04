<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Models\ServiceLog;
use App\Models\ServicePhoto;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Services\SmsServiceInterface;
use App\Services\StatusAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoUploadController extends Controller
{
    /**
     * POST /service-requests/{serviceRequest}/request-photo-upload
     *
     * Generate a photo upload token and send the SMS link to the customer.
     */
    public function request(ServiceRequest $serviceRequest, SmsServiceInterface $sms): RedirectResponse
    {
        $customer = $serviceRequest->customer;

        if (! $customer) {
            return back()->with('error', 'No customer associated with this service request.');
        }

        if (! $customer->wantsNotification('location_requests')) {
            return back()->with('warning', 'Customer has disabled notifications.');
        }

        // ── Consent gate ──────────────────────────────────────────
        if (! $customer->hasSmsConsent()) {
            $optInTemplate = MessageTemplate::where('slug', 'welcome-message')->first();

            if ($optInTemplate) {
                $sms->sendTemplate(
                    template: $optInTemplate,
                    to: $customer->phone,
                    customer: $customer,
                    serviceRequest: $serviceRequest,
                );
            }

            return back()->with('warning', 'Customer has not opted in to SMS. An opt-in message was sent to ' . $customer->phone . '. Once they reply START, you can request the photo upload.');
        }

        // ── Generate token & send photo upload link ───────────────
        $serviceRequest->generatePhotoUploadToken();

        $template = MessageTemplate::where('slug', 'photo-upload-request')->first();

        if (! $template) {
            $link = $serviceRequest->photoUploadUrl();
            $companyName = Setting::getValue('company_name', config('app.name'));
            $rawText = $companyName . ': Hi ' . $customer->first_name . ', please tap this link to upload a photo of your situation: ' . $link . ' Reply STOP to opt out.';
            $sms->sendRawWithLog(
                to: $customer->phone,
                text: $rawText,
                customer: $customer,
                serviceRequest: $serviceRequest,
                subject: 'Photo upload request',
                loggedBy: Auth::id(),
            );
        } else {
            $sms->sendTemplate(
                template: $template,
                to: $customer->phone,
                customer: $customer,
                serviceRequest: $serviceRequest,
                overrides: ['photo_upload_link' => $serviceRequest->photoUploadUrl()],
            );
        }

        return back()->with('success', 'Photo upload link sent to ' . $customer->phone . '.');
    }

    /**
     * GET /upload-photo/{token}
     *
     * Public page — no auth. Shows the mobile photo upload UI.
     */
    public function show(string $token)
    {
        $serviceRequest = ServiceRequest::where('photo_upload_token', $token)->firstOrFail();

        $companyName = Setting::getValue('company_name', config('app.name'));

        if (! $serviceRequest->isPhotoUploadTokenValid()) {
            return response()->view('photo-upload', [
                'expired' => true,
                'serviceRequest' => $serviceRequest,
                'companyName' => $companyName,
            ], 410);
        }

        return view('photo-upload', [
            'expired' => false,
            'serviceRequest' => $serviceRequest,
            'token' => $token,
            'companyName' => $companyName,
        ]);
    }

    /**
     * POST /api/upload-photo/{token}
     *
     * Receive the photo from the customer's mobile browser.
     */
    public function store(Request $request, string $token): JsonResponse
    {
        $serviceRequest = ServiceRequest::where('photo_upload_token', $token)->first();

        if (! $serviceRequest || ! $serviceRequest->isPhotoUploadTokenValid()) {
            return response()->json(['error' => 'Invalid or expired link.'], 422);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:15360'],
        ]);

        $file = $request->file('photo');
        $path = $file->store(
            'photos/' . $serviceRequest->id,
            'local'
        );

        $photo = ServicePhoto::create([
            'service_request_id' => $serviceRequest->id,
            'file_path'          => $path,
            'caption'            => 'Uploaded by customer',
            'taken_at'           => now(),
            'type'               => 'before',
            'uploaded_by'        => null,
        ]);

        ServiceLog::log($serviceRequest, 'photo_uploaded', [
            'photo_id'  => $photo->id,
            'type'      => $photo->type,
            'caption'   => $photo->caption,
            'source'    => 'customer_upload',
        ]);

        app(StatusAutomationService::class)->handle($serviceRequest, 'photo_uploaded');

        return response()->json([
            'ok'      => true,
            'message' => 'Photo uploaded successfully. Thank you!',
        ]);
    }
}
