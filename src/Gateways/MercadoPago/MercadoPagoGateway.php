<?php

namespace IsraelNogueira\PaymentHub\Gateways\MercadoPago;

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
 * Mercado Pago Gateway - Payment Processing for Latin America
 * 
 * Supports:
 * - Credit Cards (all major brands)
 * - Debit Cards
 * - PIX (Brazil instant payment)
 * - Boleto (Brazil bank slip)
 * - Mercado Crédito (Buy now, pay later)
 * - Subscriptions (recurring billing)
 * - Refunds (full/partial)
 * - Customer management
 * - Payment Links (Checkout Pro)
 * - Marketplace (Split payments)
 * - Money In/Out (Wallets)
 * 
 * Documentation: https://www.mercadopago.com.br/developers
 */
class MercadoPagoGateway implements PaymentGatewayInterface
{
    private const API_URL = 'https://api.mercadopago.com';
    
    private string $accessToken;
    private bool $testMode;
    private string $publicKey;

    public function __construct(string $accessToken, string $publicKey = '', bool $testMode = false)
    {
        $this->accessToken = $accessToken;
        $this->publicKey = $publicKey;
        $this->testMode = $testMode;
        
        // Validate access token format
        if (!str_starts_with($accessToken, 'TEST-') && !str_starts_with($accessToken, 'APP_USR-')) {
            throw new GatewayException('Invalid Mercado Pago access token format');
        }
        
        // Ensure test/live mode matches token
        $isTestToken = str_starts_with($accessToken, 'TEST-');
        if ($testMode !== $isTestToken) {
            throw new GatewayException('Access token mode mismatch: TEST- token requires testMode=true, APP_USR- requires testMode=false');
        }
    }

    // ==================== MÉTODOS PRIVADOS ====================
    
    private function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = self::API_URL . $endpoint;
        
        $defaultHeaders = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . uniqid('', true),
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
            $errorMessage = $decoded['message'] ?? $decoded['error'] ?? 'Request failed';
            $errorCause = $decoded['cause'][0]['description'] ?? '';
            
