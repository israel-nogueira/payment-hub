<?php

namespace IsraelNogueira\PaymentHub\Gateways\Stripe;

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
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\GatewayException;

/**
 * Stripe Gateway - International Payment Processing
 * 
 * Supports:
 * - Credit Cards (international + local)
 * - Subscriptions (recurring billing)
 * - Refunds (full/partial)
 * - Customer management
 * - Payment Links
 * 
 * Does NOT support:
 * - PIX (Brazil only)
 * - Boleto (Brazil only)
 * - Direct debit cards
 */
class StripeGateway implements PaymentGatewayInterface
{
    private const API_URL = 'https://api.stripe.com/v1';
    private const API_VERSION = '2024-12-18.acacia';
    
    private string $apiKey;
    private bool $testMode;

    public function __construct(string $apiKey, bool $testMode = false)
    {
        $this->apiKey = $apiKey;
        $this->testMode = $testMode;
        
        // Validate API key format
        if (!str_starts_with($apiKey, 'sk_test_') && !str_starts_with($apiKey, 'sk_live_')) {
            throw new GatewayException('Invalid Stripe API key format');
        }
        
        // Ensure test/live mode matches key
        $isTestKey = str_starts_with($apiKey, 'sk_test_');
        if ($testMode !== $isTestKey) {
            throw new GatewayException('API key mode mismatch: test key requires testMode=true, live key requires testMode=false');
        }
    }

    // ==================== MÉTODOS PRIVADOS ====================
    
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = self::API_URL . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Stripe-Version: ' . self::API_VERSION,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new GatewayException('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decoded['error']['message'] ?? 'Request failed';
            $errorType = $decoded['error']['type'] ?? 'unknown_error';
            
