<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Signatures;

class HmacSignatureValidator implements SignatureValidatorInterface
{
    /**
     * Valida a assinatura HMAC-SHA256 do webhook.
     *
     * IMPORTANTE: $rawBody deve ser o body HTTP bruto — nunca o payload
     * re-serializado (toJson()), pois a assinatura foi gerada pelo gateway
     * sobre os bytes exatos recebidos.
     *
     * Aceita assinatura com ou sem prefixo "sha256=".
     */
    public function validate(string $rawBody, string $signature, string $secret): bool
    {
        // Remove prefixo "sha256=" se presente
        $received = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        $expected = hash_hmac('sha256', $rawBody, $secret);

        // Comparação segura contra timing attacks
        return hash_equals($expected, $received);
    }
}