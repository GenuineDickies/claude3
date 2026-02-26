<?php

namespace Database\Seeders;

use App\Models\StateTaxRate;
use Illuminate\Database\Seeder;

class StateTaxRateSeeder extends Seeder
{
    /**
     * Seed default US state sales tax rates.
     *
     * Source: Sales Tax Institute (salestaxinstitute.com/resources/rates)
     * These are state-level rates only (no local surtaxes).
     * Rates as of March 2026 — verify periodically.
     */
    public function run(): void
    {
        $rates = [
            'AL' => ['Alabama', 4.000],
            'AK' => ['Alaska', 0.000],
            'AZ' => ['Arizona', 5.600],
            'AR' => ['Arkansas', 6.500],
            'CA' => ['California', 7.250],
            'CO' => ['Colorado', 2.900],
            'CT' => ['Connecticut', 6.350],
            'DE' => ['Delaware', 0.000],
            'DC' => ['District of Columbia', 6.000],
            'FL' => ['Florida', 6.000],
            'GA' => ['Georgia', 4.000],
            'HI' => ['Hawaii', 4.000],
            'ID' => ['Idaho', 6.000],
            'IL' => ['Illinois', 6.250],
            'IN' => ['Indiana', 7.000],
            'IA' => ['Iowa', 6.000],
            'KS' => ['Kansas', 6.500],
            'KY' => ['Kentucky', 6.000],
            'LA' => ['Louisiana', 5.000],
            'ME' => ['Maine', 5.500],
            'MD' => ['Maryland', 6.000],
            'MA' => ['Massachusetts', 6.250],
            'MI' => ['Michigan', 6.000],
            'MN' => ['Minnesota', 6.875],
            'MS' => ['Mississippi', 7.000],
            'MO' => ['Missouri', 4.225],
            'MT' => ['Montana', 0.000],
            'NE' => ['Nebraska', 5.500],
            'NV' => ['Nevada', 6.850],
            'NH' => ['New Hampshire', 0.000],
            'NJ' => ['New Jersey', 6.625],
            'NM' => ['New Mexico', 4.875],
            'NY' => ['New York', 4.000],
            'NC' => ['North Carolina', 4.750],
            'ND' => ['North Dakota', 5.000],
            'OH' => ['Ohio', 5.750],
            'OK' => ['Oklahoma', 4.500],
            'OR' => ['Oregon', 0.000],
            'PA' => ['Pennsylvania', 6.000],
            'RI' => ['Rhode Island', 7.000],
            'SC' => ['South Carolina', 6.000],
            'SD' => ['South Dakota', 4.200],
            'TN' => ['Tennessee', 7.000],
            'TX' => ['Texas', 6.250],
            'UT' => ['Utah', 4.850],
            'VT' => ['Vermont', 6.000],
            'VA' => ['Virginia', 4.300],
            'WA' => ['Washington', 6.500],
            'WV' => ['West Virginia', 6.000],
            'WI' => ['Wisconsin', 5.000],
            'WY' => ['Wyoming', 4.000],
        ];

        foreach ($rates as $code => [$name, $rate]) {
            StateTaxRate::updateOrCreate(
                ['state_code' => $code],
                [
                    'state_name' => $name,
                    'tax_rate' => $rate,
                ],
            );
        }
    }
}
