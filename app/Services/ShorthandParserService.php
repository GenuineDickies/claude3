<?php

namespace App\Services;

use App\Models\CatalogItem;

class ShorthandParserService
{
    /**
     * Keyword → catalog item name mapping.
     * Keys are lowercase patterns, values are the CatalogItem name to match.
     */
    private const KEYWORD_MAP = [
        // Tire services
        'flat tire'       => 'Flat Tire Change',
        'flat'            => 'Flat Tire Change',
        'tire change'     => 'Flat Tire Change',
        'tire'            => 'Flat Tire Change',
        'mount and balance' => 'Mobile mount and Balance',
        'mount'           => 'Mobile mount and Balance',
        'balance'         => 'Mobile mount and Balance',

        // Jump start / battery
        'jump start'      => 'Jump Start',
        'jump'            => 'Jump Start',
        'jumpstart'       => 'Jump Start',
        'dead battery'    => 'Jump Start',
        'battery install' => 'Battery installation',
        'battery'         => 'Jump Start',

        // Lockout
        'lockout'         => 'Lockout Service',
        'lock out'        => 'Lockout Service',
        'locked out'      => 'Lockout Service',
        'keys locked'     => 'Lockout Service',

        // Tow
        'tow'             => 'Tow',
        'towing'          => 'Tow',

        // Winch
        'winch'           => 'Winch Out',
        'winch out'       => 'Winch Out',
        'stuck'           => 'Winch Out',
        'pull out'        => 'Winch Out',

        // Fuel
        'fuel'            => 'Fuel Delivery',
        'fuel delivery'   => 'Fuel Delivery',
        'gas'             => 'Fuel Delivery',
        'out of gas'      => 'Fuel Delivery',
        'no gas'          => 'Fuel Delivery',
        'empty tank'      => 'Fuel Delivery',
    ];

    /**
     * Parse shorthand text and return the best-matching catalog item.
     *
     * @return array{matched: bool, catalog_item: ?CatalogItem, keyword: ?string}
     */
    public function parse(string $input): array
    {
        $normalized = strtolower(trim($input));

        if ($normalized === '') {
            return ['matched' => false, 'catalog_item' => null, 'keyword' => null];
        }

        // Try longest keywords first for more specific matches
        $keywords = self::KEYWORD_MAP;
        uksort($keywords, fn ($a, $b) => strlen($b) - strlen($a));

        foreach ($keywords as $keyword => $itemName) {
            if (str_contains($normalized, $keyword)) {
                $item = CatalogItem::where('name', $itemName)
                    ->where('is_active', true)
                    ->first();

                if ($item) {
                    return [
                        'matched'      => true,
                        'catalog_item' => $item,
                        'keyword'      => $keyword,
                    ];
                }
            }
        }

        return ['matched' => false, 'catalog_item' => null, 'keyword' => null];
    }

    /**
     * Return all known shorthand keywords with their mapped service names.
     */
    public function keywords(): array
    {
        return self::KEYWORD_MAP;
    }
}
