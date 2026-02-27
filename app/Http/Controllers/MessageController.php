<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Services\SmsServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest, SmsServiceInterface $sms): RedirectResponse
    {
        $customer = $serviceRequest->customer;

        if (! $customer) {
            return back()->with('error', 'No customer associated with this service request.');
        }

        $validated = $request->validate([
            'body'        => 'required|string|max:1600',
            'template_id' => 'nullable|integer|exists:message_templates,id',
        ]);

        // Consent gate — only compliance templates bypass this
        if (! $customer->hasSmsConsent()) {
            return back()->with('error', 'Customer has not opted in to SMS. Send an opt-in message first.');
        }

        if ($validated['template_id'] ?? null) {
            $template = MessageTemplate::findOrFail($validated['template_id']);
            $result = $sms->sendTemplate(
                template: $template,
                to: $customer->phone,
                customer: $customer,
                serviceRequest: $serviceRequest,
            );

            if (! $result['success']) {
                return back()->with('error', 'SMS failed: ' . ($result['error'] ?? 'unknown'));
            }

            return back()->with('success', 'Template message sent.');
        }

        // Free-text message
        $result = $sms->sendRawWithLog(
            to: $customer->phone,
            text: $validated['body'],
            customer: $customer,
            serviceRequest: $serviceRequest,
            subject: 'SMS (free-text)',
            loggedBy: Auth::id(),
        );

        if (! $result['success']) {
            return back()->with('error', 'SMS failed: ' . ($result['error'] ?? 'unknown'));
        }

        return back()->with('success', 'Message sent.');
    }
}
