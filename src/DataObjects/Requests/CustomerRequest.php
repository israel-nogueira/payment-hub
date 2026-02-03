<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class CustomerRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $documentNumber = null,
        public readonly ?string $phone = null,
        public readonly ?string $birthDate = null,
        public readonly ?array $address = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'document_number' => $this->documentNumber,
            'phone' => $this->phone,
            'birth_date' => $this->birthDate,
            'address' => $this->address,
            'metadata' => $this->metadata,
        ];
    }

    public function hasDocument(): bool
    {
        return $this->documentNumber !== null;
    }

    public function hasAddress(): bool
    {
        return $this->address !== null;
    }
}