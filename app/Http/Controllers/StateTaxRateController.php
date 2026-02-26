<?php

namespace App\Http\Controllers;

use App\Models\StateTaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StateTaxRateController extends Controller
{
    /**
     * GET /settings/tax-rates
     */
    public function index()
    {
        $rates = StateTaxRate::orderBy('state_name')->get()->keyBy('state_code');
        $stateList = StateTaxRate::stateList();

        return view('settings.tax-rates', compact('rates', 'stateList'));
    }

    /**
     * PUT /settings/tax-rates
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $stateList = StateTaxRate::stateList();

        foreach ($validated['rates'] as $code => $rate) {
            if (! array_key_exists($code, $stateList)) {
                continue;
            }

            if ($rate === null || $rate === '') {
                StateTaxRate::where('state_code', $code)->delete();
                continue;
            }

            StateTaxRate::updateOrCreate(
                ['state_code' => $code],
                [
                    'state_name' => $stateList[$code],
                    'tax_rate' => $rate,
                ],
            );
        }

        return redirect()->route('settings.tax-rates')->with('success', 'State tax rates saved.');
    }
}
