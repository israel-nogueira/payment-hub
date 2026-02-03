<?php

namespace IsraelNogueira\PaymentHub\ValueObjects;

use IsraelNogueira\PaymentHub\Exceptions\InvalidEmailException;

final class Email
{
    private string $value;

    private function __construct(string $value)
    {
        $cleaned = trim(strtolower($value));
        
        if (!$this->isValid($cleaned)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
        
        $this->value = $cleaned;
    }

    /**
     * Create from string
     */
    public static function fromString(string $email): self
    {
        return new self($email);
    }

    /**
     * Get email value
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get masked email (u***@domain.com)
     */
    public function masked(): string
    {
        [$local, $domain] = explode('@', $this->value);
        
        $localLength = strlen($local);
        $visibleChars = min(1, $localLength);
        $maskedLocal = substr($local, 0, $visibleChars) . str_repeat('*', $localLength - $visibleChars);
        
        return $maskedLocal . '@' . $domain;
    }

    /**
     * Get domain part
     */
    public function domain(): string
    {
        return explode('@', $this->value)[1];
    }

    /**
     * Get local part (before @)
     */
    public function local(): string
    {
        return explode('@', $this->value)[0];
    }

    /**
     * Check if email is from a specific domain
     */
    public function isDomain(string $domain): bool
    {
        return $this->domain() === strtolower($domain);
    }

    /**
     * Check if email is disposable/temporary
     */
    public function isDisposable(): bool
    {
        $disposableDomains = [
            'tempmail.com',
            'guerrillamail.com',
            'mailinator.com',
            '10minutemail.com',
            'throwaway.email',
            'maildrop.cc',
            'temp-mail.org',
        ];

        return in_array($this->domain(), $disposableDomains);
    }

    /**
     * Validate email
     */
    private function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * For JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Check equality
     */
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}