            throw new GatewayException(
                "Stripe {$errorType}: {$errorMessage}",
                $httpCode,
                null,
                ['response' => $decoded]
            );
        }

        return $decoded ?? [];
    }

    private function mapStripeStatus(string $stripeStatus): PaymentStatus
    {
        $statusMap = [
            // PaymentIntent statuses
            'succeeded' => PaymentStatus::APPROVED,
            'processing' => PaymentStatus::PROCESSING,
            'requires_payment_method' => PaymentStatus::PENDING,
            'requires_confirmation' => PaymentStatus::PENDING,
            'requires_action' => PaymentStatus::PENDING,
            'requires_capture' => PaymentStatus::AUTHORIZED,
            'canceled' => PaymentStatus::CANCELLED,
            
            // Charge statuses
            'pending' => PaymentStatus::PENDING,
            'failed' => PaymentStatus::FAILED,
            
            // Subscription statuses
            'active' => PaymentStatus::APPROVED,
            'past_due' => PaymentStatus::PENDING,
            'unpaid' => PaymentStatus::FAILED,
            'incomplete' => PaymentStatus::PENDING,
            'incomplete_expired' => PaymentStatus::FAILED,
            'trialing' => PaymentStatus::APPROVED,
            'paused' => PaymentStatus::CANCELLED,
            
            // Refund statuses
            'refunded' => PaymentStatus::REFUNDED,
        ];

        return $statusMap[$stripeStatus] ?? PaymentStatus::fromString($stripeStatus);
    }

    private function convertAmount(float $amount): int
    {
        // Stripe usa centavos (smallest currency unit)
        return (int) round($amount * 100);
    }

    private function convertFromStripeAmount(int $amount): float
    {
        return $amount / 100;
    }

    private function getCurrencyCode(Currency $currency): string
    {
        return strtolower($currency->value);
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->phone) {
            $data['phone'] = $request->phone;
        }

        if ($request->documentNumber) {
            $data['metadata[document_number]'] = $request->documentNumber;
        }

        if ($request->address) {
            $data['address[line1]'] = $request->address['street'] ?? null;
            $data['address[line2]'] = $request->address['complement'] ?? null;
            $data['address[city]'] = $request->address['city'] ?? null;
            $data['address[state]'] = $request->address['state'] ?? null;
            $data['address[postal_code]'] = $request->address['zipcode'] ?? null;
            $data['address[country]'] = $request->address['country'] ?? 'US';
        }

        $response = $this->request('POST', '/customers', $data);

        return new CustomerResponse(
            success: true,
            customerId: $response['id'],
            message: 'Customer created successfully',
            rawResponse: $response
        );
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        $response = $this->request('POST', "/customers/{$customerId}", $data);

        return new CustomerResponse(
            success: true,
            customerId: $response['id'],
            message: 'Customer updated successfully',
            rawResponse: $response
        );
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        $response = $this->request('GET', "/customers/{$customerId}");

        return new CustomerResponse(
            success: true,
            customerId: $response['id'],
            message: 'Customer retrieved successfully',
            rawResponse: $response
        );
    }

    public function listCustomers(array $filters = []): array
    {
        $response = $this->request('GET', '/customers', $filters);
        return $response['data'] ?? [];
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('PIX is not supported by Stripe - available only in Brazilian gateways (Asaas, PagSeguro, etc)');
    }
    
    public function getPixQrCode(string $transactionId): string
    {
        throw new GatewayException('PIX is not supported by Stripe');
    }
    
    public function getPixCopyPaste(string $transactionId): string
    {
        throw new GatewayException('PIX is not supported by Stripe');
    }
    
    // ==================== CARTÃO DE CRÉDITO ====================
    
	public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse{
		// Criar/buscar customer
		$customerData = [
			'name' => $request->customerName ?? 'Customer',
			'email' => $request->customerEmail?->value() ?? 'customer@example.com',
		];

		// ✅ FIX 1: customerDocument é string, não objeto
		if ($request->customerDocument) {
			$customerData['metadata[document]'] = $request->customerDocument;
		}

		$customerResponse = $this->request('POST', '/customers', $customerData);
		$customerId = $customerResponse['id'];

		// Criar PaymentMethod com cartão
		$pmData = [
			'type' => 'card',
			// ✅ FIX 2: usar value() ao invés de sanitized()
			'card[number]' => $request->cardNumber->value(),
			'card[exp_month]' => $request->cardExpiryMonth,
			'card[exp_year]' => $request->cardExpiryYear,
			'card[cvc]' => $request->cardCvv,
		];

		if ($request->cardHolderName) {
			$pmData['billing_details[name]'] = $request->cardHolderName;
		}

		$paymentMethod = $this->request('POST', '/payment_methods', $pmData);

        // Anexar PaymentMethod ao Customer
        $this->request('POST', "/payment_methods/{$paymentMethod['id']}/attach", [
            'customer' => $customerId
        ]);

        // Criar PaymentIntent
        $amount = $this->convertAmount($request->money->amount());
        $currency = $this->getCurrencyCode($request->money->currency());

        $intentData = [
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethod['id'],
            'confirm' => true,
            'automatic_payment_methods[enabled]' => false,
            'capture_method' => 'automatic',
        ];

        if ($request->description) {
            $intentData['description'] = $request->description;
        }

        // Setup future usage se salvar cartão
        if ($request->saveCard) {
            $intentData['setup_future_usage'] = 'off_session';
        }

        // Metadata para parcelamento
        if ($request->installments > 1) {
            $intentData['metadata[installments]'] = $request->installments;
        }

        $response = $this->request('POST', '/payment_intents', $intentData);

        $money = Money::from($this->convertFromStripeAmount($response['amount']), Currency::fromString(strtoupper($response['currency'])));

        return new PaymentResponse(
            success: $response['status'] === 'succeeded',
            transactionId: $response['id'],
            status: $this->mapStripeStatus($response['status']),
            money: $money,
            message: $response['status'] === 'succeeded' ? 'Payment successful' : 'Payment processing',
            rawResponse: $response,
            metadata: [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethod['id'],
                'charge_id' => $response['latest_charge'] ?? null,
                'card_brand' => $paymentMethod['card']['brand'] ?? null,
                'card_last4' => $paymentMethod['card']['last4'] ?? null,
            ]
        );
    }
    
    public function tokenizeCard(array $cardData): string
    {
        $data = [
            'type' => 'card',
            'card[number]' => $cardData['number'],
            'card[exp_month]' => $cardData['expiryMonth'],
            'card[exp_year]' => $cardData['expiryYear'],
            'card[cvc]' => $cardData['cvv'],
        ];

        if (isset($cardData['holderName'])) {
            $data['billing_details[name]'] = $cardData['holderName'];
        }

        $response = $this->request('POST', '/payment_methods', $data);
        
        return $response['id'];
    }
    
    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $data = [];
        
        if ($amount !== null) {
            $data['amount_to_capture'] = $this->convertAmount($amount);
        }

        $response = $this->request('POST', "/payment_intents/{$transactionId}/capture", $data);

        $money = Money::from($this->convertFromStripeAmount($response['amount']), Currency::fromString(strtoupper($response['currency'])));

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapStripeStatus($response['status']),
            money: $money,
            message: 'Pre-authorization captured successfully',
            rawResponse: $response
        );
    }
    
    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $response = $this->request('POST', "/payment_intents/{$transactionId}/cancel");

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: PaymentStatus::CANCELLED,
            money: null,
            message: 'Pre-authorization cancelled successfully',
            rawResponse: $response
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Direct debit card payments are not supported by Stripe API - use credit card flow instead');
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Boleto is not supported by Stripe - available only in Brazilian gateways (Asaas, PagSeguro, etc)');
    }
    
    public function getBoletoUrl(string $transactionId): string
    {
        throw new GatewayException('Boleto is not supported by Stripe');
    }
    
    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        throw new GatewayException('Boleto is not supported by Stripe');
    }
    
    // ==================== ASSINATURAS ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        // Criar Price (produto de assinatura)
        $priceData = [
            'unit_amount' => $this->convertAmount($request->money->amount()),
            'currency' => $this->getCurrencyCode($request->money->currency()),
            'recurring[interval]' => $this->mapInterval($request->interval->value),
            'product_data[name]' => $request->description ?? 'Subscription',
        ];

        $price = $this->request('POST', '/prices', $priceData);

        // Criar assinatura
        $subData = [
            'customer' => $request->customerId,
            'items[0][price]' => $price['id'],
        ];

        if ($request->paymentMethod) {
            $subData['default_payment_method'] = $request->paymentMethod;
        }

        if ($request->trialDays > 0) {
            $subData['trial_period_days'] = $request->trialDays;
        }

        if ($request->cycles) {
            // Stripe usa subscription_schedule para limitar ciclos
            $subData['metadata[max_cycles]'] = $request->cycles;
        }

        $response = $this->request('POST', '/subscriptions', $subData);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'],
            message: 'Subscription created successfully',
            rawResponse: $response
        );
    }
    
    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('DELETE', "/subscriptions/{$subscriptionId}");

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'canceled',
            message: 'Subscription cancelled successfully',
            rawResponse: $response
        );
    }
    
    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/subscriptions/{$subscriptionId}", [
            'pause_collection[behavior]' => 'void',
        ]);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'],
            message: 'Subscription suspended successfully',
            rawResponse: $response
        );
    }
    
    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/subscriptions/{$subscriptionId}", [
            'pause_collection' => '',
        ]);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'],
            message: 'Subscription reactivated successfully',
            rawResponse: $response
        );
    }
    
    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        $response = $this->request('POST', "/subscriptions/{$subscriptionId}", $data);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'],
            message: 'Subscription updated successfully',
            rawResponse: $response
        );
    }

    private function mapInterval(string $interval): string
    {
        $intervalMap = [
            'daily' => 'day',
            'weekly' => 'week',
            'monthly' => 'month',
            'yearly' => 'year',
        ];

        return $intervalMap[strtolower($interval)] ?? 'month';
    }
    
    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $response = $this->request('GET', "/payment_intents/{$transactionId}");

        $money = Money::from($this->convertFromStripeAmount($response['amount']), Currency::fromString(strtoupper($response['currency'])));

        return new TransactionStatusResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapStripeStatus($response['status']),
            money: $money,
            rawResponse: $response
        );
    }
    
    public function listTransactions(array $filters = []): array
    {
        $response = $this->request('GET', '/payment_intents', $filters);
        return $response['data'] ?? [];
    }
    
    // ==================== ESTORNOS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [
            'payment_intent' => $request->transactionId,
        ];

        if ($request->reason) {
            $data['metadata[reason]'] = $request->reason;
        }

        $response = $this->request('POST', '/refunds', $data);

        $money = Money::from($this->convertFromStripeAmount($response['amount']), Currency::fromString(strtoupper($response['currency'])));

        return new RefundResponse(
            success: true,
            refundId: $response['id'],
            transactionId: $request->transactionId,
            money: $money,
            status: $this->mapStripeStatus($response['status']),
            message: 'Refund processed successfully',
            rawResponse: $response
        );
    }
    
    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $data = [
            'payment_intent' => $transactionId,
            'amount' => $this->convertAmount($amount),
        ];

        $response = $this->request('POST', '/refunds', $data);

        $money = Money::from($this->convertFromStripeAmount($response['amount']), Currency::fromString(strtoupper($response['currency'])));

        return new RefundResponse(
            success: true,
            refundId: $response['id'],
            transactionId: $transactionId,
            money: $money,
            status: $this->mapStripeStatus($response['status']),
            message: 'Partial refund processed successfully',
            rawResponse: $response
        );
    }
    
    public function getChargebacks(array $filters = []): array
    {
        // Stripe chama de "disputes"
        $response = $this->request('GET', '/disputes', $filters);
        return $response['data'] ?? [];
    }
    
    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        $data = [];
        
        foreach ($evidence as $key => $value) {
            $data["evidence[{$key}]"] = $value;
        }

        $response = $this->request('POST', "/disputes/{$chargebackId}", $data);

        return new PaymentResponse(
            success: true,
            transactionId: $chargebackId,
            status: PaymentStatus::PROCESSING,
            money: null,
            message: 'Chargeback disputed successfully',
            rawResponse: $response
        );
    }
    
    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Split payments require Stripe Connect - use Connect Accounts and Transfers instead');
    }
    
    // ==================== SUB-CONTAS (STRIPE CONNECT) ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        $data = [
            'type' => 'express',
            'country' => 'US',
            'email' => $request->email,
            'business_profile[name]' => $request->name,
        ];

        if ($request->metadata) {
            foreach ($request->metadata as $key => $value) {
                $data["metadata[{$key}]"] = $value;
            }
        }

        $response = $this->request('POST', '/accounts', $data);

        return new SubAccountResponse(
            success: true,
            subAccountId: $response['id'],
            status: 'pending',
            message: 'Sub-account created successfully',
            rawResponse: $response
        );
    }
    
    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        $response = $this->request('POST', "/accounts/{$subAccountId}", $data);

        return new SubAccountResponse(
            success: true,
            subAccountId: $response['id'],
            status: 'active',
            message: 'Sub-account updated successfully',
            rawResponse: $response
        );
    }
    
    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        $response = $this->request('GET', "/accounts/{$subAccountId}");

        return new SubAccountResponse(
            success: true,
            subAccountId: $response['id'],
            status: 'active',
            message: 'Sub-account retrieved successfully',
            rawResponse: $response
        );
    }
    
    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        // Stripe não tem "ativação" explícita - contas são ativadas quando completam onboarding
        throw new GatewayException('Stripe Connect accounts are activated automatically upon completing onboarding');
    }
    
    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        $response = $this->request('DELETE', "/accounts/{$subAccountId}");

        return new SubAccountResponse(
            success: true,
            subAccountId: $subAccountId,
            status: 'deactivated',
            message: 'Sub-account deactivated successfully',
            rawResponse: $response
        );
    }
    
    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('Wallets not directly supported - use Customer Balance API or Connect with separate balance tracking');
    }
    
    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not directly supported - use Customer Balance API');
    }
    
    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not directly supported - use Customer Balance API');
    }
    
    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Wallets not directly supported - use Customer Balance API');
    }
    
    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Wallet transfers not supported - use Stripe Connect Transfers between accounts');
    }
    
    // ==================== ESCROW ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported - use Stripe Connect with separate_charges_and_transfers or PaymentIntent with capture_method=manual');
    }
    
    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported');
    }
    
    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported');
    }
    
    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported');
    }
    
    // ==================== TRANSFERÊNCIAS ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        throw new GatewayException('Transfers require Stripe Connect - use /v1/transfers endpoint with Connect accounts');
    }
    
    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        throw new GatewayException('Scheduled transfers not directly supported - implement custom scheduling logic');
    }
    
    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        throw new GatewayException('Scheduled transfers not directly supported');
    }
    
    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $data = [
            'line_items[0][price_data][currency]' => 'usd',
            'line_items[0][price_data][product_data][name]' => $request->description ?? 'Payment Link',
            'line_items[0][price_data][unit_amount]' => $this->convertAmount($request->amount),
            'line_items[0][quantity]' => 1,
        ];

        if ($request->expiresAt) {
            $data['expires_at'] = strtotime($request->expiresAt);
        }

        if (isset($request->metadata['max_uses'])) {
            // Stripe Payment Links não suportam max_uses diretamente
            $data['metadata[max_uses]'] = $request->metadata['max_uses'];
        }

        $response = $this->request('POST', '/payment_links', $data);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['url'],
            status: $response['active'] ? 'active' : 'inactive',
            message: 'Payment link created successfully',
            rawResponse: $response
        );
    }
    
    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/payment_links/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['url'],
            status: $response['active'] ? 'active' : 'inactive',
            message: 'Payment link retrieved successfully',
            rawResponse: $response
        );
    }
    
    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('POST', "/payment_links/{$linkId}", [
            'active' => false
        ]);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['url'],
            status: 'expired',
            message: 'Payment link expired successfully',
            rawResponse: $response
        );
    }
    
    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        // Stripe Radar é automático
        $response = $this->request('GET', "/payment_intents/{$transactionId}");
        
        return [
            'risk_level' => $response['charges']['data'][0]['outcome']['risk_level'] ?? 'normal',
            'risk_score' => $response['charges']['data'][0]['outcome']['risk_score'] ?? null,
            'seller_message' => $response['charges']['data'][0]['outcome']['seller_message'] ?? null,
        ];
    }
    
    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management not available via API - use Stripe Radar rules in Dashboard');
    }
    
    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management not available via API - use Stripe Radar rules in Dashboard');
    }
    
    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        $data = [
            'url' => $url,
            'enabled_events' => $events,
        ];

        $response = $this->request('POST', '/webhook_endpoints', $data);

        return [
            'webhook_id' => $response['id'],
            'url' => $url,
            'events' => $events,
            'secret' => $response['secret'],
        ];
    }
    
    public function listWebhooks(): array
    {
        $response = $this->request('GET', '/webhook_endpoints');
        return $response['data'] ?? [];
    }
    
    public function deleteWebhook(string $webhookId): bool
    {
        $this->request('DELETE', "/webhook_endpoints/{$webhookId}");
        return true;
    }
    
    // ==================== SALDO ====================
    
    public function getBalance(): BalanceResponse
    {
        $response = $this->request('GET', '/balance');

        $available = 0;
        $pending = 0;

        foreach ($response['available'] as $balance) {
            $available += $balance['amount'];
        }

        foreach ($response['pending'] as $balance) {
            $pending += $balance['amount'];
        }

        return new BalanceResponse(
            success: true,
            balance: $this->convertFromStripeAmount($available + $pending),
            availableBalance: $this->convertFromStripeAmount($available),
            pendingBalance: $this->convertFromStripeAmount($pending),
            currency: 'USD',
            rawResponse: $response
        );
    }
    
    public function getSettlementSchedule(array $filters = []): array
    {
        // Stripe usa payouts
        $response = $this->request('GET', '/payouts', $filters);
        return $response['data'] ?? [];
    }
    
    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        throw new GatewayException('Receivables anticipation not available - Stripe uses automatic daily/weekly payouts configured in Dashboard');
    }
}