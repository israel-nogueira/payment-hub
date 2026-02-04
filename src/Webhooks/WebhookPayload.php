<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks;

use DateTimeImmutable;

/**
 * Representa o payload de um webhook recebido
 *
 * Encapsula todos os dados relacionados a um webhook, incluindo
 * headers, body, timestamp e metadados de processamento.
 */
class WebhookPayload
{
    private string $id;
    private string $eventType;
    private array $data;
    private array $headers;
    private DateTimeImmutable $receivedAt;
    private ?string $signature;
    private ?string $gateway;

    /**
     * @param string $id ID único do webhook
     * @param string $eventType Tipo do evento (ex: 'payment.completed')
     * @param array $data Dados do payload
     * @param array $headers Headers HTTP recebidos
     * @param DateTimeImmutable|null $receivedAt Timestamp de recebimento
     * @param string|null $signature Assinatura para validação
     * @param string|null $gateway Gateway de origem (ex: 'stripe', 'mercadopago')
     */
    public function __construct(
        string $id,
        string $eventType,
        array $data,
        array $headers = [],
        ?DateTimeImmutable $receivedAt = null,
        ?string $signature = null,
        ?string $gateway = null
    ) {
        $this->id = $id;
        $this->eventType = $eventType;
        $this->data = $data;
        $this->headers = $headers;
        $this->receivedAt = $receivedAt ?? new DateTimeImmutable();
        $this->signature = $signature;
        $this->gateway = $gateway;
    }

    /**
     * Cria um WebhookPayload a partir de uma requisição HTTP
     *
     * @param string $rawBody Corpo bruto da requisição
     * @param array $headers Headers da requisição
     * @param string|null $gateway Nome do gateway
     * @return self
     * @throws \InvalidArgumentException Se o payload for inválido
     */
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
            id: $data['id'],
            eventType: $data['type'],
            data: $data['data'] ?? $data,
            headers: $headers,
            signature: $signature,
            gateway: $gateway
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

    /**
     * Obtém um valor específico dos dados
     *
     * @param string $key Chave usando notação dot (ex: 'payment.amount')
     * @param mixed $default Valor padrão se não encontrado
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
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
     * Verifica se o webhook é de um tipo específico
     *
     * @param string $type Tipo para verificar
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->eventType === $type;
    }

    /**
     * Verifica se o webhook corresponde a um padrão de tipo
     *
     * @param string $pattern Padrão com wildcard (ex: 'payment.*')
     * @return bool
     */
    public function matchesType(string $pattern): bool
    {
        $pattern = str_replace('.', '\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        
        return (bool) preg_match('/^' . $pattern . '$/', $this->eventType);
    }

    /**
     * Verifica se o payload contém uma chave específica
     *
     * @param string $key Chave usando notação dot
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Converte o payload para array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->eventType,
            'data' => $this->data,
            'headers' => $this->headers,
            'received_at' => $this->receivedAt->format('c'),
            'signature' => $this->signature,
            'gateway' => $this->gateway,
        ];
    }

    /**
     * Converte o payload para JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Retorna uma representação string do payload
     *
     * @return string
     */
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
