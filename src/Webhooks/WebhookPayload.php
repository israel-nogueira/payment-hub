<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks;

use DateTimeImmutable;

class WebhookPayload
{
    private string $id;
    private string $eventType;
    private array $data;
    private array $headers;
    private DateTimeImmutable $receivedAt;
    private ?string $signature;
    private ?string $gateway;
    private string $rawBody; // ✅ preservado para validação HMAC

    public function __construct(
        string $id,
        string $eventType,
        array $data,
        array $headers = [],
        ?DateTimeImmutable $receivedAt = null,
        ?string $signature = null,
        ?string $gateway = null,
        string $rawBody = ''
    ) {
        $this->id         = $id;
        $this->eventType  = $eventType;
        $this->data       = $data;
        $this->headers    = $headers;
        $this->receivedAt = $receivedAt ?? new DateTimeImmutable();
        $this->signature  = $signature;
        $this->gateway    = $gateway;
        $this->rawBody    = $rawBody;
    }

    public static function fromRequest(
        string $rawBody,
        array $headers = [],
        ?string $gateway = null
    ): self {
        $data = json_decode($rawBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'Invalid JSON payload: ' . json_last_error_msg()
            );
        }

        if (!isset($data['id']) || !isset($data['type'])) {
            throw new \InvalidArgumentException(
                'Webhook payload must contain "id" and "type" fields'
            );
        }

        $signature = $headers['X-Webhook-Signature']
            ?? $headers['X-Hub-Signature-256']
            ?? null;

        return new self(
            id:        $data['id'],
            eventType: $data['type'],
            data:      $data['data'] ?? $data,
            headers:   $headers,
            signature: $signature,
            gateway:   $gateway,
            rawBody:   $rawBody  // ✅ body original preservado
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys  = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    public function getReceivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function getGateway(): ?string
    {
        return $this->gateway;
    }

    /**
     * Retorna o body HTTP bruto original.
     * Use este método para validação de assinatura HMAC.
     */
    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function isType(string $type): bool
    {
        return $this->eventType === $type;
    }

    public function matchesType(string $pattern): bool
    {
        $pattern = str_replace('.', '\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        
        return (bool) preg_match('/^' . $pattern . '$/', $this->eventType);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->eventType,
            'data'        => $this->data,
            'headers'     => $this->headers,
            'received_at' => $this->receivedAt->format('c'),
            'signature'   => $this->signature,
            'gateway'     => $this->gateway,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public function __toString(): string
    {
        return sprintf(
            'Webhook[id=%s, type=%s, gateway=%s]',
            $this->id,
            $this->eventType,
            $this->gateway ?? 'unknown'
        );
    }
}