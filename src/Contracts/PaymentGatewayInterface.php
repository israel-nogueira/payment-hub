<?php

namespace IsraelNogueira\PaymentHub\Contracts;

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

interface PaymentGatewayInterface
{
    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse;
    
    public function getPixQrCode(string $transactionId): string;
    
    public function getPixCopyPaste(string $transactionId): string;
    
    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse;
    
    public function tokenizeCard(array $cardData): string;
    
    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse;
    
    public function cancelPreAuthorization(string $transactionId): PaymentResponse;
    
    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse;
    
    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse;
    
    public function getBoletoUrl(string $transactionId): string;
    
    public function cancelBoleto(string $transactionId): PaymentResponse;
    
    // ==================== ASSINATURAS/RECORRÊNCIA ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse;
    
    public function cancelSubscription(string $subscriptionId): SubscriptionResponse;
    
    public function suspendSubscription(string $subscriptionId): SubscriptionResponse;
    
    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse;
    
    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse;
    
    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse;
    
    public function listTransactions(array $filters = []): array;
    
    // ==================== ESTORNOS E CHARGEBACKS ====================
    
    public function refund(RefundRequest $request): RefundResponse;
    
    public function partialRefund(string $transactionId, float $amount): RefundResponse;
    
    public function getChargebacks(array $filters = []): array;
    
    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse;
    
    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse;
    
    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse;
    
    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse;
    
    public function getSubAccount(string $subAccountId): SubAccountResponse;
    
    public function activateSubAccount(string $subAccountId): SubAccountResponse;
    
    public function deactivateSubAccount(string $subAccountId): SubAccountResponse;
    
    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse;
    
    public function addBalance(string $walletId, float $amount): WalletResponse;
    
    public function deductBalance(string $walletId, float $amount): WalletResponse;
    
    public function getWalletBalance(string $walletId): BalanceResponse;
    
    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse;
    
    // ==================== ESCROW (CUSTÓDIA) ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse;
    
    public function releaseEscrow(string $escrowId): EscrowResponse;
    
    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse;
    
    public function cancelEscrow(string $escrowId): EscrowResponse;
    
    // ==================== TRANSFERÊNCIAS E SAQUES ====================
    
    public function transfer(TransferRequest $request): TransferResponse;
    
    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse;
    
    public function cancelScheduledTransfer(string $transferId): TransferResponse;
    
    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse;
    
    public function getPaymentLink(string $linkId): PaymentLinkResponse;
    
    public function expirePaymentLink(string $linkId): PaymentLinkResponse;
    
    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse;
    
    public function updateCustomer(string $customerId, array $data): CustomerResponse;
    
    public function getCustomer(string $customerId): CustomerResponse;
    
    public function listCustomers(array $filters = []): array;
    
    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array;
    
    public function addToBlacklist(string $identifier, string $type): bool;
    
    public function removeFromBlacklist(string $identifier, string $type): bool;
    
    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array;
    
    public function listWebhooks(): array;
    
    public function deleteWebhook(string $webhookId): bool;
    
    // ==================== SALDO E CONCILIAÇÃO ====================
    
    public function getBalance(): BalanceResponse;
    
    public function getSettlementSchedule(array $filters = []): array;
    
    public function anticipateReceivables(array $transactionIds): PaymentResponse;
}