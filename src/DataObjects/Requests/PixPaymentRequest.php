<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Email;
use IsraelNogueira\PaymentHub\ValueObjects\CPF;
use IsraelNogueira\PaymentHub\ValueObjects\CNPJ;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

/**
 * VERSÃO MELHORADA com Enums e ValueObjects
 * 
 * Como usar:
 * 
 * $request = PixPaymentRequest::create(
 *     amount: 100.50,
 *     currency: Currency::BRL,
 *     customerDocument: '123.456.789-00',
 *     customerEmail: 'joao@email.com'
 * );
 */
class PixPaymentRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly CPF|CNPJ|null $customerDocument = null,
        public readonly ?Email $customerEmail = null,
        public readonly ?int $expiresInMinutes = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('PIX payment amount must be greater than zero');
        }

        if ($this->expiresInMinutes !== null && $this->expiresInMinutes < 1) {
            throw new InvalidAmountException('PIX expiration must be at least 1 minute');
        }
    }

    /**
     * Factory method - mantém compatibilidade com código antigo
     */
    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        ?string $description = null,
        ?string $customerName = null,
        ?string $customerDocument = null,
        ?string $customerEmail = null,
        ?int $expiresInMinutes = null,
        ?array $metadata = null
    ): self {
        // Converte Currency string para Enum se necessário
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        // Cria Money object
        $money = Money::from($amount, $currency);

        // Cria Email object se fornecido
        $email = $customerEmail ? Email::fromString($customerEmail) : null;

        // Cria CPF ou CNPJ object se fornecido
        $document = null;
        if ($customerDocument) {
            $cleaned = preg_replace('/\D/', '', $customerDocument);
            $document = strlen($cleaned) === 11 
                ? CPF::fromString($customerDocument)
                : CNPJ::fromString($customerDocument);
        }

        return new self(
            money: $money,
            description: $description,
            customerName: $customerName,
            customerDocument: $document,
            customerEmail: $email,
            expiresInMinutes: $expiresInMinutes,
            metadata: $metadata
        );
    }

    /**
     * Mantém compatibilidade com código antigo
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument?->value(),
            'customer_email' => $this->customerEmail?->value(),
            'expires_in_minutes' => $this->expiresInMinutes,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Getters para manter compatibilidade
     */
    public function getAmount(): float
    {
        return $this->money->amount();
    }

    public function getCurrency(): string
    {
        return $this->money->currency()->value;
    }

    public function getFormattedAmount(): string
    {
        return $this->money->formatted();
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail?->value();
    }

    public function getCustomerDocument(): ?string
    {
        return $this->customerDocument?->value();
    }

    public function hasExpiration(): bool
    {
        return $this->expiresInMinutes !== null;
    }

    public function isHighValue(): bool
    {
        return $this->money->greaterThan(Money::from(1000, $this->money->currency()));
    }
}