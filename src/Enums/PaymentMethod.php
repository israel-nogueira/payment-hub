<?php

namespace IsraelNogueira\PaymentHub\Enums;

enum PaymentMethod: string
{
    case PIX = 'pix';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case BOLETO = 'boleto';
    case BANK_TRANSFER = 'bank_transfer';
    case WALLET = 'wallet';
    case CASH = 'cash';
    case PAYPAL = 'paypal';
    case APPLE_PAY = 'apple_pay';
    case GOOGLE_PAY = 'google_pay';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::PIX => 'PIX',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::DEBIT_CARD => 'Cartão de Débito',
            self::BOLETO => 'Boleto Bancário',
            self::BANK_TRANSFER => 'Transferência Bancária',
            self::WALLET => 'Carteira Digital',
            self::CASH => 'Dinheiro',
            self::PAYPAL => 'PayPal',
            self::APPLE_PAY => 'Apple Pay',
            self::GOOGLE_PAY => 'Google Pay',
        };
    }

    /**
     * Check if method requires card data
     */
    public function requiresCard(): bool
    {
        return in_array($this, [
            self::CREDIT_CARD,
            self::DEBIT_CARD,
        ]);
    }

    /**
     * Check if method is instant
     */
    public function isInstant(): bool
    {
        return in_array($this, [
            self::PIX,
            self::CREDIT_CARD,
            self::DEBIT_CARD,
            self::WALLET,
            self::APPLE_PAY,
            self::GOOGLE_PAY,
        ]);
    }

    /**
     * Check if method is Brazilian-specific
     */
    public function isBrazilian(): bool
    {
        return in_array($this, [
            self::PIX,
            self::BOLETO,
        ]);
    }

    /**
     * Check if method supports installments
     */
    public function supportsInstallments(): bool
    {
        return $this === self::CREDIT_CARD;
    }

    /**
     * Get typical processing time in minutes
     */
    public function typicalProcessingTime(): int
    {
        return match($this) {
            self::PIX => 1,
            self::CREDIT_CARD, self::DEBIT_CARD => 5,
            self::WALLET, self::APPLE_PAY, self::GOOGLE_PAY => 1,
            self::BANK_TRANSFER => 1440, // 1 day
            self::BOLETO => 2880, // 2 days
            self::CASH => 0,
            self::PAYPAL => 10,
        };
    }

    /**
     * Get icon/emoji for UI
     */
    public function icon(): string
    {
        return match($this) {
            self::PIX => '🔷',
            self::CREDIT_CARD => '💳',
            self::DEBIT_CARD => '💳',
            self::BOLETO => '🧾',
            self::BANK_TRANSFER => '🏦',
            self::WALLET => '👛',
            self::CASH => '💵',
            self::PAYPAL => '🅿️',
            self::APPLE_PAY => '🍎',
            self::GOOGLE_PAY => '🔍',
        };
    }

    /**
     * Create from string (case-insensitive)
     */
    public static function fromString(string $method): self
    {
        $normalized = strtolower($method);
        
        return self::from($normalized);
    }

    /**
     * Get all methods available for a currency
     */
    public static function availableFor(Currency $currency): array
    {
        if ($currency === Currency::BRL) {
            return self::cases();
        }

        return array_filter(self::cases(), fn($method) => !$method->isBrazilian());
    }
}