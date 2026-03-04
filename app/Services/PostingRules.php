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
    public const CASH                = '1000';
    public const CHECKING            = '1010';
    public const SAVINGS             = '1120';
    public const SQUARE_CLEARING     = '1050';
    public const ACCOUNTS_RECEIVABLE = '1100';
    public const PARTS_INVENTORY     = '1200';

    // ── Liability accounts ──────────────────────────────
    public const ACCOUNTS_PAYABLE       = '2000';
    public const CREDIT_CARD_PAYABLE    = '2010';
    public const SALES_TAX_PAYABLE      = '2020';
    public const CORE_DEPOSITS_PAYABLE  = '2050';
    public const CUSTOMER_REFUNDS       = '2060';

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
    public const REVENUE_FUEL_DELIVERY  = '4200';
    public const REVENUE_TOWING         = '4250';

    // ── COGS accounts ───────────────────────────────────
    public const COGS_PARTS             = '5000';
    public const COGS_BATTERY           = '5010';
    public const COGS_STARTER           = '5020';
    public const COGS_FUEL              = '5100';
    public const COGS_PARTS_MATERIALS   = '5300';
    public const COGS_SHOP_SUPPLIES     = '5200';

    // ── Expense accounts ────────────────────────────────
    public const EXPENSE_FUEL           = '6000';
    public const EXPENSE_VEHICLE_REPAIR = '6020';
    public const EXPENSE_INSURANCE      = '6300';
    public const EXPENSE_SUPPLIES       = '6400';
    public const EXPENSE_LICENSING      = '6500';
    public const EXPENSE_TOOLS          = '6600';
    public const EXPENSE_MARKETING      = '6100';
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
