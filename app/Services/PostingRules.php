<?php

namespace App\Services;

/**
 * Centralized account-code map for GL posting.
 *
 * All account-code references live here so AccountingService and any
 * future posting logic never hardcode raw strings. If the chart of
 * accounts is renumbered, update this one file.
 */
final class PostingRules
{
    // ── Asset accounts ──────────────────────────────────
    public const CASH                = '1100';
    public const CHECKING            = '1110';
    public const SAVINGS             = '1120';
    public const SQUARE_CLEARING     = '1150';
    public const ACCOUNTS_RECEIVABLE = '1200';
    public const PARTS_INVENTORY     = '1400';

    // ── Liability accounts ──────────────────────────────
    public const ACCOUNTS_PAYABLE       = '2100';
    public const SALES_TAX_PAYABLE      = '2200';
    public const CORE_DEPOSITS_PAYABLE  = '2350';
    public const CUSTOMER_REFUNDS       = '2360';
    public const CREDIT_CARD_PAYABLE    = '2400';

    // ── Revenue accounts ────────────────────────────────
    public const REVENUE_DEFAULT        = '4000'; // Roadside Service Revenue
    public const REVENUE_JUMP_START     = '4010';
    public const REVENUE_TIRE_CHANGE    = '4020';
    public const REVENUE_LOCKOUT        = '4030';
    public const REVENUE_BATTERY_LABOR  = '4040';
    public const REVENUE_MECHANIC_LABOR = '4050';
    public const REVENUE_PARTS_SALES    = '4100';
    public const REVENUE_BATTERY_SALES  = '4110';
    public const REVENUE_STARTER_SALES  = '4120';
    public const REVENUE_FUEL_DELIVERY  = '4150';
    public const REVENUE_TOWING         = '4200';

    // ── COGS accounts ───────────────────────────────────
    public const COGS_PARTS             = '5000';
    public const COGS_BATTERY           = '5010';
    public const COGS_STARTER           = '5020';
    public const COGS_FUEL              = '5050';
    public const COGS_PARTS_MATERIALS   = '5100';
    public const COGS_SHOP_SUPPLIES     = '5150';

    // ── Expense accounts ────────────────────────────────
    public const EXPENSE_FUEL           = '6150';
    public const EXPENSE_VEHICLE_REPAIR = '6200';
    public const EXPENSE_INSURANCE      = '6300';
    public const EXPENSE_SUPPLIES       = '6400';
    public const EXPENSE_LICENSING      = '6500';
    public const EXPENSE_TOOLS          = '6600';
    public const EXPENSE_MARKETING      = '6700';
    public const EXPENSE_OFFICE         = '6800';
    public const EXPENSE_OTHER          = '6900';

    // ── Processing fee accounts ─────────────────────────
    public const MERCHANT_FEES          = '7000';
    public const SQUARE_FEES            = '7010';

    // ── Category → expense-account mapping ──────────────
    public const EXPENSE_CATEGORY_MAP = [
        'fuel'           => self::EXPENSE_FUEL,
        'vehicle_repair' => self::EXPENSE_VEHICLE_REPAIR,
        'supplies'       => self::EXPENSE_SUPPLIES,
        'parts'          => self::COGS_PARTS_MATERIALS,
        'insurance'      => self::EXPENSE_INSURANCE,
        'licensing'      => self::EXPENSE_LICENSING,
        'tools'          => self::EXPENSE_TOOLS,
        'marketing'      => self::EXPENSE_MARKETING,
        'office'         => self::EXPENSE_OFFICE,
        'other'          => self::EXPENSE_OTHER,
    ];

    // ── Payment-method → cash-account mapping ───────────

    /**
     * Which asset account to debit when a customer pays.
     */
    public static function cashAccountForPayment(string $method): string
    {
        return match ($method) {
            'card'  => self::SQUARE_CLEARING,
            default => self::CASH,
        };
    }

    /**
     * Which asset account to credit when paying a vendor.
     */
    public static function cashAccountForDisbursement(string $method): string
    {
        return match ($method) {
            'check', 'ach' => self::CHECKING,
            default        => self::CASH,
        };
    }
}
