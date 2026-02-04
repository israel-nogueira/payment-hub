<?php

namespace IsraelNogueira\PaymentHub\ValueObjects;

use IsraelNogueira\PaymentHub\Exceptions\InvalidDocumentException;

final class CPF
{
    private string $value;

    private function __construct(string $value)
    {
        $cleaned = $this->clean($value);
        
        if (!$this->isValid($cleaned)) {
            throw new InvalidDocumentException("Invalid CPF: {$value}");
        }
        
        $this->value = $cleaned;
    }

    /**
     * Create from string
     */
    public static function fromString(string $cpf): self
    {
        return new self($cpf);
    }

    /**
     * Get raw CPF (only numbers)
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get formatted CPF (000.000.000-00)
     */
    public function formatted(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->value, 0, 3),
            substr($this->value, 3, 3),
            substr($this->value, 6, 3),
            substr($this->value, 9, 2)
        );
    }

    /**
     * Get masked CPF (***.***.000-00)
     */
    public function masked(): string
    {
        return sprintf(
            '***.%s.%s-%s',
            substr($this->value, 3, 3),
            substr($this->value, 6, 3),
            substr($this->value, 9, 2)
        );
    }

    /**
     * Get first 3 digits masked
     */
    public function partialMasked(): string
    {
        return sprintf(
            '***.***.*%s-%s',
            substr($this->value, 7, 2),
            substr($this->value, 9, 2)
        );
    }

    /**
     * Clean CPF (remove dots, dashes, spaces)
     */
    private function clean(string $cpf): string
    {
        $cleaned = preg_replace('/\D/', '', $cpf);
        
        if (empty($cleaned)) {
            throw new InvalidDocumentException("CPF cannot be empty");
        }
        
        return $cleaned;
    }

    /**
     * Validate CPF
     */
    private function isValid(string $cpf): bool
    {
        // Check length
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Check if all digits are the same (invalid CPF)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);

        if ((int)$cpf[9] !== $digit1) {
            return false;
        }

        // Validate second check digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);

        return (int)$cpf[10] === $digit2;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->formatted();
    }

    /**
     * For JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->value();
    }

    /**
     * Check equality
     */
    public function equals(CPF $other): bool
    {
        return $this->value === $other->value;
    }
}