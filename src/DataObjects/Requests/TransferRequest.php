<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class TransferRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
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
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
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
}