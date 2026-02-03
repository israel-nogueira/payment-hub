<?php

namespace IsraelNogueira\PaymentHub\Enums;

enum SubscriptionInterval: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMIANNUALLY = 'semiannually';
    case YEARLY = 'yearly';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::DAILY => 'Diário',
            self::WEEKLY => 'Semanal',
            self::BIWEEKLY => 'Quinzenal',
            self::MONTHLY => 'Mensal',
            self::QUARTERLY => 'Trimestral',
            self::SEMIANNUALLY => 'Semestral',
            self::YEARLY => 'Anual',
        };
    }

    /**
     * Get number of days in the interval
     */
    public function days(): int
    {
        return match($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::BIWEEKLY => 14,
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::SEMIANNUALLY => 180,
            self::YEARLY => 365,
        };
    }

    /**
     * Get number of months (approximate)
     */
    public function months(): float
    {
        return match($this) {
            self::DAILY => 0.033,
            self::WEEKLY => 0.25,
            self::BIWEEKLY => 0.5,
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::SEMIANNUALLY => 6,
            self::YEARLY => 12,
        };
    }

    /**
     * Calculate next billing date from a given date
     */
    public function nextBillingDate(\DateTimeInterface $from = null): \DateTime
    {
        $date = $from ? clone $from : new \DateTime();
        
        return match($this) {
            self::DAILY => $date->modify('+1 day'),
            self::WEEKLY => $date->modify('+1 week'),
            self::BIWEEKLY => $date->modify('+2 weeks'),
            self::MONTHLY => $date->modify('+1 month'),
            self::QUARTERLY => $date->modify('+3 months'),
            self::SEMIANNUALLY => $date->modify('+6 months'),
            self::YEARLY => $date->modify('+1 year'),
        };
    }

    /**
     * Get typical discount percentage for this interval
     */
    public function typicalDiscount(): int
    {
        return match($this) {
            self::DAILY, self::WEEKLY => 0,
            self::BIWEEKLY => 5,
            self::MONTHLY => 0,
            self::QUARTERLY => 10,
            self::SEMIANNUALLY => 15,
            self::YEARLY => 20,
        };
    }

    /**
     * Create from string (case-insensitive)
     */
    public static function fromString(string $interval): self
    {
        $normalized = strtolower($interval);
        
        return self::from($normalized);
    }
}