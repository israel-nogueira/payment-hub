<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks;

class WebhookConfig
{
    private string $secret;
    private string $signatureHeader;
    private array  $allowedIps;
    private int    $timeout;

    public function __construct(array $config)
    {
        if (empty($config['secret'])) {
            throw new \InvalidArgumentException('WebhookConfig: secret is required.');
        }

        $this->secret          = $config['secret'];
        $this->signatureHeader = $config['signature_header'] ?? 'X-Hub-Signature-256';
        $this->allowedIps      = $config['allowed_ips']      ?? [];
        $this->timeout         = $config['timeout']          ?? 30;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getSignatureHeader(): string
    {
        return $this->signatureHeader;
    }

    public function getAllowedIps(): array
    {
        return $this->allowedIps;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowedIps)) {
            return true; // sem whitelist = todos permitidos
        }

        return in_array($ip, $this->allowedIps, true);
    }
}