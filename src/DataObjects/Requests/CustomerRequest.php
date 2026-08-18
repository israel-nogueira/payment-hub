<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\ValueObjects\Email;
use IsraelNogueira\PaymentHub\ValueObjects\CPF;
use IsraelNogueira\PaymentHub\ValueObjects\CNPJ;

class CustomerRequest
{
    public function __construct(
        public readonly string $name,
        public readonly Email $email,
        public readonly CPF|CNPJ|null $document = null,
        public readonly ?string $phone = null,
        public readonly ?string $birthDate = null,
        public readonly ?array $address = null,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Factory method — aceita strings e converte para ValueObjects
     */
    public static function create(
        string $name,
        string $email,
        ?string $document = null,
        ?string $phone = null,
        ?string $birthDate = null,
        ?array $address = null,
        ?array $metadata = null
    ): self {
        $emailVO = Email::fromString($email);

        $documentVO = null;
        if ($document) {
            $cleaned    = preg_replace('/\D/', '', $document);
            $documentVO = strlen($cleaned) === 11
                ? CPF::fromString($document)
                : CNPJ::fromString($document);
        }

        return new self(
            name:      $name,
            email:     $emailVO,
            document:  $documentVO,
            phone:     $phone,
            birthDate: $birthDate,
            address:   $address,
            metadata:  $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'email'     => $this->email->value(),
            'document'  => $this->document?->value(),
            'phone'     => $this->phone,
            'birth_date'=> $this->birthDate,
            'address'   => $this->address,
            'metadata'  => $this->metadata,
        ];
    }

    public function hasDocument(): bool
    {
        return $this->document !== null;
    }

    public function hasAddress(): bool
    {
        return $this->address !== null;
    }

    public function isCompany(): bool
    {
        return $this->document instanceof CNPJ;
    }

    public function isPerson(): bool
    {
        return $this->document instanceof CPF;
    }
}