            throw new GatewayException(
                "Mercado Pago Error: {$errorMessage}" . ($errorCause ? " - {$errorCause}" : ''),
                $httpCode,
                null,
                [
                    'status' => $decoded['status'] ?? null,
                    'error' => $decoded['error'] ?? null,
                    'message' => $decoded['message'] ?? null,
                    'cause' => $decoded['cause'] ?? null,
                    'response' => $decoded
                ]
            );
        }

        return $decoded ?? [];
    }

    private function mapMercadoPagoStatus(string $mpStatus): PaymentStatus
    {
        $statusMap = [
            // Payment statuses
            'approved' => PaymentStatus::APPROVED,
            'pending' => PaymentStatus::PENDING,
            'authorized' => PaymentStatus::PROCESSING, // Pré-autorizado (aguardando captura)
            'in_process' => PaymentStatus::PROCESSING,
            'in_mediation' => PaymentStatus::PROCESSING,
            'rejected' => PaymentStatus::FAILED,
            'cancelled' => PaymentStatus::CANCELLED,
            'refunded' => PaymentStatus::REFUNDED,
            'charged_back' => PaymentStatus::REFUNDED,
            
            // Subscription statuses
            'paused' => PaymentStatus::CANCELLED,
        ];

        return $statusMap[$mpStatus] ?? PaymentStatus::fromString($mpStatus);
    }

    private function getCurrencyCode(Currency $currency): string
    {
        return $currency->value;
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        $data = [
            'email' => $request->email,
        ];

        if ($request->name) {
            $firstName = explode(' ', $request->name)[0] ?? '';
            $lastName = implode(' ', array_slice(explode(' ', $request->name), 1)) ?: $firstName;
            
            $data['first_name'] = $firstName;
            $data['last_name'] = $lastName;
        }

        if ($request->phone) {
            $data['phone'] = [
                'area_code' => substr(preg_replace('/\D/', '', $request->phone), 0, 2),
                'number' => substr(preg_replace('/\D/', '', $request->phone), 2),
            ];
        }

        if ($request->documentNumber) {
            $data['identification'] = [
                'type' => strlen(preg_replace('/\D/', '', $request->documentNumber)) === 11 ? 'CPF' : 'CNPJ',
                'number' => preg_replace('/\D/', '', $request->documentNumber),
            ];
        }

        if ($request->address) {
            $data['address'] = [
                'zip_code' => $request->address['zipcode'] ?? '',
                'street_name' => $request->address['street'] ?? '',
                'street_number' => $request->address['number'] ?? '',
                'city' => $request->address['city'] ?? '',
                'state' => $request->address['state'] ?? '',
            ];
        }

        $response = $this->request('POST', '/v1/customers', $data);

        return new CustomerResponse(
            success: true,
            customerId: $response['id'],
            message: 'Customer created successfully',
            rawResponse: $response
        );
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        $response = $this->request('PUT', "/v1/customers/{$customerId}", $data);

        return new CustomerResponse(
            success: true,
            customerId: $response['id'],
            message: 'Customer updated successfully',
            rawResponse: $response
        );
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        $response = $this->request('GET', "/v1/customers/{$customerId}");

        return new CustomerResponse(
            success: true,
            customerId: $response['id'],
            message: 'Customer retrieved successfully',
            rawResponse: $response
        );
    }

    public function listCustomers(array $filters = []): array
    {
        $response = $this->request('GET', '/v1/customers/search', $filters);
        return $response['results'] ?? [];
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $data = [
            'transaction_amount' => $request->money->amount(),
            'description' => $request->description ?? 'PIX Payment',
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $request->customerEmail?->value() ?? 'customer@example.com',
            ],
        ];

        if ($request->customerName) {
            $firstName = explode(' ', $request->customerName)[0] ?? '';
            $lastName = implode(' ', array_slice(explode(' ', $request->customerName), 1)) ?: $firstName;
            
            $data['payer']['first_name'] = $firstName;
            $data['payer']['last_name'] = $lastName;
        }

        if ($request->customerDocument) {
            $data['payer']['identification'] = [
                'type' => strlen(preg_replace('/\D/', '', $request->customerDocument)) === 11 ? 'CPF' : 'CNPJ',
                'number' => preg_replace('/\D/', '', $request->customerDocument),
            ];
        }

        if ($request->expiresInMinutes) {
            $data['date_of_expiration'] = date('c', strtotime("+{$request->expiresInMinutes} minutes"));
        }

        $response = $this->request('POST', '/v1/payments', $data);

        $money = Money::from($response['transaction_amount'], Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'approved',
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: $money,
            message: $response['status'] === 'approved' ? 'PIX payment approved' : 'PIX payment pending',
            rawResponse: $response,
            metadata: [
                'qr_code' => $response['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                'qr_code_base64' => $response['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                'ticket_url' => $response['point_of_interaction']['transaction_data']['ticket_url'] ?? null,
            ]
        );
    }
    
    public function getPixQrCode(string $transactionId): string
    {
        $response = $this->request('GET', "/v1/payments/{$transactionId}");
        return $response['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '';
    }
    
    public function getPixCopyPaste(string $transactionId): string
    {
        $response = $this->request('GET', "/v1/payments/{$transactionId}");
        return $response['point_of_interaction']['transaction_data']['qr_code'] ?? '';
    }
    
    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $data = [
            'transaction_amount' => $request->money->amount(),
            'installments' => $request->installments,
            'payment_method_id' => $this->detectPaymentMethod($request->cardNumber->value()),
            'payer' => [
                'email' => $request->customerEmail?->value() ?? 'customer@example.com',
            ],
        ];

        // Se tem token do cartão, usa ele
        if ($request->cardToken) {
            $data['token'] = $request->cardToken;
        } else {
            // Senão, cria token inline (requer public_key do frontend normalmente)
            $data['token'] = $this->createCardToken($request);
        }

        if ($request->customerName) {
            $firstName = explode(' ', $request->customerName)[0] ?? '';
            $lastName = implode(' ', array_slice(explode(' ', $request->customerName), 1)) ?: $firstName;
            
            $data['payer']['first_name'] = $firstName;
            $data['payer']['last_name'] = $lastName;
        }

        if ($request->customerDocument) {
            $data['payer']['identification'] = [
                'type' => strlen(preg_replace('/\D/', '', $request->customerDocument)) === 11 ? 'CPF' : 'CNPJ',
                'number' => preg_replace('/\D/', '', $request->customerDocument),
            ];
        }

        if ($request->description) {
            $data['description'] = $request->description;
        }

        if (!$request->capture) {
            $data['capture'] = false;
        }

        $response = $this->request('POST', '/v1/payments', $data);

        $money = Money::from($response['transaction_amount'], Currency::fromString($response['currency_id']));

        return new PaymentResponse(
            success: $response['status'] === 'approved',
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: $money,
            message: $response['status'] === 'approved' ? 'Payment approved' : $response['status_detail'],
            rawResponse: $response,
            metadata: [
                'payment_method' => $response['payment_method_id'] ?? null,
                'card_brand' => $response['payment_type_id'] ?? null,
                'card_last4' => $response['card']['last_four_digits'] ?? null,
                'installments' => $response['installments'] ?? 1,
            ]
        );
    }

    private function createCardToken(CreditCardPaymentRequest $request): string
    {
        $data = [
            'card_number' => $request->cardNumber->value(),
            'cardholder' => [
                'name' => $request->cardHolderName ?? $request->customerName ?? 'CARD HOLDER',
            ],
            'expiration_month' => (int)$request->cardExpiryMonth,
            'expiration_year' => (int)$request->cardExpiryYear,
            'security_code' => $request->cardCvv,
        ];

        if ($request->customerDocument) {
            $data['cardholder']['identification'] = [
                'type' => strlen(preg_replace('/\D/', '', $request->customerDocument)) === 11 ? 'CPF' : 'CNPJ',
                'number' => preg_replace('/\D/', '', $request->customerDocument),
            ];
        }

        $response = $this->request('POST', '/v1/card_tokens', $data);
        
        return $response['id'];
    }

    private function detectPaymentMethod(string $cardNumber): string
    {
        $firstDigit = substr($cardNumber, 0, 1);
        $firstTwo = substr($cardNumber, 0, 2);

        // Detecção básica - Mercado Pago tem endpoint /v1/payment_methods para isso
        if ($firstDigit === '4') return 'visa';
        if (in_array($firstTwo, ['51', '52', '53', '54', '55'])) return 'master';
        if (in_array($firstTwo, ['34', '37'])) return 'amex';
        if ($firstTwo === '60') return 'hipercard';
        if ($firstTwo === '50') return 'elo';

        return 'visa'; // fallback
    }
    
    public function tokenizeCard(array $cardData): string
    {
        $data = [
            'card_number' => $cardData['number'],
            'cardholder' => [
                'name' => $cardData['holderName'] ?? 'CARD HOLDER',
            ],
            'expiration_month' => (int)$cardData['expiryMonth'],
            'expiration_year' => (int)$cardData['expiryYear'],
            'security_code' => $cardData['cvv'],
        ];

        $response = $this->request('POST', '/v1/card_tokens', $data);
        
        return $response['id'];
    }
    
    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $data = ['capture' => true];
        
        if ($amount !== null) {
            $data['transaction_amount'] = $amount;
        }

        $response = $this->request('PUT', "/v1/payments/{$transactionId}", $data);

        $money = Money::from($response['transaction_amount'], Currency::fromString($response['currency_id']));

        return new PaymentResponse(
            success: true,
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: $money,
            message: 'Pre-authorization captured successfully',
            rawResponse: $response
        );
    }
    
    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $response = $this->request('PUT', "/v1/payments/{$transactionId}", [
            'status' => 'cancelled'
        ]);

        return new PaymentResponse(
            success: true,
            transactionId: (string)$response['id'],
            status: PaymentStatus::CANCELLED,
            money: null,
            message: 'Pre-authorization cancelled successfully',
            rawResponse: $response
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        // Mercado Pago trata débito similar a crédito, mas com payment_method específico
        $data = [
            'transaction_amount' => $request->money->amount(),
            'payment_method_id' => 'debmaster', // ou debvisa, debelo, etc
            'payer' => [
                'email' => $request->customerEmail?->value() ?? 'customer@example.com',
            ],
        ];

        if ($request->cardToken) {
            $data['token'] = $request->cardToken;
        }

        $response = $this->request('POST', '/v1/payments', $data);

        $money = Money::from($response['transaction_amount'], Currency::fromString($response['currency_id']));

        return new PaymentResponse(
            success: $response['status'] === 'approved',
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: $money,
            message: $response['status'] === 'approved' ? 'Debit payment approved' : $response['status_detail'],
            rawResponse: $response
        );
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $data = [
            'transaction_amount' => $request->money->amount(),
            'description' => $request->description ?? 'Boleto Payment',
            'payment_method_id' => 'bolbradesco', // ou outros bancos
            'payer' => [
                'email' => $request->customerEmail?->value() ?? 'customer@example.com',
            ],
        ];

        if ($request->customerName) {
            $firstName = explode(' ', $request->customerName)[0] ?? '';
            $lastName = implode(' ', array_slice(explode(' ', $request->customerName), 1)) ?: $firstName;
            
            $data['payer']['first_name'] = $firstName;
            $data['payer']['last_name'] = $lastName;
        }

        if ($request->customerDocument) {
            $data['payer']['identification'] = [
                'type' => strlen(preg_replace('/\D/', '', $request->customerDocument)) === 11 ? 'CPF' : 'CNPJ',
                'number' => preg_replace('/\D/', '', $request->customerDocument),
            ];
        }

        if ($request->dueDate) {
            $data['date_of_expiration'] = date('c', strtotime($request->dueDate));
        }

        $response = $this->request('POST', '/v1/payments', $data);

        $money = Money::from($response['transaction_amount'], Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'pending',
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: $money,
            message: 'Boleto generated successfully',
            rawResponse: $response,
            metadata: [
                'boleto_url' => $response['transaction_details']['external_resource_url'] ?? null,
                'barcode' => $response['barcode']['content'] ?? null,
                'due_date' => $response['date_of_expiration'] ?? null,
            ]
        );
    }
    
    public function getBoletoUrl(string $transactionId): string
    {
        $response = $this->request('GET', "/v1/payments/{$transactionId}");
        return $response['transaction_details']['external_resource_url'] ?? '';
    }
    
    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        $response = $this->request('PUT', "/v1/payments/{$transactionId}", [
            'status' => 'cancelled'
        ]);

        return new PaymentResponse(
            success: true,
            transactionId: (string)$response['id'],
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
            'reason' => $request->description ?? 'Subscription',
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => $this->mapInterval($request->interval->value),
                'transaction_amount' => $request->money->amount(),
                'currency_id' => $this->getCurrencyCode($request->money->currency()),
            ],
            'back_url' => $request->metadata['back_url'] ?? null,
            'payer_email' => $request->customerEmail ?? 'customer@example.com',
        ];

        if ($request->paymentMethod) {
            $data['card_token_id'] = $request->paymentMethod;
        }

        if ($request->trialDays > 0) {
            $data['auto_recurring']['free_trial'] = [
                'frequency' => $request->trialDays,
                'frequency_type' => 'days',
            ];
        }

        $response = $this->request('POST', '/preapproval', $data);

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
        $response = $this->request('PUT', "/preapproval/{$subscriptionId}", [
            'status' => 'cancelled'
        ]);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $response['id'],
            status: 'cancelled',
            message: 'Subscription cancelled successfully',
            rawResponse: $response
        );
    }
    
    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('PUT', "/preapproval/{$subscriptionId}", [
            'status' => 'paused'
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
        $response = $this->request('PUT', "/preapproval/{$subscriptionId}", [
            'status' => 'authorized'
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
        $response = $this->request('PUT', "/preapproval/{$subscriptionId}", $data);

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
            'daily' => 'days',
            'weekly' => 'weeks',
            'monthly' => 'months',
            'yearly' => 'years',
        ];

        return $intervalMap[strtolower($interval)] ?? 'months';
    }
    
    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $response = $this->request('GET', "/v1/payments/{$transactionId}");

        $money = Money::from($response['transaction_amount'], Currency::fromString($response['currency_id']));

        return new TransactionStatusResponse(
            success: true,
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: $money,
            rawResponse: $response
        );
    }
    
    public function listTransactions(array $filters = []): array
    {
        $response = $this->request('GET', '/v1/payments/search', $filters);
        return $response['results'] ?? [];
    }
    
    // ==================== ESTORNOS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [];

        if ($request->reason) {
            $data['metadata'] = ['reason' => $request->reason];
        }

        $response = $this->request('POST', "/v1/payments/{$request->transactionId}/refunds", $data);

        $money = Money::from($response['amount'], Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: (string)$response['id'],
            transactionId: $request->transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Refund processed successfully',
            rawResponse: $response
        );
    }
    
    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $response = $this->request('POST', "/v1/payments/{$transactionId}/refunds", [
            'amount' => $amount
        ]);

        $money = Money::from($response['amount'], Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: (string)$response['id'],
            transactionId: $transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Partial refund processed successfully',
            rawResponse: $response
        );
    }
    
    public function getChargebacks(array $filters = []): array
    {
        // Mercado Pago usa claims/mediations
        $response = $this->request('GET', '/v1/customers/me/claims', $filters);
        return $response['results'] ?? [];
    }
    
    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        throw new GatewayException('Chargeback disputes must be handled through Mercado Pago dashboard');
    }
    
    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        // Marketplace - requer configuração de application_fee
        $data = [
            'transaction_amount' => $request->money->amount(),
            'application_fee' => $request->platformFee ?? 0,
            'marketplace' => 'MARKETPLACE',
            'marketplace_fee' => $request->platformFee ?? 0,
        ];

        // Adicionar receivers (splits)
        if (!empty($request->splits)) {
            foreach ($request->splits as $split) {
                // Mercado Pago usa money release system
                $data['additional_info']['split_payment'][] = [
                    'amount' => $split['amount'],
                    'account_id' => $split['recipient_id'],
                ];
            }
        }

        $response = $this->request('POST', '/v1/advanced_payments', $data);

        return new PaymentResponse(
            success: true,
            transactionId: (string)$response['id'],
            status: $this->mapMercadoPagoStatus($response['status']),
            money: Money::from($response['transaction_amount'], Currency::BRL),
            message: 'Split payment created successfully',
            rawResponse: $response
        );
    }
    
    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts (Marketplace sellers) must be created through OAuth flow - see: https://www.mercadopago.com.br/developers/en/docs/marketplace/landing');
    }
    
    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        throw new GatewayException('Sub-account management requires OAuth - use Mercado Pago dashboard');
    }
    
    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account management requires OAuth - use Mercado Pago dashboard');
    }
    
    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account activation handled automatically by Mercado Pago');
    }
    
    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-account deactivation must be done through Mercado Pago dashboard');
    }
    
    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('Wallets not directly supported - use Mercado Pago Money In/Out API');
    }
    
    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Balance management via Money In API - requires specific integration');
    }
    
    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Balance management via Money Out API - requires specific integration');
    }
    
    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Use getBalance() to check Mercado Pago account balance');
    }
    
    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Wallet transfers not supported - use money_release for marketplace payments');
    }
    
    // ==================== ESCROW ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        // Mercado Pago tem money release system que funciona como escrow
        throw new GatewayException('Use money_release parameter in Advanced Payments for escrow-like functionality');
    }
    
    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Use /v1/advanced_payments/{id}/disburses endpoint for money release');
    }
    
    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Use /v1/advanced_payments/{id}/disburses with amount parameter');
    }
    
    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Cancel the payment to void unreleased funds');
    }
    
    // ==================== TRANSFERÊNCIAS ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        throw new GatewayException('Direct transfers use /v1/advanced_payments with disbursements');
    }
    
    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        throw new GatewayException('Use money_release_date parameter in Advanced Payments');
    }
    
    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        throw new GatewayException('Cancel the parent payment to cancel scheduled releases');
    }
    
    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $data = [
            'items' => [
                [
                    'title' => $request->description ?? 'Payment Link',
                    'quantity' => 1,
                    'unit_price' => $request->amount,
                ]
            ],
            'back_urls' => [
                'success' => $request->metadata['success_url'] ?? null,
                'failure' => $request->metadata['failure_url'] ?? null,
                'pending' => $request->metadata['pending_url'] ?? null,
            ],
            'auto_return' => 'approved',
        ];

        if ($request->expiresAt) {
            $data['expiration_date_to'] = date('c', strtotime($request->expiresAt));
        }

        $response = $this->request('POST', '/checkout/preferences', $data);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['init_point'], // Sandbox URL
            status: 'active',
            message: 'Payment link created successfully',
            rawResponse: $response
        );
    }
    
    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/checkout/preferences/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['init_point'],
            status: 'active',
            message: 'Payment link retrieved successfully',
            rawResponse: $response
        );
    }
    
    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('PUT', "/checkout/preferences/{$linkId}", [
            'expiration_date_to' => date('c')
        ]);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['init_point'],
            status: 'expired',
            message: 'Payment link expired successfully',
            rawResponse: $response
        );
    }
    
    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        $response = $this->request('GET', "/v1/payments/{$transactionId}");
        
        return [
            'risk_level' => 'normal', // Mercado Pago não expõe score detalhado
            'status' => $response['status'],
            'status_detail' => $response['status_detail'],
        ];
    }
    
    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management must be done through Mercado Pago dashboard');
    }
    
    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management must be done through Mercado Pago dashboard');
    }
    
    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        // Mercado Pago usa IPN/Webhooks configurados no painel
        throw new GatewayException('Webhooks must be configured through Mercado Pago dashboard at: https://www.mercadopago.com.br/developers/panel/webhooks');
    }
    
    public function listWebhooks(): array
    {
        throw new GatewayException('List webhooks through Mercado Pago dashboard');
    }
    
    public function deleteWebhook(string $webhookId): bool
    {
        throw new GatewayException('Delete webhooks through Mercado Pago dashboard');
    }
    
    // ==================== SALDO ====================
    
    public function getBalance(): BalanceResponse
    {
        // Mercado Pago não tem endpoint público de saldo
        // Precisa usar reports ou merchant services
        throw new GatewayException('Balance checking requires Merchant Services API - contact Mercado Pago support');
    }
    
    public function getSettlementSchedule(array $filters = []): array
    {
        // Mercado Pago usa releases automáticos
        throw new GatewayException('Settlement schedule available through Reports API or Dashboard');
    }
    
    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        throw new GatewayException('Receivables anticipation available through Mercado Pago Antecipação - contact support');
    }
}