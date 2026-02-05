<?php

namespace IsraelNogueira\PaymentHub\Gateways\PayPal;

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
 * PayPal Gateway - Global Payment Processing
 * 
 * Supports:
 * - PayPal Checkout (redirect flow)
 * - Credit Cards (via Braintree/Advanced)
 * - PayPal Balance (wallet payments)
 * - Subscriptions (recurring billing)
 * - Refunds (full/partial)
 * - Payouts (mass payments)
 * - Invoicing
 * - Payment Links
 * 
 * Does NOT support (directly):
 * - PIX (Brazil only - use local gateways)
 * - Boleto (Brazil only - use local gateways)
 * - Direct debit cards (use credit card flow)
 * 
 * Documentation: https://developer.paypal.com/docs/api/overview/
 */
class PayPalGateway implements PaymentGatewayInterface
{
    private const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    private const PRODUCTION_URL = 'https://api-m.paypal.com';
    
    private string $clientId;
    private string $clientSecret;
    private bool $testMode;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function __construct(string $clientId, string $clientSecret, bool $testMode = false)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->testMode = $testMode;
    }

    // ==================== MÉTODOS PRIVADOS ====================
    
    private function getApiUrl(): string
    {
        return $this->testMode ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    private function getAccessToken(): string
    {
        // Reutilizar token se ainda válido
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $url = $this->getApiUrl() . '/v1/oauth2/token';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new GatewayException('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400 || !isset($decoded['access_token'])) {
            throw new GatewayException('Failed to obtain PayPal access token', $httpCode);
        }

        $this->accessToken = $decoded['access_token'];
        $this->tokenExpiry = time() + ($decoded['expires_in'] ?? 3600) - 60; // 60s margem

        return $this->accessToken;
    }
    
    private function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->getApiUrl() . $endpoint;
        $token = $this->getAccessToken();
        
        $defaultHeaders = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        $headers = array_merge($defaultHeaders, $headers);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
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
            $errorMessage = $decoded['message'] ?? $decoded['error_description'] ?? 'Request failed';
            $errorName = $decoded['name'] ?? $decoded['error'] ?? 'unknown_error';
            
            throw new GatewayException(
                "PayPal {$errorName}: {$errorMessage}",
                $httpCode,
                null,
                [
                    'name' => $errorName,
                    'message' => $errorMessage,
                    'details' => $decoded['details'] ?? null,
                    'response' => $decoded
                ]
            );
        }

        return $decoded ?? [];
    }

    private function mapPayPalStatus(string $paypalStatus): PaymentStatus
    {
        $statusMap = [
            // Order/Payment statuses
            'CREATED' => PaymentStatus::PENDING,
            'SAVED' => PaymentStatus::PENDING,
            'APPROVED' => PaymentStatus::APPROVED,
            'VOIDED' => PaymentStatus::CANCELLED,
            'COMPLETED' => PaymentStatus::APPROVED,
            'PAYER_ACTION_REQUIRED' => PaymentStatus::PENDING,
            
            // Capture statuses
            'PENDING' => PaymentStatus::PENDING,
            'DECLINED' => PaymentStatus::FAILED,
            'FAILED' => PaymentStatus::FAILED,
            
            // Subscription statuses
            'ACTIVE' => PaymentStatus::APPROVED,
            'SUSPENDED' => PaymentStatus::CANCELLED,
            'CANCELLED' => PaymentStatus::CANCELLED,
            'EXPIRED' => PaymentStatus::CANCELLED,
            
            // Refund statuses
            'REFUNDED' => PaymentStatus::REFUNDED,
            'PARTIALLY_REFUNDED' => PaymentStatus::REFUNDED,
        ];

        return $statusMap[$paypalStatus] ?? PaymentStatus::fromString(strtolower($paypalStatus));
    }

    private function getCurrencyCode(Currency $currency): string
    {
        return $currency->value;
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        // PayPal não tem API de "customers" tradicional
        // Usa o conceito de "payers" que são criados dinamicamente
        throw new GatewayException('PayPal does not have a dedicated Customer API - customers are managed through orders/subscriptions');
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        throw new GatewayException('PayPal customer management not supported via API');
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        throw new GatewayException('PayPal customer management not supported via API');
    }

    public function listCustomers(array $filters = []): array
    {
        throw new GatewayException('PayPal customer management not supported via API');
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('PIX is not supported by PayPal - available only in Brazilian gateways (Mercado Pago, Asaas, PagSeguro)');
    }
    
    public function getPixQrCode(string $transactionId): string
    {
        throw new GatewayException('PIX is not supported by PayPal');
    }
    
    public function getPixCopyPaste(string $transactionId): string
    {
        throw new GatewayException('PIX is not supported by PayPal');
    }
    
    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        // PayPal aceita cartões via Orders API ou Advanced Credit/Debit Card Payments
        $data = [
            'intent' => $request->capture ? 'CAPTURE' : 'AUTHORIZE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $this->getCurrencyCode($request->money->currency()),
                        'value' => number_format($request->money->amount(), 2, '.', ''),
                    ],
                    'description' => $request->description ?? 'Payment',
                ]
            ],
            'payment_source' => [
                'card' => [
                    'number' => $request->cardNumber->value(),
                    'expiry' => $request->cardExpiryYear . '-' . str_pad($request->cardExpiryMonth, 2, '0', STR_PAD_LEFT),
                    'security_code' => $request->cardCvv,
                    'name' => $request->cardHolderName ?? $request->customerName ?? 'Card Holder',
                ]
            ]
        ];

        if ($request->billingAddress) {
            $data['payment_source']['card']['billing_address'] = [
                'address_line_1' => $request->billingAddress['street'] ?? '',
                'admin_area_2' => $request->billingAddress['city'] ?? '',
                'admin_area_1' => $request->billingAddress['state'] ?? '',
                'postal_code' => $request->billingAddress['zipcode'] ?? '',
                'country_code' => $request->billingAddress['country'] ?? 'US',
            ];
        }

        $response = $this->request('POST', '/v2/checkout/orders', $data);

        // Se CAPTURE, fazer captura automática
        if ($request->capture && $response['status'] === 'CREATED') {
            $captureResponse = $this->request('POST', "/v2/checkout/orders/{$response['id']}/capture");
            $response = $captureResponse;
        }

        $money = Money::from(
            $response['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? $request->money->amount(),
            Currency::fromString($response['purchase_units'][0]['amount']['currency_code'] ?? 'USD')
        );

        return new PaymentResponse(
            success: $response['status'] === 'COMPLETED',
            transactionId: $response['id'],
            status: $this->mapPayPalStatus($response['status']),
            money: $money,
            message: $response['status'] === 'COMPLETED' ? 'Payment completed' : 'Payment ' . strtolower($response['status']),
            rawResponse: $response,
            metadata: [
                'order_id' => $response['id'],
                'capture_id' => $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
                'payer_email' => $response['payer']['email_address'] ?? null,
            ]
        );
    }
    
    public function tokenizeCard(array $cardData): string
    {
        // PayPal usa Vaulting para salvar cartões
        $data = [
            'payment_source' => [
                'card' => [
                    'number' => $cardData['number'],
                    'expiry' => $cardData['expiryYear'] . '-' . str_pad($cardData['expiryMonth'], 2, '0', STR_PAD_LEFT),
                    'security_code' => $cardData['cvv'],
                    'name' => $cardData['holderName'] ?? 'Card Holder',
                ]
            ]
        ];

        $response = $this->request('POST', '/v3/vault/payment-tokens', $data);
        
        return $response['id'];
    }
    
    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $data = [];
        
        if ($amount !== null) {
            $data['amount'] = [
                'currency_code' => 'USD',
                'value' => number_format($amount, 2, '.', ''),
            ];
        }

        $response = $this->request('POST', "/v2/checkout/orders/{$transactionId}/capture", $data);

        $money = Money::from(
            $response['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
            Currency::fromString($response['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'])
        );

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapPayPalStatus($response['status']),
            money: $money,
            message: 'Authorization captured successfully',
            rawResponse: $response
        );
    }
    
    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        // PayPal usa void para cancelar authorization
        $response = $this->request('POST', "/v2/payments/authorizations/{$transactionId}/void");

        return new PaymentResponse(
            success: true,
            transactionId: $response['id'],
            status: PaymentStatus::CANCELLED,
            money: null,
            message: 'Authorization voided successfully',
            rawResponse: $response
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Direct debit card payments not supported - use credit card flow (PayPal accepts debit cards as credit)');
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Boleto is not supported by PayPal - available only in Brazilian gateways');
    }
    
    public function getBoletoUrl(string $transactionId): string
    {
        throw new GatewayException('Boleto is not supported by PayPal');
    }
    
    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        throw new GatewayException('Boleto is not supported by PayPal');
    }
    
    // ==================== ASSINATURAS ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        // 1. Criar plano (produto + billing plan)
        $planData = [
            'product_id' => $request->metadata['product_id'] ?? $this->createProduct($request->description ?? 'Subscription'),
            'name' => $request->description ?? 'Subscription Plan',
            'billing_cycles' => [
                [
                    'frequency' => [
                        'interval_unit' => strtoupper($this->mapInterval($request->interval->value)),
                        'interval_count' => 1,
                    ],
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => $request->cycles ?? 0, // 0 = infinito
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value' => number_format($request->money->amount(), 2, '.', ''),
                            'currency_code' => $this->getCurrencyCode($request->money->currency()),
                        ]
                    ]
                ]
            ],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => 3,
            ]
        ];

        if ($request->trialDays > 0) {
            array_unshift($planData['billing_cycles'], [
                'frequency' => [
                    'interval_unit' => 'DAY',
                    'interval_count' => $request->trialDays,
                ],
                'tenure_type' => 'TRIAL',
                'sequence' => 1,
                'total_cycles' => 1,
                'pricing_scheme' => [
                    'fixed_price' => [
                        'value' => '0',
                        'currency_code' => $this->getCurrencyCode($request->money->currency()),
                    ]
                ]
            ]);
            // Reajustar sequence do regular
            $planData['billing_cycles'][1]['sequence'] = 2;
        }

        $plan = $this->request('POST', '/v1/billing/plans', $planData);

        // 2. Criar assinatura
        $subData = [
            'plan_id' => $plan['id'],
            'subscriber' => [
                'email_address' => $request->customerEmail ?? 'subscriber@example.com',
            ],
            'application_context' => [
                'return_url' => $request->metadata['return_url'] ?? 'https://example.com/success',
                'cancel_url' => $request->metadata['cancel_url'] ?? 'https://example.com/cancel',
            ]
        ];

        $response = $this->request('POST', '/v1/billing/subscriptions', $subData);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: $response['status'],
            message: 'Subscription created successfully',
            rawResponse: $response
        );
    }

    private function createProduct(string $name): string
    {
        $data = [
            'name' => $name,
            'type' => 'SERVICE',
        ];

        $response = $this->request('POST', '/v1/catalogs/products', $data);
        return $response['id'];
    }
    
    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/v1/billing/subscriptions/{$subscriptionId}/cancel", [
            'reason' => 'Customer requested cancellation'
        ]);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'CANCELLED',
            message: 'Subscription cancelled successfully',
            rawResponse: ['id' => $subscriptionId, 'status' => 'CANCELLED']
        );
    }
    
    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/v1/billing/subscriptions/{$subscriptionId}/suspend", [
            'reason' => 'Subscription suspended'
        ]);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'SUSPENDED',
            message: 'Subscription suspended successfully',
            rawResponse: ['id' => $subscriptionId, 'status' => 'SUSPENDED']
        );
    }
    
    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/v1/billing/subscriptions/{$subscriptionId}/activate", [
            'reason' => 'Subscription reactivated'
        ]);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'ACTIVE',
            message: 'Subscription reactivated successfully',
            rawResponse: ['id' => $subscriptionId, 'status' => 'ACTIVE']
        );
    }
    
    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        $response = $this->request('PATCH', "/v1/billing/subscriptions/{$subscriptionId}", $data);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'ACTIVE',
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
        $response = $this->request('GET', "/v2/checkout/orders/{$transactionId}");

        $money = Money::from(
            $response['purchase_units'][0]['amount']['value'],
            Currency::fromString($response['purchase_units'][0]['amount']['currency_code'])
        );

        return new TransactionStatusResponse(
            success: true,
            transactionId: $response['id'],
            status: $this->mapPayPalStatus($response['status']),
            money: $money,
            rawResponse: $response
        );
    }
    
    public function listTransactions(array $filters = []): array
    {
        // PayPal usa Transaction Search API
        $response = $this->request('GET', '/v1/reporting/transactions', $filters);
        return $response['transaction_details'] ?? [];
    }
    
    // ==================== ESTORNOS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [];

        if ($request->reason) {
            $data['note_to_payer'] = $request->reason;
        }

        $response = $this->request('POST', "/v2/payments/captures/{$request->transactionId}/refund", $data);

        $money = Money::from(
            $response['amount']['value'],
            Currency::fromString($response['amount']['currency_code'])
        );

        return new RefundResponse(
            success: true,
            refundId: $response['id'],
            transactionId: $request->transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Refund processed successfully',
            rawResponse: $response
        );
    }
    
    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $data = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => 'USD',
            ]
        ];

        $response = $this->request('POST', "/v2/payments/captures/{$transactionId}/refund", $data);

        $money = Money::from(
            $response['amount']['value'],
            Currency::fromString($response['amount']['currency_code'])
        );

        return new RefundResponse(
            success: true,
            refundId: $response['id'],
            transactionId: $transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Partial refund processed successfully',
            rawResponse: $response
        );
    }
    
    public function getChargebacks(array $filters = []): array
    {
        // PayPal usa Disputes API
        $response = $this->request('GET', '/v1/customer/disputes', $filters);
        return $response['items'] ?? [];
    }
    
    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        $data = [];
        
        if (isset($evidence['note'])) {
            $data['note'] = $evidence['note'];
        }

        if (isset($evidence['documents'])) {
            $data['evidence_documents'] = $evidence['documents'];
        }

        $response = $this->request('POST', "/v1/customer/disputes/{$chargebackId}/provide-evidence", $data);

        return new PaymentResponse(
            success: true,
            transactionId: $chargebackId,
            status: PaymentStatus::PROCESSING,
            money: null,
            message: 'Dispute evidence submitted successfully',
            rawResponse: $response
        );
    }
    
    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        // PayPal usa Payouts ou Partner referrals para marketplace
        throw new GatewayException('Split payments require PayPal for Marketplaces - use Payouts API or Partner Referrals');
    }
    
    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts require PayPal Partner program and Partner Referrals API - contact PayPal for marketplace setup');
    }
    
    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        throw new GatewayException('Sub-account management requires Partner API access');
    }
    
    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account management requires Partner API access');
    }
    
    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account activation handled by PayPal onboarding flow');
    }
    
    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account deactivation must be done through PayPal dashboard');
    }
    
    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('PayPal accounts serve as wallets - users create them directly on PayPal');
    }
    
    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Use Payouts API to send money to PayPal accounts');
    }
    
    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Balance deduction happens through payments - cannot be done directly');
    }
    
    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Use getBalance() to check merchant account balance');
    }
    
    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Use Payouts API for transfers to other PayPal accounts');
    }
    
    // ==================== ESCROW ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        throw new GatewayException('PayPal has built-in buyer/seller protection that acts as escrow - no manual API');
    }
    
    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Funds are released automatically when transaction completes');
    }
    
    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Use partial capture for similar functionality');
    }
    
    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Cancel the order to void unreleased funds');
    }
    
    // ==================== TRANSFERÊNCIAS ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        // PayPal usa Payouts API
        $data = [
            'sender_batch_header' => [
                'sender_batch_id' => uniqid('batch_', true),
                'email_subject' => 'You have a payout!',
                'email_message' => 'You have received a payout.',
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($request->money->amount(), 2, '.', ''),
                        'currency' => $this->getCurrencyCode($request->money->currency()),
                    ],
                    'receiver' => $request->recipientId,
                    'note' => $request->description ?? 'Payout',
                ]
            ]
        ];

        $response = $this->request('POST', '/v1/payments/payouts', $data);

        return new TransferResponse(
            success: true,
            transferId: $response['batch_header']['payout_batch_id'],
            money: $request->money,
            status: PaymentStatus::PENDING,
            message: 'Payout created successfully',
            rawResponse: $response
        );
    }
    
    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        throw new GatewayException('Scheduled payouts not directly supported - implement custom scheduling logic');
    }
    
    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        throw new GatewayException('Cannot cancel payouts after submission - contact PayPal support for exceptions');
    }
    
    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        // PayPal usa Orders API com approve link
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($request->amount, 2, '.', ''),
                    ],
                    'description' => $request->description ?? 'Payment Link',
                ]
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'return_url' => $request->metadata['return_url'] ?? 'https://example.com/success',
                        'cancel_url' => $request->metadata['cancel_url'] ?? 'https://example.com/cancel',
                    ]
                ]
            ]
        ];

        $response = $this->request('POST', '/v2/checkout/orders', $data);

        // Pegar approve link
        $approveLink = '';
        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveLink = $link['href'];
                break;
            }
        }

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $approveLink,
            status: $response['status'],
            message: 'Payment link created successfully',
            rawResponse: $response
        );
    }
    
    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/v2/checkout/orders/{$linkId}");

        $approveLink = '';
        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveLink = $link['href'];
                break;
            }
        }

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $approveLink,
            status: $response['status'],
            message: 'Payment link retrieved successfully',
            rawResponse: $response
        );
    }
    
    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        // PayPal orders expiram automaticamente após 3 horas
        // Ou pode-se cancelar manualmente
        throw new GatewayException('PayPal orders expire automatically after 3 hours - or cancel the order manually');
    }
    
    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        // PayPal tem proteção automática ao vendedor/comprador
        $response = $this->request('GET', "/v2/checkout/orders/{$transactionId}");
        
        return [
            'risk_level' => 'normal', // PayPal não expõe risk score
            'status' => $response['status'],
            'seller_protection' => $response['purchase_units'][0]['payments']['captures'][0]['seller_protection'] ?? null,
        ];
    }
    
    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management must be done through PayPal dashboard');
    }
    
    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management must be done through PayPal dashboard');
    }
    
    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        $data = [
            'url' => $url,
            'event_types' => array_map(fn($event) => ['name' => $event], $events),
        ];

        $response = $this->request('POST', '/v1/notifications/webhooks', $data);

        return [
            'webhook_id' => $response['id'],
            'url' => $url,
            'events' => $events,
        ];
    }
    
    public function listWebhooks(): array
    {
        $response = $this->request('GET', '/v1/notifications/webhooks');
        return $response['webhooks'] ?? [];
    }
    
    public function deleteWebhook(string $webhookId): bool
    {
        $this->request('DELETE', "/v1/notifications/webhooks/{$webhookId}");
        return true;
    }
    
    // ==================== SALDO ====================
    
    public function getBalance(): BalanceResponse
    {
        // PayPal Balance requer permissões específicas
        throw new GatewayException('Balance checking requires special API permissions - use PayPal dashboard or Reporting API');
    }
    
    public function getSettlementSchedule(array $filters = []): array
    {
        // PayPal usa Transaction Search
        throw new GatewayException('Use Transaction Search API (/v1/reporting/transactions) for settlement details');
    }
    
    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        throw new GatewayException('PayPal does not support receivables anticipation');
    }
}
