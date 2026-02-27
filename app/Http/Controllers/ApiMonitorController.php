<?php

namespace App\Http\Controllers;

use App\Models\ApiMonitorEndpoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ApiMonitorController extends Controller
{
    public function index()
    {
        $endpoints = ApiMonitorEndpoint::query()
            ->with(['runs' => fn ($q) => $q->latest('checked_at')->limit(1)])
            ->orderBy('name')
            ->get();

        return view('settings.api-monitor', compact('endpoints'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url:https'],
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
            'expected_status_code' => ['nullable', 'integer', 'between:100,599'],
            'check_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ApiMonitorEndpoint::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'method' => $validated['method'],
            'expected_status_code' => $validated['expected_status_code'] ?? null,
            'check_interval_minutes' => $validated['check_interval_minutes'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'headers' => null,
        ]);

        return redirect()->route('settings.api-monitor.index')->with('success', 'Endpoint added.');
    }

    public function update(Request $request, ApiMonitorEndpoint $endpoint): RedirectResponse
    {
        $validated = $request->validate([
            'expected_status_code' => ['nullable', 'integer', 'between:100,599'],
            'check_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $endpoint->update([
            'expected_status_code' => $validated['expected_status_code'] ?? null,
            'check_interval_minutes' => $validated['check_interval_minutes'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('settings.api-monitor.index')->with('success', 'Endpoint updated.');
    }

    public function run(ApiMonitorEndpoint $endpoint): RedirectResponse
    {
        Artisan::call('api:monitor', [
            '--only-id' => (string) $endpoint->id,
        ]);

        return redirect()->route('settings.api-monitor.index')->with('success', 'Health check executed.');
    }
}
