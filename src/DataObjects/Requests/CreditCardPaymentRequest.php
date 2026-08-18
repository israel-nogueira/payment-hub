<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\ValueObjects\Email;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

class CreditCardPaymentRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly ?string $cardToken = null,
        public readonly ?CardNumber $cardNumber = null,
        public readonly ?string $cardHolderName = null,
        public readonly ?string $cardExpiryMonth = null,
        public readonly ?string $cardExpiryYear = null, // ✅ corrigido
        public readonly ?string $cardCvv = null,
        public readonly int $installments = 1,
        public readonly bool $capture = true,
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerDocument = null,
        public readonly ?Email $customerEmail = null,
        public readonly ?array $billingAddress = null,
        public readonly ?array $metadata = null
    ) {
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Payment amount must be greater than zero');
        }

        if ($this->installments < 1) {
            throw new InvalidAmountException('Installments must be at least 1');
        }

        if ($this->installments > 12) {
            throw new InvalidAmountException('Maximum 12 installments allowed');
        }

        if (!$this->cardToken && !$this->cardNumber) {
            throw new \InvalidArgumentException('Either cardToken or cardNumber must be provided');
        }

        if ($this->cardNumber && (!$this->cardExpiryMonth || !$this->cardExpiryYear || !$this->cardCvv)) {
            throw new \InvalidArgumentException('Card expiry and CVV are required when using card number');
        }
    }

    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        ?string $cardToken = null,
        ?string $cardNumber = null,
        ?string $cardHolderName = null,
        ?string $cardExpiryMonth = null,
        ?string $cardExpiryYear = null, // ✅ corrigido
        ?string $cardCvv = null,
        int $installments = 1,
        bool $capture = true,
        ?string $description = null,
        ?string $customerName = null,
        ?string $customerDocument = null,
        ?string $customerEmail = null,
        ?array $billingAddress = null,
        ?array $metadata = null
    ): self {
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        $money = Money::from($amount, $currency);
        $card  = $cardNumber ? CardNumber::fromString($cardNumber) : null;
        $email = $customerEmail ? Email::fromString($customerEmail) : null;

        return new self(
            money:            $money,
            cardToken:        $cardToken,
            cardNumber:       $card,
            cardHolderName:   $cardHolderName,
            cardExpiryMonth:  $cardExpiryMonth,
            cardExpiryYear:   $cardExpiryYear,
            cardCvv:          $cardCvv,
            installments:     $installments,
            capture:          $capture,
            description:      $description,
            customerName:     $customerName,
            customerDocument: $customerDocument,
            customerEmail:    $email,
            billingAddress:   $billingAddress,
            metadata:         $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount'           => $this->money->amount(),
            'currency'         => $this->money->currency()->value,
            'card_token'       => $this->cardToken,
            'card_number'      => $this->cardNumber?->value(),
            'card_holder_name' => $this->cardHolderName,
            'card_expiry_month'=> $this->cardExpiryMonth,
            'card_expiry_year' => $this->cardExpiryYear,
            'card_cvv'         => $this->cardCvv,
            'installments'     => $this->installments,
            'capture'          => $this->capture,
            'description'      => $this->description,
            'customer_name'    => $this->customerName,
            'customer_document'=> $this->customerDocument,
            'customer_email'   => $this->customerEmail?->value(),
            'billing_address'  => $this->billingAddress,
            'metadata'         => $this->metadata,
        ];
    }

    public function hasCardToken(): bool
    {
        return $this->cardToken !== null;
    }

    public function hasRawCardData(): bool
    {
        return $this->cardNumber !== null;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardNumber?->brand();
    }

    public function getCardMasked(): ?string
    {
        return $this->cardNumber?->formattedMasked();
    }

    public function getInstallmentAmount(): Money
    {
        return $this->money->divide($this->installments);
    }

    public function isInstallment(): bool
    {
        return $this->installments > 1;
    }

    public function getFormattedDescription(): string
    {
        $desc = $this->description ?? 'Payment';

        if ($this->isInstallment()) {
            $installmentValue = $this->getInstallmentAmount();
            return "{$desc} ({$this->installments}x de {$installmentValue->formatted()})";
        }

        return "{$desc} ({$this->money->formatted()})";
    }
}