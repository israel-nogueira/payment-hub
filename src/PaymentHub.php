<?php

namespace IsraelNogueira\PaymentHub;

use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\DebitCardPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubscriptionRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\RefundRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\TransferRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\SplitPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubAccountRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\WalletRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\EscrowRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PaymentLinkRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\CustomerRequest;
use IsraelNogueira\PaymentHub\DataObjects\Responses\PaymentResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\TransactionStatusResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\SubscriptionResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\RefundResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\TransferResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\SubAccountResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\WalletResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\EscrowResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\PaymentLinkResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\CustomerResponse;
use IsraelNogueira\PaymentHub\DataObjects\Responses\BalanceResponse;
use IsraelNogueira\PaymentHub\Events\EventDispatcher;
use IsraelNogueira\PaymentHub\Events\PaymentCreated;
use IsraelNogueira\PaymentHub\Events\PaymentCompleted;
use IsraelNogueira\PaymentHub\Events\PaymentFailed;
use IsraelNogueira\PaymentHub\Events\PaymentRefunded;
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PaymentHub
{
    private EventDispatcher $eventDispatcher;

    public function __construct(
        private PaymentGatewayInterface $gateway,
        private ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = new EventDispatcher();
    }

    public function setGateway(PaymentGatewayInterface $gateway): self
    {
        $this->gateway = $gateway;
        return $this;
    }

    public function getGateway(): PaymentGatewayInterface
    {
        return $this->gateway;
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    // ==================== PIX ====================

    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $this->logger->info('Creating PIX payment', ['amount' => $request->money->amount()]);

        try {
            $response = $this->gateway->createPixPayment($request);

            $this->eventDispatcher->dispatch(new PaymentCreated(
                $response->transactionId,
                $response->money->amount(),
                $response->getCurrency(),
                PaymentMethod::PIX,
                $request->metadata ?? []
            ));

            if ($response->isSuccess()) {
                $this->eventDispatcher->dispatch(new PaymentCompleted(
                    $response->transactionId,
                    $response->money->amount(),
                    $response->getCurrency(),
                    $response->status,
                    $request->metadata ?? []
                ));
            }

            $this->logger->info('PIX payment created', ['transaction_id' => $response->transactionId]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('PIX payment failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getPixQrCode(string $transactionId): string
    {
        return $this->gateway->getPixQrCode($transactionId);
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        return $this->gateway->getPixCopyPaste($transactionId);
    }

    // ==================== CARTÃO DE CRÉDITO ====================

    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $this->logger->info('Creating credit card payment', ['amount' => $request->money->amount()]);

        try {
            $response = $this->gateway->createCreditCardPayment($request);

            $this->eventDispatcher->dispatch(new PaymentCreated(
                $response->transactionId,
                $response->money->amount(),
                $response->getCurrency(),
                PaymentMethod::CREDIT_CARD,
                $request->metadata ?? []
            ));

            if ($response->isSuccess()) {
                $this->eventDispatcher->dispatch(new PaymentCompleted(
                    $response->transactionId,
                    $response->money->amount(),
                    $response->getCurrency(),
                    $response->status,
                    $request->metadata ?? []
                ));
            } elseif ($response->isFailed()) {
                $this->eventDispatcher->dispatch(new PaymentFailed(
                    $response->transactionId,
                    $response->money->amount(),
                    $response->getCurrency(),
                    $response->status,
                    $response->message ?? 'Payment failed',
                    $request->metadata ?? []
                ));
            }

            $this->logger->info('Credit card payment created', ['transaction_id' => $response->transactionId]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Credit card payment failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function tokenizeCard(array $cardData): string
    {
        return $this->gateway->tokenizeCard($cardData);
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        return $this->gateway->capturePreAuthorization($transactionId, $amount);
    }

    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        return $this->gateway->cancelPreAuthorization($transactionId);
    }

    // ==================== CARTÃO DE DÉBITO ====================

    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        $this->logger->info('Creating debit card payment', ['amount' => $request->money->amount()]);

        try {
            $response = $this->gateway->createDebitCardPayment($request);
            $this->logger->info('Debit card payment created', ['transaction_id' => $response->transactionId]);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Debit card payment failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ==================== BOLETO ====================

    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $this->logger->info('Creating boleto', ['amount' => $request->money->amount()]);

        try {
            $response = $this->gateway->createBoleto($request);
            $this->logger->info('Boleto created', ['transaction_id' => $response->transactionId]);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Boleto creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getBoletoUrl(string $transactionId): string
    {
        return $this->gateway->getBoletoUrl($transactionId);
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        return $this->gateway->cancelBoleto($transactionId);
    }

    // ==================== ASSINATURAS/RECORRÊNCIA ====================

    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $this->logger->info('Creating subscription', ['amount' => $request->money->amount()]);

        try {
            $response = $this->gateway->createSubscription($request);
            $this->logger->info('Subscription created', ['subscription_id' => $response->subscriptionId]);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Subscription creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        return $this->gateway->cancelSubscription($subscriptionId);
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        return $this->gateway->suspendSubscription($subscriptionId);
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        return $this->gateway->reactivateSubscription($subscriptionId);
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        return $this->gateway->updateSubscription($subscriptionId, $data);
    }

    // ==================== TRANSAÇÕES ====================

    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        return $this->gateway->getTransactionStatus($transactionId);
    }

    /**
     * @return array<int, mixed>
     */
    public function listTransactions(array $filters = []): array
    {
        return $this->gateway->listTransactions($filters);
    }

    // ==================== ESTORNOS E CHARGEBACKS ====================

    public function refund(RefundRequest $request): RefundResponse
    {
        $this->logger->info('Processing refund', ['transaction_id' => $request->transactionId]);

        try {
            $response = $this->gateway->refund($request);

            $this->eventDispatcher->dispatch(new PaymentRefunded(
                $request->transactionId,
                $response->refundId,
                $response->money->amount(),
                'BRL',
                $request->reason ?? 'Refund requested'
            ));

            $this->logger->info('Refund processed', ['refund_id' => $response->refundId]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Refund failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        return $this->gateway->partialRefund($transactionId, $amount);
    }

    /**
     * @return array<int, mixed>
     */
    public function getChargebacks(array $filters = []): array
    {
        return $this->gateway->getChargebacks($filters);
    }

    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        return $this->gateway->disputeChargeback($chargebackId, $evidence);
    }

    // ==================== SPLIT DE PAGAMENTO ====================

    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        return $this->gateway->createSplitPayment($request);
    }

    // ==================== SUB-CONTAS ====================

    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        return $this->gateway->createSubAccount($request);
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        return $this->gateway->updateSubAccount($subAccountId, $data);
    }

    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        return $this->gateway->getSubAccount($subAccountId);
    }

    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        return $this->gateway->activateSubAccount($subAccountId);
    }

    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        return $this->gateway->deactivateSubAccount($subAccountId);
    }

    // ==================== WALLETS ====================

    public function createWallet(WalletRequest $request): WalletResponse
    {
        return $this->gateway->createWallet($request);
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        return $this->gateway->addBalance($walletId, $amount);
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        return $this->gateway->deductBalance($walletId, $amount);
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        return $this->gateway->getWalletBalance($walletId);
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        return $this->gateway->transferBetweenWallets($fromWalletId, $toWalletId, $amount);
    }

    // ==================== ESCROW (CUSTÓDIA) ====================

    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        return $this->gateway->holdInEscrow($request);
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        return $this->gateway->releaseEscrow($escrowId);
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        return $this->gateway->partialReleaseEscrow($escrowId, $amount);
    }

    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        return $this->gateway->cancelEscrow($escrowId);
    }

    // ==================== TRANSFERÊNCIAS E SAQUES ====================

    public function transfer(TransferRequest $request): TransferResponse
    {
        return $this->gateway->transfer($request);
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        return $this->gateway->scheduleTransfer($request, $date);
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        return $this->gateway->cancelScheduledTransfer($transferId);
    }

    // ==================== LINK DE PAGAMENTO ====================

    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        return $this->gateway->createPaymentLink($request);
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        return $this->gateway->getPaymentLink($linkId);
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        return $this->gateway->expirePaymentLink($linkId);
    }

    // ==================== CLIENTES ====================

    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        return $this->gateway->createCustomer($request);
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        return $this->gateway->updateCustomer($customerId, $data);
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        return $this->gateway->getCustomer($customerId);
    }

    /**
     * @return array<int, mixed>
     */
    public function listCustomers(array $filters = []): array
    {
        return $this->gateway->listCustomers($filters);
    }

    // ==================== ANTIFRAUDE ====================

    /**
     * @return array<string, mixed>
     */
    public function analyzeTransaction(string $transactionId): array
    {
        return $this->gateway->analyzeTransaction($transactionId);
    }

    public function addToBlacklist(string $identifier, string $type): bool
    {
        return $this->gateway->addToBlacklist($identifier, $type);
    }

    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        return $this->gateway->removeFromBlacklist($identifier, $type);
    }

    // ==================== WEBHOOKS ====================

    /**
     * @return array<string, mixed>
     */
    public function registerWebhook(string $url, array $events): array
    {
        return $this->gateway->registerWebhook($url, $events);
    }

    /**
     * @return array<int, mixed>
     */
    public function listWebhooks(): array
    {
        return $this->gateway->listWebhooks();
    }

    public function deleteWebhook(string $webhookId): bool
    {
        return $this->gateway->deleteWebhook($webhookId);
    }

    // ==================== SALDO E CONCILIAÇÃO ====================

    public function getBalance(): BalanceResponse
    {
        return $this->gateway->getBalance();
    }

    /**
     * @return array<int, mixed>
     */
    public function getSettlementSchedule(array $filters = []): array
    {
        return $this->gateway->getSettlementSchedule($filters);
    }

    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        return $this->gateway->anticipateReceivables($transactionIds);
    }
}