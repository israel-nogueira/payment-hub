<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class SubAccountRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $documentNumber,
        public readonly string $email,
        public readonly float $incomeValue,
        public readonly ?string $phone = null,
        public readonly ?string $businessName = null,
        public readonly ?string $businessType = null,
        public readonly ?array $address = null,
        public readonly ?array $bankAccount = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'document_number' => $this->documentNumber,
            'email'         => $this->email,
            'income_value'  => $this->incomeValue,
            'phone'         => $this->phone,
            'business_name' => $this->businessName,
            'business_type' => $this->businessType,
            'address'       => $this->address,
            'bank_account'  => $this->bankAccount,
            'metadata'      => $this->metadata,
        ];
    }

    /**
     * Remove máscara antes de verificar o tamanho.
     * Funciona com "12.345.678/0001-90" e "12345678000190".
     */
    private function cleanedDocument(): string
    {
        return preg_replace('/\D/', '', $this->documentNumber);
    }

    public function isCompany(): bool
    {
        return strlen($this->cleanedDocument()) === 14;
    }

    public function isPerson(): bool
    {
        return strlen($this->cleanedDocument()) === 11;
    }

    public function hasBankAccount(): bool
    {
        return $this->bankAccount !== null;
    }
}