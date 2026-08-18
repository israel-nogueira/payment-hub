<?php

namespace IsraelNogueira\PaymentHub\Enums;

enum PaymentStatus: string
{
    case PENDING     = 'pending';
    case PROCESSING  = 'processing';
    case WAITING     = 'waiting';
    case SCHEDULED   = 'scheduled'; // ✅ adicionado
    case PAID        = 'paid';
    case APPROVED    = 'approved';
    case COMPLETED   = 'completed';
    case SUCCESS     = 'success';
    case FAILED      = 'failed';
    case DECLINED    = 'declined';
    case REJECTED    = 'rejected';
    case ERROR       = 'error';
    case CANCELLED   = 'cancelled';
    case CANCELED    = 'canceled';
    case VOIDED      = 'voided';
    case REFUNDED    = 'refunded';

    public function isPaid(): bool
    {
        return in_array($this, [
            self::PAID,
            self::APPROVED,
            self::COMPLETED,
            self::SUCCESS,
        ]);
    }

    public function isPending(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
            self::WAITING,
            self::SCHEDULED,
        ]);
    }

    public function isFailed(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::DECLINED,
            self::REJECTED,
            self::ERROR,
        ]);
    }

    public function isCancelled(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::CANCELED,
            self::VOIDED,
        ]);
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING, self::PROCESSING         => 'Pendente',
            self::WAITING                           => 'Aguardando',
            self::SCHEDULED                         => 'Agendado',
            self::PAID, self::APPROVED,
            self::COMPLETED, self::SUCCESS          => 'Aprovado',
            self::FAILED, self::DECLINED,
            self::REJECTED, self::ERROR             => 'Recusado',
            self::CANCELLED, self::CANCELED,
            self::VOIDED                            => 'Cancelado',
            self::REFUNDED                          => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match(true) {
            $this->isPaid()      => 'green',
            $this->isPending()   => 'yellow',
            $this->isFailed()    => 'red',
            $this->isCancelled() => 'gray',
            $this->isRefunded()  => 'blue',
            default              => 'gray',
        };
    }

    public static function fromString(string $status): self
    {
        $normalized = strtolower($status);

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        return self::PENDING;
    }
}