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
    private FakeBankStorage $storage;
    private float $balance = 10000.00;

    public function __construct(?string $storagePath = null)
    {
        $this->storage = new FakeBankStorage($storagePath);
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_PIX_' . uniqid();
        
        $data = [
            'type' => 'pix',
            'status' => 'approved',
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'customer_name' => $request->customerName,
            'customer_document' => $request->getCustomerDocument(),
            'customer_email' => $request->getCustomerEmail(),
            'description' => $request->description,
            'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'qr_code_text' => '00020126330014BR.GOV.BCB.PIX0111' . $transactionId,
        ];
        
        $this->storage->save('transactions', $transactionId, $data);

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->getAmount(),
            currency: $request->getCurrency(),
            message: 'PIX payment created successfully',
            rawResponse: $data,
            metadata: $request->metadata
        );
    }

    public function getPixQrCode(string $transactionId): string
    {
        $transaction = $this->storage->get('transactions', $transactionId);
        return $transaction['qr_code'] ?? '';
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        $transaction = $this->storage->get('transactions', $transactionId);
        return $transaction['qr_code_text'] ?? '';
    }

    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_CC_' . uniqid();
        
        // Detectar número do cartão (pode ser CardNumber object ou string)
        $cardNumber = $request->cardNumber?->value() ?? $request->cardNumber;
        
        $data = [
            'type' => 'credit_card',
            'status' => 'approved',
            'amount' => $request->money->amount(),
            'currency' => $request->money->currency()->value,
            'installments' => $request->installments,
            'card_last4' => substr($cardNumber, -4),
            'card_brand' => $this->detectCardBrand($cardNumber),
            'customer_email' => $request->customerEmail?->value() ?? $request->customerEmail,
            'description' => $request->description,
        ];
        
        $this->storage->save('transactions', $transactionId, $data);

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->money->amount(),
            currency: $request->money->currency()->value,
            message: 'Credit card payment approved',
            rawResponse: $data,
            metadata: $request->metadata
        );
    }

    public function tokenizeCard(array $cardData): string
    {
        $token = 'FAKE_TOKEN_' . uniqid();
        
        $tokenData = [
            'card_number_last4' => substr($cardData['number'] ?? '', -4),
            'card_brand' => $this->detectCardBrand($cardData['number'] ?? ''),
            'card_holder_name' => $cardData['holder_name'] ?? '',
            'card_expiry_month' => $cardData['expiry_month'] ?? '',
            'card_expiry_year' => $cardData['expiry_year'] ?? '',
            'is_active' => true,
        ];
        
        $this->storage->save('tokens', $token, $tokenData);
        
        return $token;
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $transaction = $this->storage->get('transactions', $transactionId);
        
        if ($transaction) {
            $this->storage->update('transactions', $transactionId, [
                'status' => 'captured',
                'captured_amount' => $amount ?? $transaction['amount']
            ]);
        }
        
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
        $transaction = $this->storage->get('transactions', $transactionId);
        
        if ($transaction) {
            $this->storage->update('transactions', $transactionId, [
                'status' => 'cancelled'
            ]);
        }
        
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

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_DC_' . uniqid();
        
        $data = [
            'type' => 'debit_card',
            'status' => 'approved',
            'amount' => $request->amount,
            'currency' => $request->currency,
            'card_last4' => substr($request->cardNumber, -4),
            'card_brand' => $this->detectCardBrand($request->cardNumber),
        ];
        
        $this->storage->save('transactions', $transactionId, $data);
        
        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->amount,
            currency: $request->currency,
            message: 'Debit card payment approved',
            rawResponse: $data
        );
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_BOLETO_' . uniqid();
        
        $data = [
            'type' => 'boleto',
            'status' => 'pending',
            'amount' => $request->money->amount(),
            'currency' => $request->money->currency()->value,
            'due_date' => $request->dueDate,
            'customer_name' => $request->customerName,
            'customer_document' => $request->customerDocument?->value(),
            'barcode' => '34191.79001 01043.510047 91020.150008 1 84460000002000',
            'url' => 'https://fakebank.com/boleto/' . $transactionId,
        ];
        
        $this->storage->save('transactions', $transactionId, $data);

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'pending',
            amount: $request->money->amount(),
            currency: $request->money->currency()->value,
            message: 'Boleto created successfully',
            rawResponse: $data
        );
    }

    public function getBoletoUrl(string $transactionId): string
    {
        $transaction = $this->storage->get('transactions', $transactionId);
        return $transaction['url'] ?? '';
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        $transaction = $this->storage->get('transactions', $transactionId);
        
        if ($transaction) {
            $this->storage->update('transactions', $transactionId, [
                'status' => 'cancelled'
            ]);
        }
        
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

    // ==================== ASSINATURAS/RECORRÊNCIA ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $subscriptionId = 'FAKE_SUB_' . uniqid();
        
        $data = [
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'interval' => $request->getInterval(),
            'customer_id' => $request->customerId,
            'card_token' => $request->cardToken,
            'status' => 'active',
            'description' => $request->description,
            'trial_days' => $request->trialDays,
            'cycles' => $request->cycles,
        ];
        
        $this->storage->save('subscriptions', $subscriptionId, $data);
        
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'active',
            message: 'Subscription created',
            rawResponse: $data
        );
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $subscription = $this->storage->get('subscriptions', $subscriptionId);
        
        if ($subscription) {
            $this->storage->update('subscriptions', $subscriptionId, [
                'status' => 'cancelled'
            ]);
        }
        
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'cancelled',
            message: 'Subscription cancelled',
            rawResponse: $subscription ?? []
        );
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        $subscription = $this->storage->get('subscriptions', $subscriptionId);
        
        if ($subscription) {
            $this->storage->update('subscriptions', $subscriptionId, [
                'status' => 'suspended'
            ]);
        }
        
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'suspended',
            message: 'Subscription suspended',
            rawResponse: $subscription ?? []
        );
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        $subscription = $this->storage->get('subscriptions', $subscriptionId);
        
        if ($subscription) {
            $this->storage->update('subscriptions', $subscriptionId, [
                'status' => 'active'
            ]);
        }
        
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'active',
            message: 'Subscription reactivated',
            rawResponse: $subscription ?? []
        );
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        $this->storage->update('subscriptions', $subscriptionId, $data);
        
        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'active',
            message: 'Subscription updated',
            rawResponse: $data
        );
    }

    // ==================== TRANSAÇÕES ====================
    
	public function getTransactionStatus(string $transactionId): TransactionStatusResponse
	{
		$transaction = $this->storage->get('transactions', $transactionId);
		
		return TransactionStatusResponse::create(
			success: $transaction !== null,
			transactionId: $transactionId,
			status: $transaction['status'] ?? 'not_found',
			amount: $transaction['amount'] ?? null,
			currency: $transaction['currency'] ?? 'BRL',
			rawResponse: $transaction
		);
	}
    public function listTransactions(array $filters = []): array
    {
        return $this->storage->find('transactions', $filters);
    }

    // ==================== ESTORNOS E CHARGEBACKS ====================
    
	public function refund(RefundRequest $request): RefundResponse
	{
		$refundId = 'FAKE_REFUND_' . uniqid();
		
		// Busca transação original
		$transaction = $this->storage->get('transactions', $request->transactionId);
		
		// Verifica se é estorno parcial
		$isPartialRefund = $request->isPartialRefund();
		
		// Define valor do estorno
		$refundAmount = $isPartialRefund 
			? $request->amount 
			: ($transaction['amount'] ?? 0.0);
		
		$data = [
			'transaction_id' => $request->transactionId,
			'amount' => $refundAmount,
			'original_amount' => $transaction['amount'] ?? null,
			'refund_type' => $isPartialRefund ? 'partial' : 'full',
			'reason' => $request->reason ?? null,
			'status' => 'refunded',
		];
		
		$this->storage->save('refunds', $refundId, $data);
		
		// Usa o factory method ::create()
		return RefundResponse::create(
			success: true,
			refundId: $refundId,
			transactionId: $request->transactionId,
			amount: $refundAmount,
			status: 'refunded',
			currency: $transaction['currency'] ?? 'BRL',
			message: $isPartialRefund ? 'Partial refund processed' : 'Full refund processed',
			rawResponse: $data
		);
	}

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $refundId = 'FAKE_REFUND_' . uniqid();
        
        $data = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'status' => 'refunded',
            'type' => 'partial',
        ];
        
        $this->storage->save('refunds', $refundId, $data);
        
        return new RefundResponse(
            success: true,
            refundId: $refundId,
            transactionId: $transactionId,
            amount: $amount,
            status: 'refunded',
            message: 'Partial refund processed',
            rawResponse: $data
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
            status: 'under_review',
            amount: null,
            currency: 'BRL',
            message: 'Chargeback dispute submitted',
            rawResponse: $evidence
        );
    }

    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_SPLIT_' . uniqid();
        
        $data = [
            'type' => 'split_payment',
            'status' => 'approved',
            'amount' => $request->amount,
            'splits' => $request->splits ?? [],
        ];
        
        $this->storage->save('transactions', $transactionId, $data);
        
        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            amount: $request->amount,
            currency: 'BRL',
            message: 'Split payment created',
            rawResponse: $data
        );
    }

    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        $subAccountId = 'FAKE_SUB_ACC_' . uniqid();
        
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'document_number' => $request->documentNumber,
            'status' => 'active',
        ];
        
        $this->storage->save('sub_accounts', $subAccountId, $data);
        
        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'active',
            message: 'Sub-account created',
            rawResponse: $data
        );
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        $this->storage->update('sub_accounts', $subAccountId, $data);
        
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
        $subAccount = $this->storage->get('sub_accounts', $subAccountId);
        
        return new SubAccountResponse(
            success: $subAccount !== null,
            subAccountId: $subAccountId,
            status: $subAccount['status'] ?? 'not_found',
            message: 'Sub-account retrieved',
            rawResponse: $subAccount ?? []
        );
    }

    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        $this->storage->update('sub_accounts', $subAccountId, ['status' => 'active']);
        
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
        $this->storage->update('sub_accounts', $subAccountId, ['status' => 'inactive']);
        
        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'inactive',
            message: 'Sub-account deactivated',
            rawResponse: []
        );
    }

    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        $walletId = 'FAKE_WALLET_' . uniqid();
        
        $data = [
            'customer_id' => $request->customerId ?? null,
            'balance' => 0.0,
            'currency' => $request->currency ?? 'BRL',
        ];
        
        $this->storage->save('wallets', $walletId, $data);
        
        return new WalletResponse(
            success: true,
            walletId: $walletId,
            balance: 0.0,
            currency: $request->currency ?? 'BRL',
            message: 'Wallet created',
            rawResponse: $data
        );
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        $wallet = $this->storage->get('wallets', $walletId);
        
        if ($wallet) {
            $newBalance = ($wallet['balance'] ?? 0) + $amount;
            $this->storage->update('wallets', $walletId, ['balance' => $newBalance]);
            
            return new WalletResponse(
                success: true,
                walletId: $walletId,
                balance: $newBalance,
                currency: $wallet['currency'] ?? 'BRL',
                message: 'Balance added',
                rawResponse: []
            );
        }
        
        return new WalletResponse(
            success: false,
            walletId: $walletId,
            balance: 0,
            currency: 'BRL',
            message: 'Wallet not found',
            rawResponse: []
        );
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        $wallet = $this->storage->get('wallets', $walletId);
        
        if ($wallet) {
            $newBalance = ($wallet['balance'] ?? 0) - $amount;
            $this->storage->update('wallets', $walletId, ['balance' => $newBalance]);
            
            return new WalletResponse(
                success: true,
                walletId: $walletId,
                balance: $newBalance,
                currency: $wallet['currency'] ?? 'BRL',
                message: 'Balance deducted',
                rawResponse: []
            );
        }
        
        return new WalletResponse(
            success: false,
            walletId: $walletId,
            balance: 0,
            currency: 'BRL',
            message: 'Wallet not found',
            rawResponse: []
        );
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        $wallet = $this->storage->get('wallets', $walletId);
        
        return new BalanceResponse(
            success: $wallet !== null,
            balance: $wallet['balance'] ?? 0,
            availableBalance: $wallet['balance'] ?? 0,
            pendingBalance: 0.0,
            currency: $wallet['currency'] ?? 'BRL',
            rawResponse: $wallet ?? []
        );
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        $transferId = 'FAKE_TRANSFER_' . uniqid();
        
        $this->deductBalance($fromWalletId, $amount);
        $this->addBalance($toWalletId, $amount);
        
        $data = [
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'amount' => $amount,
            'status' => 'completed',
        ];
        
        $this->storage->save('transfers', $transferId, $data);
        
        return new TransferResponse(
            success: true,
            transferId: $transferId,
            money: \IsraelNogueira\PaymentHub\ValueObjects\Money::from($amount, \IsraelNogueira\PaymentHub\Enums\Currency::BRL),
            status: \IsraelNogueira\PaymentHub\Enums\PaymentStatus::fromString('completed'),
            message: 'Transfer completed',
            rawResponse: $data
        );
    }

    // ==================== ESCROW (CUSTÓDIA) ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        $escrowId = 'FAKE_ESCROW_' . uniqid();
        
        $data = [
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'status' => 'held',
        ];
        
        $this->storage->save('escrows', $escrowId, $data);
        
        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: $request->getAmount(),
            status: 'held',
            message: 'Amount held in escrow',
            rawResponse: $data
        );
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        $escrow = $this->storage->get('escrows', $escrowId);
        
        if ($escrow) {
            $this->storage->update('escrows', $escrowId, ['status' => 'released']);
        }
        
        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: $escrow['amount'] ?? null,
            status: 'released',
            message: 'Escrow released',
            rawResponse: $escrow ?? []
        );
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        $escrow = $this->storage->get('escrows', $escrowId);
        
        if ($escrow) {
            $newAmount = ($escrow['amount'] ?? 0) - $amount;
            $this->storage->update('escrows', $escrowId, [
                'amount' => $newAmount,
                'status' => 'partially_released'
            ]);
        }
        
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
        $escrow = $this->storage->get('escrows', $escrowId);
        
        if ($escrow) {
            $this->storage->update('escrows', $escrowId, ['status' => 'cancelled']);
        }
        
        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: null,
            status: 'cancelled',
            message: 'Escrow cancelled',
            rawResponse: []
        );
    }

    // ==================== TRANSFERÊNCIAS E SAQUES ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        $transferId = 'FAKE_TRANSFER_' . uniqid();
        
        $data = [
            'amount' => $request->amount,
            'recipient_id' => $request->recipientId,
            'status' => 'completed',
        ];
        
        $this->storage->save('transfers', $transferId, $data);
        
        return new TransferResponse(
            success: true,
            transferId: $transferId,
            money: \IsraelNogueira\PaymentHub\ValueObjects\Money::from($request->amount, \IsraelNogueira\PaymentHub\Enums\Currency::BRL),
            status: \IsraelNogueira\PaymentHub\Enums\PaymentStatus::fromString('completed'),
            message: 'Transfer completed',
            rawResponse: $data
        );
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        $transferId = 'FAKE_TRANSFER_' . uniqid();
        
        $data = [
            'amount' => $request->amount,
            'recipient_id' => $request->recipientId,
            'status' => 'scheduled',
            'scheduled_date' => $date,
        ];
        
        $this->storage->save('transfers', $transferId, $data);
        
        return new TransferResponse(
            success: true,
            transferId: $transferId,
            money: \IsraelNogueira\PaymentHub\ValueObjects\Money::from($request->amount, \IsraelNogueira\PaymentHub\Enums\Currency::BRL),
            status: \IsraelNogueira\PaymentHub\Enums\PaymentStatus::fromString('scheduled'),
            message: 'Transfer scheduled',
            rawResponse: $data
        );
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        $transfer = $this->storage->get('transfers', $transferId);
        
        if ($transfer) {
            $this->storage->update('transfers', $transferId, ['status' => 'cancelled']);
        }
        
        $money = isset($transfer['amount']) 
            ? \IsraelNogueira\PaymentHub\ValueObjects\Money::from($transfer['amount'], \IsraelNogueira\PaymentHub\Enums\Currency::BRL)
            : null;
        
        return new TransferResponse(
            success: true,
            transferId: $transferId,
            money: $money,
            status: \IsraelNogueira\PaymentHub\Enums\PaymentStatus::fromString('cancelled'),
            message: 'Scheduled transfer cancelled',
            rawResponse: []
        );
    }

    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $linkId = 'FAKE_LINK_' . uniqid();
        
        $data = [
            'amount' => $request->amount,
            'description' => $request->description ?? null,
            'url' => 'https://fakebank.com/pay/' . $linkId,
            'status' => 'active',
        ];
        
        $this->storage->save('payment_links', $linkId, $data);
        
        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: $data['url'],
            status: 'active',
            message: 'Payment link created',
            rawResponse: $data
        );
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $link = $this->storage->get('payment_links', $linkId);
        
        return new PaymentLinkResponse(
            success: $link !== null,
            linkId: $linkId,
            url: $link['url'] ?? null,
            status: $link['status'] ?? 'not_found',
            message: 'Payment link retrieved',
            rawResponse: $link ?? []
        );
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $link = $this->storage->get('payment_links', $linkId);
        
        if ($link) {
            $this->storage->update('payment_links', $linkId, ['status' => 'expired']);
        }
        
        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: null,
            status: 'expired',
            message: 'Payment link expired',
            rawResponse: []
        );
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        $customerId = 'FAKE_CUST_' . uniqid();
        
        $data = [
            'name' => $request->name ?? null,
            'email' => $request->email ?? null,
            'document' => $request->document ?? null,
            'phone' => $request->phone ?? null,
        ];
        
        $this->storage->save('customers', $customerId, $data);
        
        return new CustomerResponse(
            success: true,
            customerId: $customerId,
            message: 'Customer created',
            rawResponse: $data
        );
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        $this->storage->update('customers', $customerId, $data);
        
        return new CustomerResponse(
            success: true,
            customerId: $customerId,
            message: 'Customer updated',
            rawResponse: $data
        );
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        $customer = $this->storage->get('customers', $customerId);
        
        return new CustomerResponse(
            success: $customer !== null,
            customerId: $customerId,
            message: 'Customer retrieved',
            rawResponse: $customer ?? []
        );
    }

    public function listCustomers(array $filters = []): array
    {
        return $this->storage->find('customers', $filters);
    }

    // ==================== ANTIFRAUDE ====================
    
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

    // ==================== WEBHOOKS ====================
    
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

    // ==================== SALDO E CONCILIAÇÃO ====================
    
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

    // ==================== HELPERS ====================
    
    private function detectCardBrand(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        
        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'elo' => '/^(4011|4312|4389|4514|4576|5041|5066|5067|6277|6362|6363|6504|6505|6516)/',
            'hipercard' => '/^606282/',
        ];
        
        foreach ($patterns as $brand => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $brand;
            }
        }
        
        return 'unknown';
    }
}
