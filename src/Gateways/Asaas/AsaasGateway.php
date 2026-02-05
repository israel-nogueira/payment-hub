<?php

namespace IsraelNogueira\PaymentHub\Gateways\Asaas;

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

class AsaasGateway implements PaymentGatewayInterface
{
    private const PRODUCTION_URL = 'https://api.asaas.com/v3';
    private const SANDBOX_URL = 'https://api-sandbox.asaas.com/v3';
    
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey, bool $sandbox = false)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    // ==================== MÉTODOS PRIVADOS ====================
    
    private function request(string $method, string $endpoint, array $data = [], array $queryParams = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new GatewayException('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decoded['errors'][0]['description'] ?? $decoded['message'] ?? 'Request failed';
            throw new GatewayException(
                $errorMessage,
                $httpCode,
                null,
                ['response' => $decoded]
            );
        }

        return $decoded ?? [];
    }

    private function mapAsaasStatus(string $asaasStatus): PaymentStatus
    {
        $statusMap = [
            'PENDING' => PaymentStatus::PENDING,
            'RECEIVED' => PaymentStatus::APPROVED,
            'CONFIRMED' => PaymentStatus::APPROVED,
            'OVERDUE' => PaymentStatus::PENDING, // Vencido mas ainda pendente
            'REFUNDED' => PaymentStatus::REFUNDED,
            'RECEIVED_IN_CASH' => PaymentStatus::APPROVED,
            'REFUND_REQUESTED' => PaymentStatus::PROCESSING,
            'CHARGEBACK_REQUESTED' => PaymentStatus::FAILED,
            'CHARGEBACK_DISPUTE' => PaymentStatus::PROCESSING,
            'AWAITING_CHARGEBACK_REVERSAL' => PaymentStatus::PROCESSING,
            'DUNNING_REQUESTED' => PaymentStatus::PROCESSING,
            'DUNNING_RECEIVED' => PaymentStatus::APPROVED,
            'AWAITING_RISK_ANALYSIS' => PaymentStatus::PROCESSING,
        ];

        return $statusMap[$asaasStatus] ?? PaymentStatus::fromString($asaasStatus);
    }

    private function mapSubscriptionCycle(string $interval): string
    {
        $cycleMap = [
            'daily' => 'DAILY',
            'weekly' => 'WEEKLY',
            'biweekly' => 'BIWEEKLY',
            'monthly' => 'MONTHLY',
            'bimonthly' => 'BIMONTHLY',
            'quarterly' => 'QUARTERLY',
            'semiannually' => 'SEMIANNUALLY',
            'yearly' => 'YEARLY',
        ];

        return $cycleMap[strtolower($interval)] ?? 'MONTHLY';
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->documentNumber) {
            $data['cpfCnpj'] = preg_replace('/\D/', '', $request->documentNumber);
        }

        if ($request->phone) {
            $data['mobilePhone'] = preg_replace('/\D/', '', $request->phone);
        }

        if ($request->address) {
            $data['address'] = $request->address['street'] ?? null;
            $data['addressNumber'] = $request->address['number'] ?? null;
            $data['complement'] = $request->address['complement'] ?? null;
            $data['province'] = $request->address['district'] ?? null;
            $data['postalCode'] = preg_replace('/\D/', '', $request->address['zipcode'] ?? '');
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
        $response = $this->request('PUT', "/customers/{$customerId}", $data);

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
        $response = $this->request('GET', '/customers', [], $filters);
        return $response['data'] ?? [];
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        // Primeiro, criar/buscar cliente
        $customerData = [
            'name' => $request->customerName ?? 'Cliente',
            'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
        ];

        if ($request->customerDocument) {
            $customerData['cpfCnpj'] = $request->customerDocument->value();
        }

        $customerResponse = $this->request('POST', '/customers', $customerData);
        $customerId = $customerResponse['id'];

        // Criar cobrança PIX
        $paymentData = [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => $request->money->amount(),
            'dueDate' => date('Y-m-d'),
        ];

        if ($request->description) {
            $paymentData['description'] = $request->description;
        }

        $response = $this->request('POST', '/payments', $paymentData);

        $amount = $response['value'] ?? $request->money->amount();
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapAsaasStatus($response['status'] ?? 'PENDING'),
            money: $money,
            message: 'PIX payment created successfully',
            rawResponse: $response,
            metadata: [
                'customer_id' => $customerId,
                'invoice_url' => $response['invoiceUrl'] ?? null,
            ]
        );
    }

    public function getPixQrCode(string $transactionId): string
    {
        $response = $this->request('GET', "/payments/{$transactionId}/pixQrCode");
        return $response['encodedImage'] ?? '';
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        $response = $this->request('GET', "/payments/{$transactionId}/pixQrCode");
        return $response['payload'] ?? '';
    }

    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        // Criar/buscar cliente
        $customerData = [
            'name' => $request->customerName ?? 'Cliente',
            'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
        ];

        if ($request->customerDocument) {
            $customerData['cpfCnpj'] = $request->customerDocument->value();
        }

        $customerResponse = $this->request('POST', '/customers', $customerData);
        $customerId = $customerResponse['id'];

        // Criar cobrança
        $paymentData = [
            'customer' => $customerId,
            'billingType' => 'CREDIT_CARD',
            'value' => $request->money->amount(),
            'dueDate' => date('Y-m-d'),
        ];

        if ($request->description) {
            $paymentData['description'] = $request->description;
        }

        // Parcelamento
        if ($request->installments > 1) {
            $paymentData['installmentCount'] = $request->installments;
            $paymentData['installmentValue'] = round($request->money->amount() / $request->installments, 2);
        }

        // Dados do cartão
        if ($request->cardToken) {
            $paymentData['creditCardToken'] = $request->cardToken;
        } else {
            $paymentData['creditCard'] = [
                'holderName' => $request->cardHolderName,
                'number' => $request->cardNumber->value(),
                'expiryMonth' => $request->cardExpiryMonth,
                'expiryYear' => $request->cardExpiryYear,
                'ccv' => $request->cardCvv,
            ];

            $paymentData['creditCardHolderInfo'] = [
                'name' => $request->customerName ?? $request->cardHolderName,
                'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
                'cpfCnpj' => ($request->customerDocument?->value() ?? '00000000000'),
                'postalCode' => preg_replace('/\D/', '', $request->billingAddress['zipcode'] ?? '00000000'),
                'addressNumber' => $request->billingAddress['number'] ?? 'S/N',
                'phone' => preg_replace('/\D/', '', $request->billingAddress['phone'] ?? '0000000000'),
            ];
        }

        $paymentData['remoteIp'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $response = $this->request('POST', '/payments', $paymentData);

        $amount = $response['value'] ?? $request->money->amount();
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapAsaasStatus($response['status'] ?? 'PENDING'),
            money: $money,
            message: 'Credit card payment created successfully',
            rawResponse: $response,
            metadata: [
                'customer_id' => $customerId,
                'credit_card_token' => $response['creditCardToken'] ?? null,
            ]
        );
    }

    public function tokenizeCard(array $cardData): string
    {
        $data = [
            'customer' => $cardData['customer_id'],
            'creditCard' => [
                'holderName' => $cardData['holder_name'],
                'number' => $cardData['number'],
                'expiryMonth' => $cardData['expiry_month'],
                'expiryYear' => $cardData['expiry_year'],
                'ccv' => $cardData['cvv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $cardData['holder_name'],
                'email' => $cardData['email'],
                'cpfCnpj' => $cardData['document'],
                'postalCode' => $cardData['postal_code'],
                'addressNumber' => $cardData['address_number'],
                'phone' => $cardData['phone'],
            ],
            'remoteIp' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];

        $response = $this->request('POST', '/creditCard/tokenize', $data);
        return $response['creditCardToken'];
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        throw new GatewayException('Pre-authorization capture not directly supported by Asaas');
    }

    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $response = $this->request('DELETE', "/payments/{$transactionId}");

        return new PaymentResponse(
            success: true,
            transactionId: $transactionId,
            status: PaymentStatus::CANCELLED,
            money: null,
            message: 'Pre-authorization cancelled successfully',
            rawResponse: $response
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Debit card payments are processed through invoiceUrl in Asaas');
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        // Criar/buscar cliente
        $customerData = [
            'name' => $request->customerName ?? 'Cliente',
            'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
        ];

        if ($request->customerDocument) {
            $customerData['cpfCnpj'] = $request->customerDocument->value();
        }

        if ($request->customerAddress) {
            $customerData['address'] = $request->customerAddress['street'] ?? null;
            $customerData['addressNumber'] = $request->customerAddress['number'] ?? null;
            $customerData['province'] = $request->customerAddress['district'] ?? null;
            $customerData['postalCode'] = preg_replace('/\D/', '', $request->customerAddress['zipcode'] ?? '');
        }

        $customerResponse = $this->request('POST', '/customers', $customerData);
        $customerId = $customerResponse['id'];

        // Criar cobrança
        $paymentData = [
            'customer' => $customerId,
            'billingType' => 'BOLETO',
            'value' => $request->money->amount(),
            'dueDate' => $request->dueDate ?? date('Y-m-d', strtotime('+3 days')),
        ];

        if ($request->description) {
            $paymentData['description'] = $request->description;
        }

        // Juros e multa
        if ($request->hasFine()) {
            $fineValue = $request->finePercentage ?? 
                ($request->fineAmount ? ($request->fineAmount->amount() / $request->money->amount() * 100) : 0);
            $paymentData['fine'] = [
                'value' => $fineValue,
            ];
        }

        if ($request->hasInterest()) {
            $paymentData['interest'] = [
                'value' => $request->interestPercentage ?? 1,
            ];
        }

        // Desconto
        if ($request->hasDiscount()) {
            $paymentData['discount'] = [
                'value' => $request->discountAmount->amount(),
                'dueDateLimitDays' => 0,
            ];
        }

        $response = $this->request('POST', '/payments', $paymentData);

        $amount = $response['value'] ?? $request->money->amount();
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapAsaasStatus($response['status'] ?? 'PENDING'),
            money: $money,
            message: 'Boleto created successfully',
            rawResponse: $response,
            metadata: [
                'customer_id' => $customerId,
                'invoice_url' => $response['invoiceUrl'] ?? null,
                'bank_slip_url' => $response['bankSlipUrl'] ?? null,
            ]
        );
    }

    public function getBoletoUrl(string $transactionId): string
    {
        $response = $this->request('GET', "/payments/{$transactionId}");
        return $response['bankSlipUrl'] ?? $response['invoiceUrl'] ?? '';
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        $response = $this->request('DELETE', "/payments/{$transactionId}");

        return new PaymentResponse(
            success: true,
            transactionId: $transactionId,
            status: PaymentStatus::CANCELLED,
            money: null,
            message: 'Boleto cancelled successfully',
            rawResponse: $response
        );
    }

    // ==================== ASSINATURAS ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $data = [
            'customer' => $request->customerId,
            'billingType' => strtoupper($request->paymentMethod ?? 'BOLETO'),
            'value' => $request->money->amount(),
            'nextDueDate' => $request->startDate ?? date('Y-m-d', strtotime('+30 days')),
            'cycle' => $this->mapSubscriptionCycle($request->interval->value),
        ];

        if ($request->description) {
            $data['description'] = $request->description;
        }

        if ($request->cardToken) {
            $data['creditCardToken'] = $request->cardToken;
        }

        $response = $this->request('POST', '/subscriptions', $data);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'active',
            message: 'Subscription created successfully',
            rawResponse: $response
        );
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('DELETE', "/subscriptions/{$subscriptionId}");

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'cancelled',
            message: 'Subscription cancelled successfully',
            rawResponse: $response
        );
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Subscription suspension not directly supported - use cancelSubscription instead');
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Subscription reactivation not supported by Asaas - create new subscription');
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        $response = $this->request('PUT', "/subscriptions/{$subscriptionId}", $data);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'active',
            message: 'Subscription updated successfully',
            rawResponse: $response
        );
    }

    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $response = $this->request('GET', "/payments/{$transactionId}");

        $money = null;
        if (isset($response['value'])) {
            $money = Money::from($response['value'], Currency::BRL);
        }

        return new TransactionStatusResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapAsaasStatus($response['status'] ?? 'PENDING'),
            money: $money,
            rawResponse: $response
        );
    }

    public function listTransactions(array $filters = []): array
    {
        $response = $this->request('GET', '/payments', [], $filters);
        return $response['data'] ?? [];
    }

    // ==================== ESTORNOS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [];
        
        if ($request->money !== null) {
            $data['value'] = $request->money->amount();
        }
        
        if ($request->reason) {
            $data['description'] = $request->reason;
        }

        $response = $this->request('POST', "/payments/{$request->transactionId}/refund", $data);

        $amount = $request->money?->amount() ?? ($response['value'] ?? 0.0);
        $money = Money::from($amount, Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: $response['id'] ?? $request->transactionId,
            transactionId: $request->transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Refund processed successfully',
            rawResponse: $response
        );
    }

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $money = Money::from($amount, Currency::BRL);
        return $this->refund(new RefundRequest($transactionId, $money, 'Partial refund'));
    }

    public function getChargebacks(array $filters = []): array
    {
        // Asaas não tem endpoint específico para listar chargebacks
        // Chargebacks aparecem no status das cobranças
        $payments = $this->listTransactions($filters);
        
        return array_filter($payments, function($payment) {
            return in_array($payment['status'], ['CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE', 'AWAITING_CHARGEBACK_REVERSAL']);
        });
    }

    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        throw new GatewayException('Chargeback disputes must be handled through Asaas dashboard');
    }

    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        // Split é configurado na criação da cobrança
        throw new GatewayException('Split payments must be configured during payment creation with split array');
    }

    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'cpfCnpj' => preg_replace('/\D/', '', $request->document),
            'companyType' => $request->metadata['company_type'] ?? 'MEI',
            'phone' => preg_replace('/\D/', '', $request->phone ?? ''),
            'mobilePhone' => preg_replace('/\D/', '', $request->mobile_phone ?? ''),
            'address' => $request->address['street'] ?? '',
            'addressNumber' => $request->address['number'] ?? '',
            'province' => $request->address['district'] ?? '',
            'postalCode' => preg_replace('/\D/', '', $request->address['zipcode'] ?? ''),
        ];

        $response = $this->request('POST', '/accounts', $data);

        return new SubAccountResponse(
            success: true,
            subAccountId: $response['id'],
            status: 'pending_approval',
            message: 'Sub-account created successfully',
            rawResponse: $response
        );
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        throw new GatewayException('Sub-account update must be done through Asaas dashboard');
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
        throw new GatewayException('Sub-account activation is handled automatically by Asaas');
    }

    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account deactivation must be done through Asaas dashboard');
    }

    // ==================== WALLETS (NÃO SUPORTADO) ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('Wallets not supported by Asaas - use accounts instead');
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not supported by Asaas');
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not supported by Asaas');
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Wallets not supported by Asaas - use getBalance() instead');
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Wallets not supported by Asaas - use transfer() instead');
    }

    // ==================== ESCROW ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        // Asaas tem Conta Escrow mas funciona diferente
        throw new GatewayException('Escrow must be configured per sub-account in Asaas');
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        $response = $this->request('POST', "/payments/{$escrowId}/escrowRelease");

        return new EscrowResponse(
            success: true,
            escrowId: $escrowId,
            amount: null,
            status: 'released',
            message: 'Escrow released successfully',
            rawResponse: $response
        );
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Partial escrow release not supported by Asaas');
    }

    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Escrow cancellation not supported by Asaas');
    }

    // ==================== TRANSFERÊNCIAS ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        $data = [
            'value' => $request->money->amount(),
        ];

        // Transferência por PIX
        if (isset($request->metadata['pix_key'])) {
            $data['pixAddressKey'] = $request->metadata['pix_key'];
            $endpoint = '/transfers';
        } else {
            // Transferência bancária
            $data['bankAccount'] = [
                'bank' => [
                    'code' => $request->metadata['bank_code'],
                ],
                'accountName' => $request->metadata['account_name'],
                'ownerName' => $request->metadata['owner_name'],
                'ownerBirthDate' => $request->metadata['owner_birth_date'],
                'cpfCnpj' => $request->metadata['document'],
                'agency' => $request->metadata['agency'],
                'account' => $request->metadata['account'],
                'accountDigit' => $request->metadata['account_digit'],
            ];
            $endpoint = '/transfers';
        }

        if ($request->description) {
            $data['description'] = $request->description;
        }

        $response = $this->request('POST', $endpoint, $data);

        $amount = $response['value'] ?? $request->money->amount();
        $money = Money::from($amount, Currency::BRL);

        return new TransferResponse(
            success: true,
            transferId: $response['id'],
            money: $money,
            status: $this->mapAsaasStatus($response['status'] ?? 'PENDING'),
            message: 'Transfer created successfully',
            rawResponse: $response
        );
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        $data = [
            'value' => $request->money->amount(),
            'scheduleDate' => $date,
        ];

        if (isset($request->metadata['pix_key'])) {
            $data['pixAddressKey'] = $request->metadata['pix_key'];
        }

        $response = $this->request('POST', '/transfers', $data);

        $money = Money::from($response['value'], Currency::BRL);

        return new TransferResponse(
            success: true,
            transferId: $response['id'],
            money: $money,
            status: PaymentStatus::PENDING,
            message: 'Transfer scheduled successfully',
            rawResponse: $response
        );
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        $response = $this->request('DELETE', "/transfers/{$transferId}");

        return new TransferResponse(
            success: true,
            transferId: $transferId,
            money: null,
            status: PaymentStatus::CANCELLED,
            message: 'Transfer cancelled successfully',
            rawResponse: $response
        );
    }

    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $data = [
            'name' => $request->description ?? 'Payment Link',
            'description' => $request->description,
            'value' => $request->amount,
            'billingType' => 'UNDEFINED',
            'chargeType' => 'DETACHED',
        ];

        if (isset($request->metadata['due_date_limit_days'])) {
            $data['dueDateLimitDays'] = $request->metadata['due_date_limit_days'];
        }

        if (isset($request->metadata['max_installments'])) {
            $data['maxInstallmentCount'] = $request->metadata['max_installments'];
        }

        $response = $this->request('POST', '/paymentLinks', $data);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['url'],
            status: 'active',
            message: 'Payment link created successfully',
            rawResponse: $response
        );
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/paymentLinks/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['url'],
            status: $response['deleted'] ? 'inactive' : 'active',
            message: 'Payment link retrieved successfully',
            rawResponse: $response
        );
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('DELETE', "/paymentLinks/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: null,
            status: 'expired',
            message: 'Payment link expired successfully',
            rawResponse: $response
        );
    }

    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        throw new GatewayException('Anti-fraud analysis is automatic in Asaas');
    }

    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management not available via API');
    }

    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management not available via API');
    }

    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        $data = [
            'name' => 'Webhook - ' . date('Y-m-d H:i:s'),
            'url' => $url,
            'email' => 'dev@example.com',
            'enabled' => true,
            'interrupted' => false,
            'authToken' => bin2hex(random_bytes(32)),
        ];

        // Asaas não permite selecionar eventos específicos por webhook
        // Todos os eventos são enviados para todos os webhooks

        $response = $this->request('POST', '/webhooks', $data);

        return [
            'webhook_id' => $response['id'],
            'url' => $url,
            'events' => ['all'],
            'auth_token' => $data['authToken'],
        ];
    }

    public function listWebhooks(): array
    {
        $response = $this->request('GET', '/webhooks');
        return $response['data'] ?? [];
    }

    public function deleteWebhook(string $webhookId): bool
    {
        $this->request('DELETE', "/webhooks/{$webhookId}");
        return true;
    }

    // ==================== SALDO ====================
    
    public function getBalance(): BalanceResponse
    {
        $response = $this->request('GET', '/finance/balance');

        return new BalanceResponse(
            success: true,
            balance: $response['balance'] ?? 0,
            availableBalance: $response['balance'] ?? 0,
            pendingBalance: 0.0,
            currency: 'BRL',
            rawResponse: $response
        );
    }

    public function getSettlementSchedule(array $filters = []): array
    {
        throw new GatewayException('Settlement schedule not available via API - check dashboard');
    }

    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        $data = [
            'payment' => $transactionIds[0] ?? null,
        ];

        $response = $this->request('POST', '/anticipations', $data);

        $money = null;
        if (isset($response['value'])) {
            $money = Money::from($response['value'], Currency::BRL);
        }

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: PaymentStatus::PROCESSING,
            money: $money,
            message: 'Anticipation requested successfully',
            rawResponse: $response
        );
    }
}