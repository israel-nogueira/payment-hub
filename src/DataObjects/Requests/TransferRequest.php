<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

/**
 * VERSÃO MELHORADA com ValueObjects
 * 
 * Como usar:
 * 
 * // Transferência PIX
 * $request = TransferRequest::create(
 *     amount: 100.00,
 *     pixKey: 'usuario@email.com'
 * );
 * 
 * // Transferência bancária
 * $request = TransferRequest::create(
 *     amount: 500.00,
 *     bankCode: '001',
 *     agencyNumber: '1234',
 *     accountNumber: '56789-0',
 *     accountType: 'checking',
 *     documentNumber: '123.456.789-00',
 *     recipientName: 'João Silva'
 * );
 */
class TransferRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly ?string $recipientId = null,
        public readonly ?string $bankCode = null,
        public readonly ?string $agencyNumber = null,
        public readonly ?string $accountNumber = null,
        public readonly ?string $accountType = null,
        public readonly ?string $documentNumber = null,
        public readonly ?string $recipientName = null,
        public readonly ?string $pixKey = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Transfer amount must be greater than zero');
        }

        // Validar que tem pelo menos um método de destinatário
        if (!$this->recipientId && !$this->pixKey && !$this->bankCode) {
            throw new \InvalidArgumentException('Must provide recipientId, pixKey, or bank account details');
        }

        // Se é transferência bancária, validar campos obrigatórios
        if ($this->bankCode && (!$this->accountNumber || !$this->documentNumber)) {
            throw new \InvalidArgumentException('Bank transfers require accountNumber and documentNumber');
        }
    }

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        ?string $recipientId = null,
        ?string $bankCode = null,
        ?string $agencyNumber = null,
        ?string $accountNumber = null,
        ?string $accountType = null,
        ?string $documentNumber = null,
        ?string $recipientName = null,
        ?string $pixKey = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        // Converte Currency
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        // Cria Money
        $money = Money::from($amount, $currency);

        return new self(
            money: $money,
            recipientId: $recipientId,
            bankCode: $bankCode,
            agencyNumber: $agencyNumber,
            accountNumber: $accountNumber,
            accountType: $accountType,
            documentNumber: $documentNumber,
            recipientName: $recipientName,
            pixKey: $pixKey,
            description: $description,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'recipient_id' => $this->recipientId,
            'bank_code' => $this->bankCode,
            'agency_number' => $this->agencyNumber,
            'account_number' => $this->accountNumber,
            'account_type' => $this->accountType,
            'document_number' => $this->documentNumber,
            'recipient_name' => $this->recipientName,
            'pix_key' => $this->pixKey,
            'description' => $this->description,
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

    public function isPixTransfer(): bool
    {
        return $this->pixKey !== null;
    }

    public function isBankTransfer(): bool
    {
        return $this->bankCode !== null && $this->accountNumber !== null;
    }

    public function usesRecipientId(): bool
    {
        return $this->recipientId !== null;
    }

    public function getTransferType(): string
    {
        if ($this->isPixTransfer()) {
            return 'pix';
        }
        if ($this->isBankTransfer()) {
            return 'bank';
        }
        if ($this->usesRecipientId()) {
            return 'recipient';
        }
        return 'unknown';
    }
}
