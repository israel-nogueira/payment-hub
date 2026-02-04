<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks;

use IsraelNogueira\PaymentHub\Webhooks\Events\WebhookFailed;
use IsraelNogueira\PaymentHub\Webhooks\Events\WebhookProcessed;
use IsraelNogueira\PaymentHub\Webhooks\Events\WebhookReceived;
use IsraelNogueira\PaymentHub\Webhooks\Processors\WebhookProcessorInterface;
use IsraelNogueira\PaymentHub\Webhooks\Signatures\SignatureValidatorInterface;
use IsraelNogueira\PaymentHub\Webhooks\Storage\WebhookStorageInterface;
use IsraelNogueira\PaymentHub\Events\EventDispatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handler principal para processar webhooks
 *
 * Coordena a recepção, validação e processamento de webhooks
 * de diferentes gateways de pagamento.
 */
class WebhookHandler
{
    /** @var array<WebhookProcessorInterface> */
    private array $processors = [];

    private ?SignatureValidatorInterface $signatureValidator;
    private ?WebhookStorageInterface $storage;
    private EventDispatcher $eventDispatcher;
    private LoggerInterface $logger;
    private WebhookConfig $config;

    public function __construct(
        WebhookConfig $config,
        ?SignatureValidatorInterface $signatureValidator = null,
        ?WebhookStorageInterface $storage = null,
        ?EventDispatcher $eventDispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->signatureValidator = $signatureValidator;
        $this->storage = $storage;
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Registra um processador de webhook
     *
     * @param WebhookProcessorInterface $processor
     * @return self
     */
    public function addProcessor(WebhookProcessorInterface $processor): self
    {
        $this->processors[] = $processor;
        
        // Ordena por prioridade (maior primeiro)
        usort($this->processors, function ($a, $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $this;
    }

    /**
     * Processa um webhook recebido
     *
     * @param WebhookPayload $payload
     * @return WebhookResult
     */
    public function handle(WebhookPayload $payload): WebhookResult
    {
        $startTime = microtime(true);

        try {
            $this->logger->info('Webhook received', [
                'id' => $payload->getId(),
                'type' => $payload->getEventType(),
                'gateway' => $payload->getGateway(),
            ]);

            // Dispara evento de recebimento
            $this->eventDispatcher->dispatch(new WebhookReceived($payload));

            // Verifica se já foi processado (idempotência)
            if ($this->storage && $this->storage->wasProcessed($payload->getId())) {
                $this->logger->info('Webhook already processed (idempotent)', [
                    'id' => $payload->getId(),
                ]);

                return WebhookResult::alreadyProcessed($payload);
            }

            // Valida assinatura se configurado
            if ($this->signatureValidator && !$this->validateSignature($payload)) {
                $this->logger->error('Webhook signature validation failed', [
                    'id' => $payload->getId(),
                ]);

                return WebhookResult::invalidSignature($payload);
            }

            // Encontra e executa processador apropriado
            $processor = $this->findProcessor($payload->getEventType());

            if (!$processor) {
                $this->logger->warning('No processor found for webhook type', [
                    'id' => $payload->getId(),
                    'type' => $payload->getEventType(),
                ]);

                return WebhookResult::noProcessorFound($payload);
            }

            // Valida payload
            if (!$processor->validate($payload)) {
                $this->logger->error('Webhook payload validation failed', [
                    'id' => $payload->getId(),
                    'processor' => get_class($processor),
                ]);

                return WebhookResult::validationFailed($payload);
            }

            // Processa o webhook
            $success = $processor->process($payload);

            // Armazena resultado
            if ($this->storage) {
                $this->storage->store($payload, $success);
            }

            $duration = microtime(true) - $startTime;

            if ($success) {
                $this->logger->info('Webhook processed successfully', [
                    'id' => $payload->getId(),
                    'duration' => $duration,
                ]);

                $this->eventDispatcher->dispatch(
                    new WebhookProcessed($payload, $duration)
                );

                return WebhookResult::success($payload, $duration);
            } else {
                $this->logger->error('Webhook processing failed', [
                    'id' => $payload->getId(),
                    'duration' => $duration,
                ]);

                $this->eventDispatcher->dispatch(
                    new WebhookFailed($payload, 'Processing returned false')
                );

                return WebhookResult::failed($payload, 'Processing failed');
            }

        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Webhook processing exception', [
                'id' => $payload->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration' => $duration,
            ]);

            $this->eventDispatcher->dispatch(
                new WebhookFailed($payload, $e->getMessage(), $e)
            );

            return WebhookResult::failed($payload, $e->getMessage(), $e);
        }
    }

    /**
     * Valida a assinatura do webhook
     *
     * @param WebhookPayload $payload
     * @return bool
     */
    private function validateSignature(WebhookPayload $payload): bool
    {
        if (!$this->signatureValidator) {
            return true;
        }

        $signature = $payload->getSignature();
        if (!$signature) {
            $this->logger->warning('No signature found in webhook', [
                'id' => $payload->getId(),
            ]);
            return false;
        }

        return $this->signatureValidator->validate(
            $payload->toJson(),
            $signature,
            $this->config->getSecret()
        );
    }

    /**
     * Encontra um processador apropriado para o tipo de evento
     *
     * @param string $eventType
     * @return WebhookProcessorInterface|null
     */
    private function findProcessor(string $eventType): ?WebhookProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($eventType)) {
                return $processor;
            }
        }

        return null;
    }

    /**
     * Retorna estatísticas de processamento
     *
     * @return array
     */
    public function getStats(): array
    {
        if (!$this->storage) {
            return [];
        }

        return $this->storage->getStats();
    }

    /**
     * Limpa webhooks antigos do armazenamento
     *
     * @param int $olderThanDays Remover webhooks mais antigos que X dias
     * @return int Número de webhooks removidos
     */
    public function cleanup(int $olderThanDays = 30): int
    {
        if (!$this->storage) {
            return 0;
        }

        $count = $this->storage->cleanup($olderThanDays);

        $this->logger->info('Webhook cleanup completed', [
            'removed' => $count,
            'older_than_days' => $olderThanDays,
        ]);

        return $count;
    }
}
