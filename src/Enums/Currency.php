<?php

namespace IsraelNogueira\PaymentHub\Enums;

enum Currency: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case ARS = 'ARS';
    case CLP = 'CLP';
    case COP = 'COP';
    case MXN = 'MXN';
    case PEN = 'PEN';
    case UYU = 'UYU';

    /**
     * Get currency symbol
     */
    public function symbol(): string
    {
        return match($this) {
            self::BRL => 'R$',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::ARS => '$',
            self::CLP => '$',
            self::COP => '$',
            self::MXN => '$',
            self::PEN => 'S/',
            self::UYU => '$U',
        };
    }

    /**
     * Get currency name
     */
    public function name(): string
    {
        return match($this) {
            self::BRL => 'Real Brasileiro',
            self::USD => 'Dólar Americano',
            self::EUR => 'Euro',
            self::GBP => 'Libra Esterlina',
            self::ARS => 'Peso Argentino',
            self::CLP => 'Peso Chileno',
            self::COP => 'Peso Colombiano',
            self::MXN => 'Peso Mexicano',
            self::PEN => 'Sol Peruano',
            self::UYU => 'Peso Uruguaio',
        };
    }

    /**
     * Get decimal places for the currency
     */
    public function decimals(): int
    {
        return match($this) {
            self::CLP => 0, // Chilean Peso has no decimals
            default => 2,
        };
    }

    /**
     * Check if currency is Latin American
     */
    public function isLatinAmerican(): bool
    {
        return in_array($this, [
            self::BRL,
            self::ARS,
            self::CLP,
            self::COP,
            self::MXN,
            self::PEN,
            self::UYU,
        ]);
    }

    /**
     * Format amount with currency symbol
     */
    public function format(float $amount): string
    {
        $formatted = number_format($amount, $this->decimals(), ',', '.');
        
        return match($this) {
            self::BRL => "R$ {$formatted}",
            self::USD, self::ARS, self::CLP, self::COP, self::MXN => "\${$formatted}",
            self::EUR => "{$formatted} €",
            self::GBP => "£{$formatted}",
            self::PEN => "S/ {$formatted}",
            self::UYU => "\$U {$formatted}",
        };
    }

    /**
     * Create from string (case-insensitive)
     */
    public static function fromString(string $currency): self
    {
        $normalized = strtoupper($currency);
        
        return self::from($normalized);
    }
}