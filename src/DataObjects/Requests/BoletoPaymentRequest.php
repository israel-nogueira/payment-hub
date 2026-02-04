<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Email;
use IsraelNogueira\PaymentHub\ValueObjects\CPF;
use IsraelNogueira\PaymentHub\ValueObjects\CNPJ;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

/**
 * VERSÃO MELHORADA com ValueObjects
 * 
 * Como usar:
 * 
 * $request = BoletoPaymentRequest::create(
 *     amount: 150.00,
 *     customerName: 'João Silva',
 *     customerDocument: '123.456.789-00',
 *     customerEmail: 'joao@email.com',
 *     dueDate: '2025-03-15'
 * );
 */
class BoletoPaymentRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly ?string $dueDate = null,
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly CPF|CNPJ|null $customerDocument = null,
        public readonly ?Email $customerEmail = null,
        public readonly ?array $customerAddress = null,
        public readonly ?Money $fineAmount = null,
        public readonly ?float $finePercentage = null,
        public readonly ?Money $interestAmount = null,
        public readonly ?float $interestPercentage = null,
        public readonly ?Money $discountAmount = null,
        public readonly ?string $discountLimitDate = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Boleto amount must be greater than zero');
        }

        // Validar percentuais
        if ($this->finePercentage !== null && ($this->finePercentage < 0 || $this->finePercentage > 100)) {
            throw new InvalidAmountException('Fine percentage must be between 0 and 100');
        }

        if ($this->interestPercentage !== null && ($this->interestPercentage < 0 || $this->interestPercentage > 100)) {
            throw new InvalidAmountException('Interest percentage must be between 0 and 100');
        }
    }

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        ?string $dueDate = null,
        ?string $description = null,
        ?string $customerName = null,
        ?string $customerDocument = null,
        ?string $customerEmail = null,
        ?array $customerAddress = null,
        ?float $fineAmount = null,
        ?float $finePercentage = null,
        ?float $interestAmount = null,
        ?float $interestPercentage = null,
        ?float $discountAmount = null,
        ?string $discountLimitDate = null,
        ?array $metadata = null
    ): self {
        // Converte Currency
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        // Cria Money
        $money = Money::from($amount, $currency);

        // Cria Email se fornecido
        $email = $customerEmail ? Email::fromString($customerEmail) : null;

        // Cria CPF ou CNPJ se fornecido
        $document = null;
        if ($customerDocument) {
            $cleaned = preg_replace('/\D/', '', $customerDocument);
            $document = strlen($cleaned) === 11 
                ? CPF::fromString($customerDocument)
                : CNPJ::fromString($customerDocument);
        }

        // Cria Money objects para multa, juros e desconto
        $fine = $fineAmount !== null ? Money::from($fineAmount, $currency) : null;
        $interest = $interestAmount !== null ? Money::from($interestAmount, $currency) : null;
        $discount = $discountAmount !== null ? Money::from($discountAmount, $currency) : null;

        return new self(
            money: $money,
            dueDate: $dueDate,
            description: $description,
            customerName: $customerName,
            customerDocument: $document,
            customerEmail: $email,
            customerAddress: $customerAddress,
            fineAmount: $fine,
            finePercentage: $finePercentage,
            interestAmount: $interest,
            interestPercentage: $interestPercentage,
            discountAmount: $discount,
            discountLimitDate: $discountLimitDate,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'due_date' => $this->dueDate,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument?->value(),
            'customer_email' => $this->customerEmail?->value(),
            'customer_address' => $this->customerAddress,
            'fine_amount' => $this->fineAmount?->amount(),
            'fine_percentage' => $this->finePercentage,
            'interest_amount' => $this->interestAmount?->amount(),
            'interest_percentage' => $this->interestPercentage,
            'discount_amount' => $this->discountAmount?->amount(),
            'discount_limit_date' => $this->discountLimitDate,
            'metadata' => $this->metadata,
        ];
    }

    // Getters para compatibilidade
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

    public function hasFine(): bool
    {
        return $this->fineAmount !== null || $this->finePercentage !== null;
    }

    public function hasInterest(): bool
    {
        return $this->interestAmount !== null || $this->interestPercentage !== null;
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount !== null;
    }

    public function getTotalWithCharges(): Money
    {
        $total = $this->money;

        if ($this->fineAmount) {
            $total = $total->add($this->fineAmount);
        }

        if ($this->interestAmount) {
            $total = $total->add($this->interestAmount);
        }

        if ($this->finePercentage) {
            $fine = $this->money->percentage($this->finePercentage);
            $total = $total->add($fine);
        }

        if ($this->interestPercentage) {
            $interest = $this->money->percentage($this->interestPercentage);
            $total = $total->add($interest);
        }

        return $total;
    }

    public function getTotalWithDiscount(): Money
    {
        if (!$this->discountAmount) {
            return $this->money;
        }

        return $this->money->subtract($this->discountAmount);
    }
}
