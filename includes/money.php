<?php

declare(strict_types=1);

/**
 * Format a decimal amount with the configured storefront currency (default: Nigerian Naira).
 */
function bb_format_money(float $amount): string
{
    return BOOKBITS_CURRENCY_SYMBOL . number_format($amount, 2);
}
