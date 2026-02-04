<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Processors;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

/**
 * Interface para processadores de webhook
 *
 * Cada tipo de evento de webhook deve ter seu próprio processador
 * que implementa esta interface.
 */
interface WebhookProcessorInterface
{
    /**
     * Processa o payload do webhook
     *
     * @param WebhookPayload $payload Dados do webhook recebido
     * @return bool True se processado com sucesso, false caso contrário
     * @throws \Exception Se ocorrer um erro durante o processamento
     */
    public function process(WebhookPayload $payload): bool;

    /**
     * Verifica se este processador pode lidar com o tipo de evento
     *
     * @param string $eventType Tipo do evento (ex: 'payment.completed')
     * @return bool True se pode processar, false caso contrário
     */
    public function supports(string $eventType): bool;

    /**
     * Retorna a prioridade do processador
     *
     * Processadores com maior prioridade são executados primeiro.
     * Útil quando múltiplos processadores podem lidar com o mesmo evento.
     *
     * @return int Prioridade (0-100, onde 100 é a mais alta)
     */
    public function getPriority(): int;

    /**
     * Valida o payload antes de processar
     *
     * @param WebhookPayload $payload Dados do webhook
     * @return bool True se válido, false caso contrário
     */
    public function validate(WebhookPayload $payload): bool;
}
