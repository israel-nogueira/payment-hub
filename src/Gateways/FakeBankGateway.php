<?php

namespace IsraelNogueira\PaymentHub\Gateways;

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
class FakeBankGateway implements PaymentGatewayInterface
{
    private array $transactions = [];
    private float $balance = 10000.00;

    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_PIX_' . uniqid();
        
        $this->transactions[$transactionId] = [
            'type' => 'pix',
            'status' => 'approved',
            'amount' => $request->money->amount(),
            'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'qr_code_text' => '00020126330014BR.GOV.BCB.PIX0111' . $transactionId,
        ];

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->money->amount(),
            currency: $request->money->currency()->value,
            message: 'PIX payment created successfully',
            rawResponse: $this->transactions[$transactionId],
            metadata: $request->metadata
        );
    }

    public function getPixQrCode(string $transactionId): string
    {
        return $this->transactions[$transactionId]['qr_code'] ?? '';
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        return $this->transactions[$transactionId]['qr_code_text'] ?? '';
    }

    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_CC_' . uniqid();
        
        $this->transactions[$transactionId] = [
            'type' => 'credit_card',
            'status' => 'approved',
            'amount' => $request->money->amount(),
            'installments' => $request->installments,
        ];

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->money->amount(),
            currency: $request->money->currency()->value,
            message: 'Credit card payment approved',
            rawResponse: $this->transactions[$transactionId],
            metadata: $request->metadata ?? null
        );
    }

    public function tokenizeCard(array $cardData): string
    {
        return 'FAKE_TOKEN_' . uniqid();
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'captured',
            amount: $amount,
            currency: 'BRL',
            message: 'Pre-authorization captured',
            rawResponse: ['captured' => true]
        );
    }

    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'cancelled',
            amount: null,
            currency: 'BRL',
            message: 'Pre-authorization cancelled',
            rawResponse: ['cancelled' => true]
        );
    }

    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_DC_' . uniqid();
        
        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->money->amount(),
            currency: $request->money->currency()->value,
            message: 'Debit card payment approved',
            rawResponse: ['type' => 'debit_card']
        );
    }

    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_BOLETO_' . uniqid();
        
        $this->transactions[$transactionId] = [
            'type' => 'boleto',
            'status' => 'pending',
            'amount' => $request->money->amount(),
            'barcode' => '34191.79001 01043.510047 91020.150008 1 84460000002000',
            'url' => 'https://fakebank.com/boleto/' . $transactionId,
        ];

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'pending',
            amount: $request->money->amount(),
            currency: $request->money->currency()->value,
            message: 'Boleto created successfully',
            rawResponse: $this->transactions[$transactionId]
        );
    }

    public function getBoletoUrl(string $transactionId): string
    {
        return $this->transactions[$transactionId]['url'] ?? '';
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'cancelled',
            amount: null,
            currency: 'BRL',
            message: 'Boleto cancelled',
            rawResponse: ['cancelled' => true]
        );
    }

    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        return new SubscriptionResponse(
            success: true,
            subscriptionId: 'FAKE_SUB_' . uniqid(),
            status: 'active',
            message: 'Subscription created',
            rawResponse: []
        );
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'cancelled',
            message: 'Subscription cancelled',
            rawResponse: []
        );
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'suspended',
            message: 'Subscription suspended',
            rawResponse: []
        );
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'active',
            message: 'Subscription reactivated',
            rawResponse: []
        );
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'active',
            message: 'Subscription updated',
            rawResponse: $data
        );
    }

    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $transaction = $this->transactions[$transactionId] ?? null;
        
        return new TransactionStatusResponse(
            success: true,
            transactionId: $transactionId,
            status: $transaction['status'] ?? 'not_found',
            amount: $transaction['amount'] ?? null,
            currency: 'BRL',
            rawResponse: $transaction
        );
    }

    public function listTransactions(array $filters = []): array
    {
        return array_map(fn($id, $data) => array_merge(['id' => $id], $data), 
            array_keys($this->transactions), 
            $this->transactions
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        return new RefundResponse(
            success: true,
            refundId: 'FAKE_REFUND_' . uniqid(),
            transactionId: $request->transactionId,
            amount: $request->money->amount(),
            status: 'refunded',
            message: 'Refund processed',
            rawResponse: []
        );
    }

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        return new RefundResponse(
            success: true,
            refundId: 'FAKE_REFUND_' . uniqid(),
            transactionId: $transactionId,
            amount: $amount,
            status: 'refunded',
            message: 'Partial refund processed',
            rawResponse: []
        );
    }

    public function getChargebacks(array $filters = []): array
    {
        return [];
    }

    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        return PaymentResponse::create(
            success: true,
            transactionId: $chargebackId,
            status: 'pending',
            amount: null,
            currency: 'BRL',
            message: 'Chargeback dispute submitted',
            rawResponse: $evidence
        );
    }

    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        return PaymentResponse::create(
            success: true,
            transactionId: 'FAKE_SPLIT_' . uniqid(),
            status: 'approved',
            amount: $request->money->amount(),
            currency: 'BRL',
            message: 'Split payment created',
            rawResponse: []
        );
    }

    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        return new SubAccountResponse(
            success: true,
            subAccountId: 'FAKE_SUB_ACC_' . uniqid(),
            status: 'active',
            message: 'Sub-account created',
            rawResponse: []
        );
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'active',
            message: 'Sub-account updated',
            rawResponse: $data
        );
    }

    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'active',
            message: 'Sub-account retrieved',
            rawResponse: []
        );
    }

    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'active',
            message: 'Sub-account activated',
            rawResponse: []
        );
    }

    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'inactive',
            message: 'Sub-account deactivated',
            rawResponse: []
        );
    }

    public function createWallet(WalletRequest $request): WalletResponse
    {
        return new WalletResponse(
            success: true,
            walletId: 'FAKE_WALLET_' . uniqid(),
            balance: 0.0,
            currency: 'BRL',
            message: 'Wallet created',
            rawResponse: []
        );
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        return new WalletResponse(
            success: true,
            walletId: $walletId,
            balance: $amount,
            currency: 'BRL',
            message: 'Balance added',
            rawResponse: []
        );
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        return new WalletResponse(
            success: true,
            walletId: $walletId,
            balance: -$amount,
            currency: 'BRL',
            message: 'Balance deducted',
            rawResponse: []
        );
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        return new BalanceResponse(
            success: true,
            balance: $this->balance,
            availableBalance: $this->balance,
            pendingBalance: 0.0,
            currency: 'BRL',
            rawResponse: []
        );
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        return new TransferResponse(
            success: true,
            transferId: 'FAKE_TRANSFER_' . uniqid(),
            amount: $amount,
            status: 'completed',
            message: 'Transfer completed',
            rawResponse: []
        );
    }

    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        return new EscrowResponse(
            success: true,
            escrowId: 'FAKE_ESCROW_' . uniqid(),
            amount: $request->money->amount(),
            status: 'held',
            message: 'Amount held in escrow',
            rawResponse: []
        );
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: null,
            status: 'released',
            message: 'Escrow released',
            rawResponse: []
        );
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: $amount,
            status: 'partially_released',
            message: 'Escrow partially released',
            rawResponse: []
        );
    }

    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: null,
            status: 'cancelled',
            message: 'Escrow cancelled',
            rawResponse: []
        );
    }

    public function transfer(TransferRequest $request): TransferResponse
    {
        return new TransferResponse(
            success: true,
            transferId: 'FAKE_TRANSFER_' . uniqid(),
            amount: $request->money->amount(),
            status: 'completed',
            message: 'Transfer completed',
            rawResponse: []
        );
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        return new TransferResponse(
            success: true,
            transferId: 'FAKE_TRANSFER_' . uniqid(),
            amount: $request->money->amount(),
            status: 'scheduled',
            message: 'Transfer scheduled',
            rawResponse: ['scheduled_date' => $date]
        );
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        return new TransferResponse(
            success: true,
            transferId: $transferId,
            amount: null,
            status: 'cancelled',
            message: 'Scheduled transfer cancelled',
            rawResponse: []
        );
    }

    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $linkId = 'FAKE_LINK_' . uniqid();
        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: 'https://fakebank.com/pay/' . $linkId,
            status: 'active',
            message: 'Payment link created',
            rawResponse: []
        );
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: 'https://fakebank.com/pay/' . $linkId,
            status: 'active',
            message: 'Payment link retrieved',
            rawResponse: []
        );
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: null,
            status: 'expired',
            message: 'Payment link expired',
            rawResponse: []
        );
    }

    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        return new CustomerResponse(
            success: true,
            customerId: 'FAKE_CUST_' . uniqid(),
            message: 'Customer created',
            rawResponse: []
        );
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        return new CustomerResponse(
            success: true,
            customerId: $customerId,
            message: 'Customer updated',
            rawResponse: $data
        );
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        return new CustomerResponse(
            success: true,
            customerId: $customerId,
            message: 'Customer retrieved',
            rawResponse: []
        );
    }

    public function listCustomers(array $filters = []): array
    {
        return [];
    }

    public function analyzeTransaction(string $transactionId): array
    {
        return [
            'risk_score' => rand(1, 100),
            'status' => 'approved',
            'recommendation' => 'approve',
        ];
    }

    public function addToBlacklist(string $identifier, string $type): bool
    {
        return true;
    }

    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        return true;
    }

    public function registerWebhook(string $url, array $events): array
    {
        return [
            'webhook_id' => 'FAKE_WEBHOOK_' . uniqid(),
            'url' => $url,
            'events' => $events,
        ];
    }

    public function listWebhooks(): array
    {
        return [];
    }

    public function deleteWebhook(string $webhookId): bool
    {
        return true;
    }

    public function getBalance(): BalanceResponse
    {
        return new BalanceResponse(
            success: true,
            balance: $this->balance,
            availableBalance: $this->balance,
            pendingBalance: 0.0,
            currency: 'BRL',
            rawResponse: []
        );
    }

    public function getSettlementSchedule(array $filters = []): array
    {
        return [];
    }

    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        return PaymentResponse::create(
            success: true,
            transactionId: 'FAKE_ANTICIPATION_' . uniqid(),
            status: 'processing',
            amount: null,
            currency: 'BRL',
            message: 'Anticipation requested',
            rawResponse: ['transaction_ids' => $transactionIds]
        );
    }
}