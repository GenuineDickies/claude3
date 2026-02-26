<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\ServicePhoto;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'photo'   => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'caption' => 'nullable|string|max:500',
            'type'    => 'required|string|in:before,during,after',
        ]);

        $file = $request->file('photo');
        $path = $file->store(
            'photos/' . $serviceRequest->id,
            'local'
        );

        $photo = ServicePhoto::create([
            'service_request_id' => $serviceRequest->id,
            'file_path'          => $path,
            'caption'            => $request->input('caption'),
            'taken_at'           => now(),
            'type'               => $request->input('type'),
            'uploaded_by'        => Auth::id(),
        ]);

        ServiceLog::log($serviceRequest, 'photo_uploaded', [
            'photo_id' => $photo->id,
            'type'     => $photo->type,
            'caption'  => $photo->caption,
        ], Auth::id());

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Photo uploaded.');
    }

    public function show(ServiceRequest $serviceRequest, ServicePhoto $photo)
    {
        abort_unless($photo->service_request_id === $serviceRequest->id, 404);

        $path = Storage::disk('local')->path($photo->file_path);
        abort_unless(file_exists($path), 404);

        return response()->file($path);
    }

    public function destroy(ServiceRequest $serviceRequest, ServicePhoto $photo)
    {
        abort_unless($photo->service_request_id === $serviceRequest->id, 404);

        Storage::disk('local')->delete($photo->file_path);

        ServiceLog::log($serviceRequest, 'photo_deleted', [
            'photo_id' => $photo->id,
            'type'     => $photo->type,
            'caption'  => $photo->caption,
        ], Auth::id());

        $photo->delete();

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Photo deleted.');
    }
}
