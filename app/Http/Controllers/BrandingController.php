<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingController extends Controller
{
    /**
     * Serve the configured company logo without depending on a public storage symlink.
     */
    public function logo(): BinaryFileResponse
    {
        $path = Setting::getValue('company_logo');

        abort_unless(is_string($path) && $path !== '' && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}