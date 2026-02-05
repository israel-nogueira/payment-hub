<?php

namespace IsraelNogueira\PaymentHub\Gateways\PagSeguro;

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

class PagSeguroGateway implements PaymentGatewayInterface
{
    private const PRODUCTION_URL = 'https://api.pagseguro.com';
    private const SANDBOX_URL = 'https://sandbox.api.pagseguro.com';
    
    private string $token;
    private string $baseUrl;
    private bool $sandbox;

    public function __construct(string $token, bool $sandbox = false)
    {
        $this->token = $token;
        $this->sandbox = $sandbox;
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
            'Authorization: Bearer ' . $this->token,
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
            $errorMessage = $decoded['error_messages'][0]['description'] ?? 
                          $decoded['message'] ?? 
                          'Request failed';
            throw new GatewayException(
                $errorMessage,
                $httpCode,
                null,
                ['response' => $decoded]
            );
        }

        return $decoded ?? [];
    }

    private function mapPagSeguroStatus(string $status): PaymentStatus
    {
        $statusMap = [
            'AUTHORIZED' => PaymentStatus::APPROVED,
            'PAID' => PaymentStatus::APPROVED,
            'IN_ANALYSIS' => PaymentStatus::PROCESSING,
            'WAITING' => PaymentStatus::PENDING,
            'CANCELED' => PaymentStatus::CANCELLED,
            'DECLINED' => PaymentStatus::FAILED,
            'REFUNDED' => PaymentStatus::REFUNDED,
        ];

        return $statusMap[$status] ?? PaymentStatus::fromString($status);
    }

    private function mapSubscriptionCycle(string $interval): string
    {
        $cycleMap = [
            'daily' => 'DAY',
            'weekly' => 'WEEK',
            'monthly' => 'MONTH',
            'bimonthly' => 'BIMONTH',
            'quarterly' => 'TRIMONTH',
            'semiannually' => 'SEMIANNUAL',
            'yearly' => 'YEAR',
        ];

        return $cycleMap[strtolower($interval)] ?? 'MONTH';
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->documentNumber) {
            $data['tax_id'] = preg_replace('/\D/', '', $request->documentNumber);
        }

        if ($request->phone) {
            $phone = preg_replace('/\D/', '', $request->phone);
            $data['phones'] = [[
                'country' => '55',
                'area' => substr($phone, 0, 2),
                'number' => substr($phone, 2),
                'type' => 'MOBILE'
            ]];
        }

        if ($request->address) {
            $data['address'] = [
                'street' => $request->address['street'] ?? null,
                'number' => $request->address['number'] ?? null,
                'complement' => $request->address['complement'] ?? null,
                'locality' => $request->address['district'] ?? null,
                'city' => $request->address['city'] ?? null,
                'region_code' => $request->address['state'] ?? null,
                'country' => 'BRA',
                'postal_code' => preg_replace('/\D/', '', $request->address['zipcode'] ?? ''),
            ];
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
        return $response['customers'] ?? [];
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $data = [
            'reference_id' => 'REF_' . uniqid(),
            'customer' => [
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
            ],
            'items' => [[
                'reference_id' => 'ITEM_1',
                'name' => $request->description ?? 'Pagamento PIX',
                'quantity' => 1,
                'unit_amount' => (int)($request->money->amount() * 100), // Centavos
            ]],
            'qr_codes' => [[
                'amount' => [
                    'value' => (int)($request->money->amount() * 100),
                ],
            ]],
        ];

        if ($request->customerDocument) {
            $data['customer']['tax_id'] = $request->customerDocument->value();
        }

        if ($request->expiresInMinutes) {
            $expirationDate = date('Y-m-d\TH:i:s', strtotime("+{$request->expiresInMinutes} minutes"));
            $data['qr_codes'][0]['expiration_date'] = $expirationDate . '-03:00';
        }

        $response = $this->request('POST', '/orders', $data);

        $amount = $response['qr_codes'][0]['amount']['value'] / 100;
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapPagSeguroStatus($response['status'] ?? 'WAITING'),
            money: $money,
            message: 'PIX payment created successfully',
            rawResponse: $response,
            metadata: [
                'qr_code_id' => $response['qr_codes'][0]['id'] ?? null,
                'reference_id' => $response['reference_id'] ?? null,
            ]
        );
    }

    public function getPixQrCode(string $transactionId): string
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        
        foreach ($response['qr_codes'] ?? [] as $qrCode) {
            if (isset($qrCode['links'])) {
                foreach ($qrCode['links'] as $link) {
                    if ($link['media'] === 'image/png') {
                        return $link['href'];
                    }
                }
            }
        }
        
        return '';
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        return $response['qr_codes'][0]['text'] ?? '';
    }

    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $data = [
            'reference_id' => 'REF_' . uniqid(),
            'customer' => [
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
            ],
            'items' => [[
                'reference_id' => 'ITEM_1',
                'name' => $request->description ?? 'Pagamento com Cartão',
                'quantity' => 1,
                'unit_amount' => (int)($request->money->amount() * 100),
            ]],
            'charges' => [[
                'reference_id' => 'CHARGE_' . uniqid(),
                'description' => $request->description ?? 'Pagamento',
                'amount' => [
                    'value' => (int)($request->money->amount() * 100),
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'installments' => $request->installments ?? 1,
                    'capture' => true,
                    'card' => [
                        'number' => $request->cardNumber->value(),
                        'exp_month' => $request->cardExpiryMonth,
                        'exp_year' => $request->cardExpiryYear,
                        'security_code' => $request->cardCvv,
                        'holder' => [
                            'name' => $request->cardHolderName,
                        ],
                    ],
                ],
            ]],
        ];

        if ($request->customerDocument) {
            $data['customer']['tax_id'] = $request->customerDocument;
        }

        $response = $this->request('POST', '/orders', $data);

        $charge = $response['charges'][0] ?? [];
        $amount = ($charge['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapPagSeguroStatus($charge['status'] ?? 'WAITING'),
            money: $money,
            message: 'Credit card payment created successfully',
            rawResponse: $response,
            metadata: [
                'charge_id' => $charge['id'] ?? null,
                'card_brand' => $charge['payment_method']['card']['brand'] ?? null,
                'installments' => $request->installments ?? 1,
            ]
        );
    }

    public function tokenizeCard(array $cardData): string
    {
        $data = [
            'number' => $cardData['number'],
            'exp_month' => $cardData['exp_month'],
            'exp_year' => $cardData['exp_year'],
            'security_code' => $cardData['cvv'],
            'holder' => [
                'name' => $cardData['holder_name'],
            ],
        ];

        $response = $this->request('POST', '/tokens/cards', $data);
        return $response['id'] ?? '';
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        $charge = $response['charges'][0] ?? [];

        $data = [];
        if ($amount !== null) {
            $data['amount'] = ['value' => (int)($amount * 100)];
        }

        $captureResponse = $this->request('POST', "/charges/{$charge['id']}/capture", $data);

        $chargeAmount = ($captureResponse['amount']['value'] ?? 0) / 100;
        $money = Money::from($chargeAmount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $transactionId,
            status: $this->mapPagSeguroStatus($captureResponse['status'] ?? 'AUTHORIZED'),
            money: $money,
            message: 'Pre-authorization captured successfully',
            rawResponse: $captureResponse
        );
    }

    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        $charge = $response['charges'][0] ?? [];

        $cancelResponse = $this->request('POST', "/charges/{$charge['id']}/cancel");

        $amount = ($cancelResponse['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $transactionId,
            status: PaymentStatus::CANCELLED,
            money: $money,
            message: 'Pre-authorization canceled successfully',
            rawResponse: $cancelResponse
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        $data = [
            'reference_id' => 'REF_' . uniqid(),
            'customer' => [
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
            ],
            'items' => [[
                'reference_id' => 'ITEM_1',
                'name' => $request->description ?? 'Pagamento com Débito',
                'quantity' => 1,
                'unit_amount' => (int)($request->money->amount() * 100),
            ]],
            'charges' => [[
                'reference_id' => 'CHARGE_' . uniqid(),
                'description' => $request->description ?? 'Pagamento',
                'amount' => [
                    'value' => (int)($request->money->amount() * 100),
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'DEBIT_CARD',
                    'capture' => true,
                    'card' => [
                        'number' => $request->cardNumber->value(),
                        'exp_month' => $request->cardExpiryMonth,
                        'exp_year' => $request->cardExpiryYear,
                        'security_code' => $request->cardCvv,
                        'holder' => [
                            'name' => $request->cardHolderName,
                        ],
                    ],
                ],
            ]],
        ];

        if ($request->customerDocument) {
            $data['customer']['tax_id'] = $request->customerDocument;
        }

        $response = $this->request('POST', '/orders', $data);

        $charge = $response['charges'][0] ?? [];
        $amount = ($charge['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapPagSeguroStatus($charge['status'] ?? 'WAITING'),
            money: $money,
            message: 'Debit card payment created successfully',
            rawResponse: $response
        );
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $data = [
            'reference_id' => 'REF_' . uniqid(),
            'customer' => [
                'name' => $request->customerName,
                'email' => $request->customerEmail?->value() ?? 'cliente@example.com',
                'tax_id' => $request->customerDocument?->value() ?? '',
            ],
            'items' => [[
                'reference_id' => 'ITEM_1',
                'name' => $request->description ?? 'Pagamento Boleto',
                'quantity' => 1,
                'unit_amount' => (int)($request->money->amount() * 100),
            ]],
            'charges' => [[
                'reference_id' => 'CHARGE_' . uniqid(),
                'description' => $request->description ?? 'Boleto',
                'amount' => [
                    'value' => (int)($request->money->amount() * 100),
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'BOLETO',
                    'boleto' => [
                        'due_date' => $request->dueDate ?? date('Y-m-d', strtotime('+3 days')),
                        'instruction_lines' => [
                            'line_1' => $request->instructions ?? 'Pagamento via boleto bancário',
                        ],
                    ],
                ],
            ]],
        ];

        $response = $this->request('POST', '/orders', $data);

        $charge = $response['charges'][0] ?? [];
        $amount = ($charge['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapPagSeguroStatus($charge['status'] ?? 'WAITING'),
            money: $money,
            message: 'Boleto created successfully',
            rawResponse: $response,
            metadata: [
                'charge_id' => $charge['id'] ?? null,
                'barcode' => $charge['payment_method']['boleto']['barcode'] ?? null,
            ]
        );
    }

    public function getBoletoUrl(string $transactionId): string
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        
        $charge = $response['charges'][0] ?? [];
        foreach ($charge['links'] ?? [] as $link) {
            if ($link['rel'] === 'BOLETO') {
                return $link['href'];
            }
        }
        
        return '';
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        $charge = $response['charges'][0] ?? [];

        $cancelResponse = $this->request('POST', "/charges/{$charge['id']}/cancel");

        $amount = ($cancelResponse['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $transactionId,
            status: PaymentStatus::CANCELLED,
            money: $money,
            message: 'Boleto canceled successfully',
            rawResponse: $cancelResponse
        );
    }

    // ==================== ASSINATURAS/RECORRÊNCIA ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $data = [
            'reference_id' => 'SUB_' . uniqid(),
            'customer' => [
                'name' => $request->customerId ?? 'Cliente',
                'email' => $request->customerId ?? 'cliente@example.com',
            ],
            'plan' => [
                'name' => $request->description ?? 'Assinatura',
                'amount' => [
                    'value' => (int)($request->money->amount() * 100),
                    'currency' => 'BRL',
                ],
                'interval' => [
                    'unit' => $this->mapSubscriptionCycle($request->interval->value),
                    'length' => 1,
                ],
            ],
        ];

        if ($request->trialDays && $request->trialDays > 0) {
            $data['plan']['trial'] = [
                'enabled' => true,
                'days' => $request->trialDays,
            ];
        }

        $response = $this->request('POST', '/subscriptions', $data);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'] ?? 'ACTIVE',
            message: 'Subscription created successfully',
            rawResponse: $response
        );
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/subscriptions/{$subscriptionId}/cancel");

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'CANCELED',
            message: 'Subscription canceled successfully',
            rawResponse: $response
        );
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/subscriptions/{$subscriptionId}/suspend");

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'SUSPENDED',
            message: 'Subscription suspended successfully',
            rawResponse: $response
        );
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/subscriptions/{$subscriptionId}/activate");

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'ACTIVE',
            message: 'Subscription reactivated successfully',
            rawResponse: $response
        );
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        $response = $this->request('PUT', "/subscriptions/{$subscriptionId}", $data);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'] ?? 'ACTIVE',
            message: 'Subscription updated successfully',
            rawResponse: $response
        );
    }

    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        
        $charge = $response['charges'][0] ?? [];
        $amount = ($charge['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new TransactionStatusResponse(
            success: true,
            transactionId: $transactionId,
            status: $this->mapPagSeguroStatus($charge['status'] ?? 'WAITING'),
            money: $money,
            rawResponse: $response
        );
    }

    public function listTransactions(array $filters = []): array
    {
        $response = $this->request('GET', '/orders', [], $filters);
        return $response['orders'] ?? [];
    }

    // ==================== ESTORNOS E CHARGEBACKS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $response = $this->request('GET', "/orders/{$request->transactionId}");
        $charge = $response['charges'][0] ?? [];

        $refundData = [
            'amount' => [
                'value' => (int)(($charge['amount']['value'] ?? 0)),
            ],
        ];

        $refundResponse = $this->request('POST', "/charges/{$charge['id']}/cancel", $refundData);

        $amount = ($refundResponse['amount']['value'] ?? 0) / 100;
        $money = Money::from($amount, Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: $refundResponse['id'] ?? uniqid('refund_'),
            transactionId: $request->transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Refund processed successfully',
            rawResponse: $refundResponse
        );
    }

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $response = $this->request('GET', "/orders/{$transactionId}");
        $charge = $response['charges'][0] ?? [];

        $refundData = [
            'amount' => [
                'value' => (int)($amount * 100),
            ],
        ];

        $refundResponse = $this->request('POST', "/charges/{$charge['id']}/cancel", $refundData);

        $refundAmount = ($refundResponse['amount']['value'] ?? 0) / 100;
        $money = Money::from($refundAmount, Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: $refundResponse['id'] ?? uniqid('refund_'),
            transactionId: $transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Partial refund processed successfully',
            rawResponse: $refundResponse
        );
    }

    public function getChargebacks(array $filters = []): array
    {
        $response = $this->request('GET', '/disputes', [], $filters);
        return $response['disputes'] ?? [];
    }

    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        $response = $this->request('POST', "/disputes/{$chargebackId}/contest", $evidence);

        return new PaymentResponse(
            success: true,
            transactionId: $chargebackId,
            status: PaymentStatus::PROCESSING,
            money: Money::from(0, Currency::BRL),
            message: 'Chargeback disputed successfully',
            rawResponse: $response
        );
    }

    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Split payment not directly supported by PagSeguro API. Use sub-accounts instead.');
    }

    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts feature requires PagBank for Business. Contact PagSeguro sales.');
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts feature requires PagBank for Business.');
    }

    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts feature requires PagBank for Business.');
    }

    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts feature requires PagBank for Business.');
    }

    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts feature requires PagBank for Business.');
    }

    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('Wallets not supported by PagSeguro standard API.');
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not supported by PagSeguro standard API.');
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not supported by PagSeguro standard API.');
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Wallets not supported by PagSeguro standard API.');
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Wallets not supported by PagSeguro standard API.');
    }

    // ==================== ESCROW (CUSTÓDIA) ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported. Use payment holds with delayed capture.');
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported.');
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported.');
    }

    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Escrow not directly supported.');
    }

    // ==================== TRANSFERÊNCIAS E SAQUES ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        throw new GatewayException('Transfers managed through PagSeguro dashboard, not available via API.');
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        throw new GatewayException('Scheduled transfers not available via API.');
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        throw new GatewayException('Transfer cancellation not available via API.');
    }

    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $data = [
            'reference_id' => 'LINK_' . uniqid(),
            'description' => $request->description ?? 'Link de Pagamento',
            'amount' => [
                'value' => (int)($request->money->amount() * 100),
                'currency' => 'BRL',
            ],
        ];

        if ($request->expiresAt) {
            $data['expiration_date'] = $request->expiresAt;
        }

        $response = $this->request('POST', '/links', $data);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['links'][0]['href'] ?? '',
            status: $response['status'] ?? 'ACTIVE',
            message: 'Payment link created successfully',
            rawResponse: $response
        );
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/links/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['links'][0]['href'] ?? '',
            status: $response['status'] ?? 'ACTIVE',
            message: 'Payment link retrieved successfully',
            rawResponse: $response
        );
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('POST', "/links/{$linkId}/inactivate");

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: '',
            status: 'EXPIRED',
            message: 'Payment link expired successfully',
            rawResponse: $response
        );
    }

    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        throw new GatewayException('Fraud analysis is automatic in PagSeguro. Check transaction details for fraud score.');
    }

    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management not available via API. Use PagSeguro dashboard.');
    }

    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management not available via API.');
    }

    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        $data = [
            'url' => $url,
            'events' => $events,
        ];

        $response = $this->request('POST', '/webhooks', $data);
        return $response;
    }

    public function listWebhooks(): array
    {
        $response = $this->request('GET', '/webhooks');
        return $response['webhooks'] ?? [];
    }

    public function deleteWebhook(string $webhookId): bool
    {
        $this->request('DELETE', "/webhooks/{$webhookId}");
        return true;
    }

    // ==================== SALDO E CONCILIAÇÃO ====================
    
    public function getBalance(): BalanceResponse
    {
        $response = $this->request('GET', '/balances');

        $available = ($response['available']['value'] ?? 0) / 100;
        $pending = ($response['waiting_funds']['value'] ?? 0) / 100;
        $total = $available + $pending;

        return new BalanceResponse(
            success: true,
            balance: $total,
            availableBalance: $available,
            pendingBalance: $pending,
            currency: 'BRL',
            rawResponse: $response
        );
    }

    public function getSettlementSchedule(array $filters = []): array
    {
        $response = $this->request('GET', '/transactions', [], $filters);
        return $response['transactions'] ?? [];
    }

    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        throw new GatewayException('Receivables anticipation managed through PagSeguro dashboard.');
    }
}