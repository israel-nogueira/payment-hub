<?php

namespace IsraelNogueira\PaymentHub\Events;

interface PaymentEventInterface
{
    public function getTransactionId(): string;
    
    public function getTimestamp(): \DateTimeImmutable;
    
    public function getEventName(): string;
    
    public function toArray(): array;
}