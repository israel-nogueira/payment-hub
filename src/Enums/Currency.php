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
     * Renomeado de name() para label() — evita conflito com propriedade nativa de enum PHP 8.1+
     */
    public function label(): string
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

    public function decimals(): int
    {
        return match($this) {
            self::CLP => 0,
            default   => 2,
        };
    }

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

    public function format(float $amount): string
    {
        $formatted = number_format($amount, $this->decimals(), ',', '.');

        return match($this) {
            self::BRL                                    => "R$ {$formatted}",
            self::USD, self::ARS, self::CLP,
            self::COP, self::MXN                         => "\${$formatted}",
            self::EUR                                    => "{$formatted} €",
            self::GBP                                    => "£{$formatted}",
            self::PEN                                    => "S/ {$formatted}",
            self::UYU                                    => "\$U {$formatted}",
        };
    }

    public static function fromString(string $currency): self
    {
        return self::from(strtoupper($currency));
    }
}