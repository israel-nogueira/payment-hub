<?php

namespace IsraelNogueira\PaymentHub\ValueObjects;

use IsraelNogueira\PaymentHub\Exceptions\InvalidCardNumberException;

final class CardNumber
{
    private string $value;

    private function __construct(string $value)
    {
        $cleaned = $this->clean($value);
        
        if (!$this->isValid($cleaned)) {
            throw new InvalidCardNumberException("Invalid card number: {$value}");
        }
        
        $this->value = $cleaned;
    }

    /**
     * Create from string
     */
    public static function fromString(string $number): self
    {
        return new self($number);
    }

    /**
     * Get the full card number (use with caution!)
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get masked card number (safe for display)
     */
    public function masked(): string
    {
        $length = strlen($this->value);
        $visible = 4;
        $masked = str_repeat('*', $length - $visible);
        
        return $masked . substr($this->value, -$visible);
    }

    /**
     * Get formatted card number with spaces
     */
    public function formatted(): string
    {
        return implode(' ', str_split($this->value, 4));
    }

    /**
     * Get formatted masked card number
     */
    public function formattedMasked(): string
    {
        return implode(' ', str_split($this->masked(), 4));
    }

    /**
     * Get last 4 digits
     */
    public function lastFour(): string
    {
        return substr($this->value, -4);
    }

    /**
     * Get first 6 digits (BIN)
     */
    public function bin(): string
    {
        return substr($this->value, 0, 6);
    }

    /**
     * Get card brand
     */
    public function brand(): string
    {
        $firstDigit = substr($this->value, 0, 1);
        $firstTwoDigits = substr($this->value, 0, 2);
        $firstFourDigits = substr($this->value, 0, 4);

        // Visa
        if ($firstDigit === '4') {
            return 'visa';
        }

        // Mastercard
        if (in_array((int)$firstTwoDigits, range(51, 55)) || in_array((int)$firstFourDigits, range(2221, 2720))) {
            return 'mastercard';
        }

        // Amex
        if (in_array($firstTwoDigits, ['34', '37'])) {
            return 'amex';
        }

        // Elo
        if (in_array($firstFourDigits, ['4011', '4312', '4389', '4514', '4576', '5041', '5066', '5090', '6277', '6362', '6363'])) {
            return 'elo';
        }

        // Hipercard
        if (in_array($firstFourDigits, ['3841', '6062'])) {
            return 'hipercard';
        }

        // Discover
        if ($firstFourDigits === '6011' || in_array($firstTwoDigits, ['64', '65'])) {
            return 'discover';
        }

        // Diners
        if (in_array($firstTwoDigits, ['36', '38']) || in_array((int)$firstTwoDigits, range(30, 35))) {
            return 'diners';
        }

        return 'unknown';
    }

    /**
     * Get brand icon/emoji
     */
    public function brandIcon(): string
    {
        return match($this->brand()) {
            'visa' => '💳 Visa',
            'mastercard' => '💳 Mastercard',
            'amex' => '💳 Amex',
            'elo' => '💳 Elo',
            'hipercard' => '💳 Hipercard',
            'discover' => '💳 Discover',
            'diners' => '💳 Diners',
            default => '💳 Unknown',
        };
    }

    /**
     * Clean card number (remove spaces, dashes, etc)
     */
    private function clean(string $number): string
    {
        $cleaned = preg_replace('/\D/', '', $number);
        
        if (empty($cleaned)) {
            throw new InvalidCardNumberException("Card number cannot be empty");
        }
        
        return $cleaned;
    }

    /**
     * Validate using Luhn algorithm
     */
    private function isValid(string $number): bool
    {
        // Check length
        $length = strlen($number);
        if ($length < 13 || $length > 19) {
            return false;
        }

        // Check if all digits
        if (!ctype_digit($number)) {
            return false;
        }

        // Luhn algorithm
        $sum = 0;
        $isEven = false;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$number[$i];

            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $isEven = !$isEven;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Convert to string (returns masked version for security)
     */
    public function __toString(): string
    {
        return $this->masked();
    }

    /**
     * For JSON serialization (returns masked)
     */
    public function jsonSerialize(): string
    {
        return $this->masked();
    }
}