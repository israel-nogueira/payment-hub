<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\ValueObjects\Email;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

/**
 * VERSÃO MELHORADA com ValueObjects
 * 
 * Como usar:
 * 
 * $request = DebitCardPaymentRequest::create(
 *     amount: 89.90,
 *     cardNumber: '5555 5555 5555 4444',
 *     cardHolderName: 'MARIA SILVA',
 *     cardExpiryMonth: '08',
 *     cardExpiryYear: '2027',
 *     cardCvv: '321'
 * );
 */
class DebitCardPaymentRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly ?string $cardToken = null,
        public readonly ?CardNumber $cardNumber = null,
        public readonly ?string $cardHolderName = null,
        public readonly ?string $cardExpiryMonth = null,
        public readonly ?string $cardExpiryYear = null,
        public readonly ?string $cardCvv = null,
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerDocument = null,
        public readonly ?Email $customerEmail = null,
        public readonly ?array $billingAddress = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Payment amount must be greater than zero');
        }

        // Validar que tem token OU dados do cartão
        if (!$this->cardToken && !$this->cardNumber) {
            throw new \InvalidArgumentException('Either cardToken or cardNumber must be provided');
        }

        // Se tem número do cartão, validar dados completos
        if ($this->cardNumber && (!$this->cardExpiryMonth || !$this->cardExpiryYear || !$this->cardCvv)) {
            throw new \InvalidArgumentException('Card expiry and CVV are required when using card number');
        }
    }

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        ?string $cardToken = null,
        ?string $cardNumber = null,
        ?string $cardHolderName = null,
        ?string $cardExpiryMonth = null,
        ?string $cardExpiryYear = null,
        ?string $cardCvv = null,
        ?string $description = null,
        ?string $customerName = null,
        ?string $customerDocument = null,
        ?string $customerEmail = null,
        ?array $billingAddress = null,
        ?array $metadata = null
    ): self {
        // Converte Currency
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        // Cria Money
        $money = Money::from($amount, $currency);

        // Cria CardNumber se fornecido
        $card = $cardNumber ? CardNumber::fromString($cardNumber) : null;

        // Cria Email se fornecido
        $email = $customerEmail ? Email::fromString($customerEmail) : null;

        return new self(
            money: $money,
            cardToken: $cardToken,
            cardNumber: $card,
            cardHolderName: $cardHolderName,
            cardExpiryMonth: $cardExpiryMonth,
            cardExpiryYear: $cardExpiryYear,
            cardCvv: $cardCvv,
            description: $description,
            customerName: $customerName,
            customerDocument: $customerDocument,
            customerEmail: $email,
            billingAddress: $billingAddress,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'card_token' => $this->cardToken,
            'card_number' => $this->cardNumber?->value(),
            'card_holder_name' => $this->cardHolderName,
            'card_expiry_month' => $this->cardExpiryMonth,
            'card_expiry_year' => $this->cardExpiryYear,
            'card_cvv' => $this->cardCvv,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument,
            'customer_email' => $this->customerEmail?->value(),
            'billing_address' => $this->billingAddress,
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
}
