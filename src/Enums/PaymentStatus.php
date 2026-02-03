<?php

namespace IsraelNogueira\PaymentHub\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case APPROVED = 'approved';
    case COMPLETED = 'completed';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case DECLINED = 'declined';
    case REJECTED = 'rejected';
    case ERROR = 'error';
    case CANCELLED = 'cancelled';
    case CANCELED = 'canceled';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';
    case WAITING = 'waiting';

    /**
     * Check if payment is in a successful state
     */
    public function isPaid(): bool
    {
        return in_array($this, [
            self::PAID,
            self::APPROVED,
            self::COMPLETED,
            self::SUCCESS,
        ]);
    }

    /**
     * Check if payment is still being processed
     */
    public function isPending(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
            self::WAITING,
        ]);
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::DECLINED,
            self::REJECTED,
            self::ERROR,
        ]);
    }

    /**
     * Check if payment was cancelled
     */
    public function isCancelled(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::CANCELED,
            self::VOIDED,
        ]);
    }

    /**
     * Check if payment was refunded
     */
    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    /**
     * Get a user-friendly label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::PAID, self::APPROVED, self::COMPLETED, self::SUCCESS => 'Aprovado',
            self::FAILED, self::DECLINED, self::REJECTED, self::ERROR => 'Recusado',
            self::CANCELLED, self::CANCELED, self::VOIDED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
            self::WAITING => 'Aguardando',
        };
    }

    /**
     * Create from string (case-insensitive)
     */
    public static function fromString(string $status): self
    {
        $normalized = strtolower($status);
        
        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        // Default to pending if unknown status
        return self::PENDING;
    }

    /**
     * Get color for UI display
     */
    public function color(): string
    {
        return match(true) {
            $this->isPaid() => 'green',
            $this->isPending() => 'yellow',
            $this->isFailed() => 'red',
            $this->isCancelled() => 'gray',
            $this->isRefunded() => 'blue',
            default => 'gray',
        };
    }
}