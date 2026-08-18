<?php

namespace IsraelNogueira\PaymentHub\Gateways\C6Bank;

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

class C6BankGateway implements PaymentGatewayInterface
{
    private const PRODUCTION_URL = 'https://baas-api.c6bank.info';
    private const SANDBOX_URL = 'https://baas-api-sandbox.c6bank.info';
    
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;
    private ?string $personId = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        bool $sandbox = false,
        ?string $personId = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        $this->personId = $personId;
    }

    // ==================== AUTENTICAÇÃO ====================
    
    private function authenticate(): string
    {
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $url = $this->baseUrl . '/v1/auth';
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $data = http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new GatewayException('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new GatewayException(
                'Authentication failed: ' . ($decoded['error_description'] ?? 'Unknown error'),
                $httpCode
            );
        }

        $this->accessToken = $decoded['access_token'];
        $this->tokenExpiry = time() + ($decoded['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    // ==================== MÉTODOS PRIVADOS ====================
    
    private function request(string $method, string $endpoint, array $data = [], array $queryParams = [], array $extraHeaders = []): array
    {
        $token = $this->authenticate();
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ];

        if ($this->personId && !in_array('person_Id:', $extraHeaders, true)) {
            $headers[] = 'person_Id: ' . $this->personId;
        }

        $headers = array_merge($headers, $extraHeaders);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
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
            $errorMessage = $decoded['error'] ?? $decoded['message'] ?? 'Request failed';
            throw new GatewayException(
                $errorMessage,
                $httpCode,
                null,
                ['response' => $decoded]
            );
        }

        return $decoded ?? [];
    }

    private function generateTxId(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = rand(26, 35);
        $txId = '';
        
        for ($i = 0; $i < $length; $i++) {
            $txId .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $txId;
    }

    private function mapC6Status(string $c6Status): PaymentStatus
    {
        $statusMap = [
            'ATIVA' => PaymentStatus::PENDING,
            'CONCLUIDA' => PaymentStatus::APPROVED,
            'REMOVIDA_PELO_USUARIO_RECEBEDOR' => PaymentStatus::CANCELLED,
            'REMOVIDA_PELO_PSP' => PaymentStatus::CANCELLED,
            'CREATED' => PaymentStatus::PENDING,
            'REGISTERED' => PaymentStatus::PENDING,
            'PAID' => PaymentStatus::APPROVED,
            'CANCELLED' => PaymentStatus::CANCELLED,
            'EXPIRED' => PaymentStatus::FAILED,
            'AUTHORIZED' => PaymentStatus::PENDING,
            'PROCESSING' => PaymentStatus::PROCESSING,
            'APPROVED' => PaymentStatus::APPROVED,
            'DENIED' => PaymentStatus::FAILED,
            'REFUNDED' => PaymentStatus::REFUNDED,
        ];

        return $statusMap[$c6Status] ?? PaymentStatus::PENDING;
    }

    // ==================== PAGAMENTO GENÉRICO ====================
    
    /**
     * Pagamento genérico para C6Bank.
     * Este método é exigido pela interface PaymentGatewayInterface.
     * C6Bank não tem um endpoint específico para pagamento genérico,
     * então redirecionamos para os métodos específicos baseado no tipo.
     */
    public function createPayment(array $data): PaymentResponse
    {
        // Verifica se é um pagamento PIX
        if (isset($data['pixKey'])) {
            $request = PixPaymentRequest::create(
                amount: $data['amount'] ?? 0,
                currency: $data['currency'] ?? 'BRL',
                customerName: $data['customerName'] ?? null,
                customerDocument: $data['customerDocument'] ?? null,
                customerEmail: $data['customerEmail'] ?? null,
                description: $data['description'] ?? null,
                metadata: ['pixKey' => $data['pixKey']]
            );
            return $this->createPixPayment($request);
        }

        // Verifica se é um pagamento com cartão
        if (isset($data['cardNumber']) || isset($data['cardToken'])) {
            $request = CreditCardPaymentRequest::create(
                amount: $data['amount'] ?? 0,
                currency: $data['currency'] ?? 'BRL',
                cardToken: $data['cardToken'] ?? null,
                cardNumber: $data['cardNumber'] ?? null,
                cardHolderName: $data['cardHolderName'] ?? null,
                cardExpiryMonth: $data['cardExpiryMonth'] ?? null,
                cardExpiryYear: $data['cardExpiryYear'] ?? null,
                cardCvv: $data['cardCvv'] ?? null,
                installments: $data['installments'] ?? 1,
                capture: $data['capture'] ?? true,
                customerName: $data['customerName'] ?? null,
                customerDocument: $data['customerDocument'] ?? null,
                customerEmail: $data['customerEmail'] ?? null
            );
            return $this->createCreditCardPayment($request);
        }

        throw new GatewayException('Invalid payment data: unable to determine payment method');
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $txId = $this->generateTxId();
        
        $data = [
            'calendario' => [
                'expiracao' => 172800,
            ],
            'valor' => [
                'original' => number_format($request->getAmount(), 2, '.', ''),
                'modalidadeAlteracao' => 1,
            ],
            'chave' => $request->metadata['pixKey'] ?? 'c6-pix-key',
            'solicitacaoPagador' => $request->description ?? 'Pagamento via PIX',
        ];

        if ($request->customerName && $request->getCustomerDocument()) {
            $docType = strlen($request->getCustomerDocument()) === 11 ? 'cpf' : 'cnpj';
            $data['devedor'] = [
                $docType => preg_replace('/\D/', '', $request->getCustomerDocument()),
                'nome' => $request->customerName,
            ];
        }

        $response = $this->request('PUT', "/v1/pix/cob/{$txId}", $data);

        return PaymentResponse::create(
            success: isset($response['txid']),
            transactionId: $response['txid'] ?? $txId,
            status: $this->mapC6Status($response['status'] ?? 'ATIVA')->value,
            amount: $request->getAmount(),
            currency: Currency::BRL->value,
            rawResponse: $response,
            metadata: [
                'pixQrCode' => $response['qrcode'] ?? null,
                'pixCopyPaste' => $response['pixCopiaECola'] ?? null,
            ]
        );
    }
    
    public function getPixQrCode(string $transactionId): string
    {
        $response = $this->request('GET', "/v1/pix/cob/{$transactionId}");
        
        if (!isset($response['qrcode'])) {
            throw new GatewayException('QR Code not found in response');
        }
        
        return $response['qrcode'];
    }
    
    public function getPixCopyPaste(string $transactionId): string
    {
        $response = $this->request('GET', "/v1/pix/cob/{$transactionId}");
        
        if (!isset($response['pixCopiaECola'])) {
            throw new GatewayException('Copy-Paste code not found in response');
        }
        
        return $response['pixCopiaECola'];
    }
    
    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $cardNumber = $request->cardNumber ? (string)$request->cardNumber : '';
        $customerEmail = $request->customerEmail ? (string)$request->customerEmail : '';
        
        $data = [
            'amount' => (int)($request->money->amount() * 100),
            'card' => [
                'number' => $cardNumber,
                'holder_name' => $request->cardHolderName,
                'exp_month' => $request->cardExpiryMonth,
                'exp_year' => $request->cardExpiryYear,
                'cvv' => $request->cardCvv,
            ],
            'installments' => $request->installments,
            'capture' => $request->capture,
            'description' => $request->description ?? 'Pagamento com cartão',
        ];

        if ($request->customerName || $request->customerDocument) {
            $data['customer'] = [
                'name' => $request->customerName ?? '',
                'tax_id' => preg_replace('/\D/', '', $request->customerDocument ?? ''),
            ];
        }

        $response = $this->request('POST', '/v1/payments/credit-card', $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? '',
            status: $this->mapC6Status($response['status'] ?? 'PROCESSING')->value,
            amount: $request->money->amount(),
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function tokenizeCard(array $cardData): string
    {
        $data = [
            'card' => [
                'number' => $cardData['number'] ?? '',
                'holder_name' => $cardData['holder_name'] ?? '',
                'exp_month' => $cardData['exp_month'] ?? '',
                'exp_year' => $cardData['exp_year'] ?? '',
                'cvv' => $cardData['cvv'] ?? '',
            ],
        ];

        $response = $this->request('POST', '/v1/cards/tokenize', $data);
        
        if (!isset($response['token'])) {
            throw new GatewayException('Card tokenization failed');
        }
        
        return $response['token'];
    }
    
    public function capturePreAuthorization(string $transactionId, ?Money $amount = null): PaymentResponse
    {
        $data = [];
        if ($amount !== null) {
            $data['amount'] = (int)($amount->amount() * 100);
        }

        $response = $this->request('POST', "/v1/payments/{$transactionId}/capture", $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? $transactionId,
            status: $this->mapC6Status($response['status'] ?? 'APPROVED')->value,
            amount: $amount?->amount() ?? 0,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $response = $this->request('POST', "/v1/payments/{$transactionId}/void");

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? $transactionId,
            status: PaymentStatus::CANCELLED->value,
            amount: 0,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        $cardNumber = $request->cardNumber ? (string)$request->cardNumber : '';
        
        $data = [
            'amount' => (int)($request->money->amount() * 100),
            'card' => [
                'number' => $cardNumber,
                'holder_name' => $request->cardHolderName,
                'exp_month' => $request->cardExpiryMonth,
                'exp_year' => $request->cardExpiryYear,
                'cvv' => $request->cardCvv,
            ],
            'description' => $request->description ?? 'Pagamento com cartão de débito',
        ];

        if ($request->customerName || $request->customerDocument) {
            $data['customer'] = [
                'name' => $request->customerName ?? '',
                'tax_id' => preg_replace('/\D/', '', $request->customerDocument ?? ''),
            ];
        }

        $response = $this->request('POST', '/v1/payments/debit-card', $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? '',
            status: $this->mapC6Status($response['status'] ?? 'PROCESSING')->value,
            amount: $request->money->amount(),
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $data = [
            'amount' => number_format($request->getAmount(), 2, '.', ''),
            'due_date' => $request->dueDate,
            'payer' => [
                'name' => $request->customerName,
                'tax_id' => preg_replace('/\D/', '', $request->getCustomerDocument() ?? ''),
            ],
            'description' => $request->description ?? 'Boleto bancário',
        ];

        if ($request->customerAddress) {
            $data['payer']['address'] = [
                'street' => $request->customerAddress['street'] ?? '',
                'number' => $request->customerAddress['number'] ?? '',
                'complement' => $request->customerAddress['complement'] ?? '',
                'neighborhood' => $request->customerAddress['neighborhood'] ?? '',
                'city' => $request->customerAddress['city'] ?? '',
                'state' => $request->customerAddress['state'] ?? '',
                'postal_code' => preg_replace('/\D/', '', $request->customerAddress['postal_code'] ?? ''),
            ];
        }

        $response = $this->request('POST', '/v1/bank-slips', $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? '',
            status: $this->mapC6Status($response['status'] ?? 'REGISTERED')->value,
            amount: $request->getAmount(),
            currency: Currency::BRL->value,
            rawResponse: $response,
            metadata: [
                'boletoUrl' => $response['url'] ?? null,
                'boletoBarcode' => $response['barcode'] ?? null,
                'boletoDigitableLine' => $response['digitable_line'] ?? null,
            ]
        );
    }

    
    
    public function getBoletoUrl(string $transactionId): string
    {
        $response = $this->request('GET', "/v1/bank-slips/{$transactionId}");
        
        if (!isset($response['url'])) {
            throw new GatewayException('Boleto URL not found');
        }
        
        return $response['url'];
    }
    
    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        $response = $this->request('DELETE', "/v1/bank-slips/{$transactionId}");

        return PaymentResponse::create(
            success: true,
            transactionId: $transactionId,
            status: PaymentStatus::CANCELLED->value,
            amount: 0,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    // ==================== ASSINATURAS/RECORRÊNCIA ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $data = [
            'amount' => number_format($request->getAmount(), 2, '.', ''),
            'interval' => strtolower($request->interval->value),
            'description' => $request->description ?? 'Assinatura',
            'customer' => [
                'name' => $request->customerName ?? '',
                'tax_id' => preg_replace('/\D/', '', $request->getCustomerDocument() ?? ''),
                'email' => $request->getCustomerEmail() ?? '',
            ],
        ];

        if ($request->cardToken) {
            $data['payment_method'] = [
                'type' => 'card',
                'token' => $request->cardToken,
            ];
        }

        if ($request->cycles) {
            $data['max_charges'] = $request->cycles;
        }

        if ($request->startDate) {
            $data['start_date'] = $request->startDate;
        }

        $response = $this->request('POST', '/v1/subscriptions', $data);

        return new SubscriptionResponse(
            success: isset($response['id']),
            subscriptionId: $response['id'] ?? '',
            status: $this->mapC6Status($response['status'] ?? 'CREATED')->value,
            rawResponse: $response
        );
    }

    
    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('DELETE', "/v1/subscriptions/{$subscriptionId}");

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'cancelled',
            rawResponse: $response
        );
    }
    
    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/v1/subscriptions/{$subscriptionId}/suspend");

        return new SubscriptionResponse(
            success: isset($response['id']),
            subscriptionId: $subscriptionId,
            status: 'suspended',
            rawResponse: $response
        );
    }
    
    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->request('POST', "/v1/subscriptions/{$subscriptionId}/reactivate");

        return new SubscriptionResponse(
            success: isset($response['id']),
            subscriptionId: $subscriptionId,
            status: 'active',
            rawResponse: $response
        );
    }
    
    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        $updateData = [];
        
        if (isset($data['amount'])) {
            $updateData['amount'] = number_format($data['amount'], 2, '.', '');
        }
        
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        $response = $this->request('PATCH', "/v1/subscriptions/{$subscriptionId}", $updateData);

        return new SubscriptionResponse(
            success: isset($response['id']),
            subscriptionId: $subscriptionId,
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }
    
    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $response = $this->request('GET', "/v1/payments/{$transactionId}");

        return TransactionStatusResponse::create(
            success: isset($response['status']),
            transactionId: $transactionId,
            status: $this->mapC6Status($response['status'] ?? 'PENDING')->value,
            amount: $response['amount'] ?? 0,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function listTransactions(array $filters = []): array
    {
        $queryParams = [];
        
        if (isset($filters['start_date'])) {
            $queryParams['start_date'] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $queryParams['end_date'] = $filters['end_date'];
        }
        
        if (isset($filters['status'])) {
            $queryParams['status'] = $filters['status'];
        }
        
        if (isset($filters['limit'])) {
            $queryParams['limit'] = $filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $queryParams['offset'] = $filters['offset'];
        }

        $response = $this->request('GET', '/v1/payments', [], $queryParams);
        
        return $response['data'] ?? [];
    }
    
    // ==================== ESTORNOS E CHARGEBACKS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [];
        
        if ($request->money) {
            $data['amount'] = $request->money->cents();
        }
        
        if ($request->reason) {
            $data['reason'] = $request->reason;
        }

        $response = $this->request('POST', "/v1/payments/{$request->transactionId}/refund", $data);

        return RefundResponse::create(
            success: isset($response['id']),
            refundId: $response['id'] ?? '',
            transactionId: $request->transactionId,
            amount: ($response['amount'] ?? 0) / 100,
            status: $this->mapC6Status($response['status'] ?? 'REFUNDED')->value,
            rawResponse: $response
        );
    }
    
    public function partialRefund(string $transactionId, Money $amount): RefundResponse
    {
        $data = [
            'amount' => $amount->cents(),
        ];

        $response = $this->request('POST', "/v1/payments/{$transactionId}/refund", $data);

        return RefundResponse::create(
            success: isset($response['id']),
            refundId: $response['id'] ?? '',
            transactionId: $transactionId,
            amount: $amount->amount(),
            status: $this->mapC6Status($response['status'] ?? 'REFUNDED')->value,
            rawResponse: $response
        );
    }
    
    public function getChargebacks(array $filters = []): array
    {
        $queryParams = [];
        
        if (isset($filters['start_date'])) {
            $queryParams['start_date'] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $queryParams['end_date'] = $filters['end_date'];
        }

        $response = $this->request('GET', '/v1/chargebacks', [], $queryParams);
        
        return $response['data'] ?? [];
    }
    
    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        $data = [
            'evidence' => $evidence,
        ];

        $response = $this->request('POST', "/v1/chargebacks/{$chargebackId}/dispute", $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? $chargebackId,
            status: $this->mapC6Status($response['status'] ?? 'PROCESSING')->value,
            amount: 0,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        $data = [
            'amount' => $request->money->cents(),
            'description' => $request->description ?? 'Split payment',
            'splits' => array_map(function($split) {
                return [
                    'recipient_id' => $split['recipientId'],
                    'amount' => (int)($split['amount'] * 100),
                    'percentage' => $split['percentage'] ?? null,
                    'fee_liable' => $split['feeLiable'] ?? false,
                ];
            }, $request->splits),
        ];

        $response = $this->request('POST', '/v1/payments/split', $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? '',
            status: $this->mapC6Status($response['status'] ?? 'PROCESSING')->value,
            amount: $request->money->amount(),
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        $data = [
            'name' => $request->name,
            'tax_id' => preg_replace('/\D/', '', $request->documentNumber),
            'email' => $request->email,
            'phone' => preg_replace('/\D/', '', $request->phone ?? ''),
            'address' => [
                'street' => $request->address['street'] ?? '',
                'number' => $request->address['number'] ?? '',
                'complement' => $request->address['complement'] ?? '',
                'neighborhood' => $request->address['neighborhood'] ?? '',
                'city' => $request->address['city'] ?? '',
                'state' => $request->address['state'] ?? '',
                'postal_code' => preg_replace('/\D/', '', $request->address['postal_code'] ?? ''),
            ],
        ];

        if (isset($request->bankAccount)) {
            $data['bank_account'] = [
                'bank_code' => $request->bankAccount['bank_code'],
                'agency' => $request->bankAccount['agency'],
                'account' => $request->bankAccount['account'],
                'account_digit' => $request->bankAccount['account_digit'],
                'type' => $request->bankAccount['type'] ?? 'checking',
            ];
        }

        $response = $this->request('POST', '/v1/sub-accounts', $data);

        return new SubAccountResponse(
            success: isset($response['id']),
            subAccountId: $response['id'] ?? '',
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }
    
    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        $response = $this->request('PATCH', "/v1/sub-accounts/{$subAccountId}", $data);

        return new SubAccountResponse(
            success: isset($response['id']),
            subAccountId: $subAccountId,
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }
    
    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        $response = $this->request('GET', "/v1/sub-accounts/{$subAccountId}");

        return new SubAccountResponse(
            success: isset($response['id']),
            subAccountId: $subAccountId,
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }
    
    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        $response = $this->request('POST', "/v1/sub-accounts/{$subAccountId}/activate");

        return new SubAccountResponse(
            success: isset($response['id']),
            subAccountId: $subAccountId,
            status: 'active',
            rawResponse: $response
        );
    }
    
    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        $response = $this->request('POST', "/v1/sub-accounts/{$subAccountId}/deactivate");

        return new SubAccountResponse(
            success: isset($response['id']),
            subAccountId: $subAccountId,
            status: 'inactive',
            rawResponse: $response
        );
    }
    
    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        $data = [
            'name' => $request->name,
            'customer_id' => $request->customerId,
            'description' => $request->description ?? '',
        ];

        $response = $this->request('POST', '/v1/wallets', $data);

        return new WalletResponse(
            success: isset($response['id']),
            walletId: $response['id'] ?? '',
            balance: $response['balance'] ?? 0,
            currency: $request->currency->value,
            rawResponse: $response
        );
    }
    
    public function addBalance(string $walletId, Money $amount): WalletResponse
    {
        $data = [
            'amount' => $amount->cents(),
            'description' => 'Add balance',
        ];

        $response = $this->request('POST', "/v1/wallets/{$walletId}/credit", $data);

        return new WalletResponse(
            success: isset($response['id']),
            walletId: $walletId,
            balance: ($response['balance'] ?? 0) / 100,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function deductBalance(string $walletId, Money $amount): WalletResponse
    {
        $data = [
            'amount' => $amount->cents(),
            'description' => 'Deduct balance',
        ];

        $response = $this->request('POST', "/v1/wallets/{$walletId}/debit", $data);

        return new WalletResponse(
            success: isset($response['id']),
            walletId: $walletId,
            balance: ($response['balance'] ?? 0) / 100,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function getWalletBalance(string $walletId): BalanceResponse
    {
        $response = $this->request('GET', "/v1/wallets/{$walletId}");

        $available = ($response['balance'] ?? 0) / 100;
        $pending = ($response['pending_balance'] ?? 0) / 100;

        return new BalanceResponse(
            success: isset($response['balance']),
            balance: $available + $pending,
            availableBalance: $available,
            pendingBalance: $pending,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, Money $amount): TransferResponse
    {
        $data = [
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'amount' => $amount->cents(),
        ];

        $response = $this->request('POST', '/v1/wallets/transfer', $data);

        return TransferResponse::create(
            success: isset($response['id']),
            transferId: $response['id'] ?? '',
            amount: $amount->amount(),
            status: $this->mapC6Status($response['status'] ?? 'APPROVED')->value,
            rawResponse: $response
        );
    }
    
    // ==================== ESCROW (CUSTÓDIA) ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        $data = [
            'amount' => $request->money->cents(),
            'transaction_id' => $request->transactionId,
            'description' => $request->description ?? 'Escrow hold',
            'release_date' => $request->releaseDate,
        ];

        $response = $this->request('POST', '/v1/escrow/hold', $data);

        return new EscrowResponse(
            success: isset($response['id']),
            escrowId: $response['id'] ?? '',
            amount: $request->money->amount(),
            status: $response['status'] ?? 'held',
            rawResponse: $response
        );
    }
    
    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        $response = $this->request('POST', "/v1/escrow/{$escrowId}/release");

        return new EscrowResponse(
            success: isset($response['id']),
            escrowId: $escrowId,
            amount: ($response['amount'] ?? 0) / 100,
            status: 'released',
            rawResponse: $response
        );
    }
    
    public function partialReleaseEscrow(string $escrowId, Money $amount): EscrowResponse
    {
        $data = [
            'amount' => $amount->cents(),
        ];

        $response = $this->request('POST', "/v1/escrow/{$escrowId}/partial-release", $data);

        return new EscrowResponse(
            success: isset($response['id']),
            escrowId: $escrowId,
            amount: $amount->amount(),
            status: $response['status'] ?? 'partially_released',
            rawResponse: $response
        );
    }
    
    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        $response = $this->request('POST', "/v1/escrow/{$escrowId}/cancel");

        return new EscrowResponse(
            success: isset($response['id']),
            escrowId: $escrowId,
            amount: 0,
            status: 'cancelled',
            rawResponse: $response
        );
    }
    
    // ==================== TRANSFERÊNCIAS E SAQUES ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        $data = [
            'amount' => $request->money->cents(),
            'bank_account' => [
                'bank_code' => $request->bankCode,
                'agency' => $request->agencyNumber,
                'account' => $request->accountNumber,
                'type' => $request->accountType ?? 'checking',
            ],
            'description' => $request->description ?? 'Transfer',
        ];

        if (isset($request->recipientName)) {
            $data['bank_account']['holder_name'] = $request->recipientName;
        }

        if (isset($request->documentNumber)) {
            $data['bank_account']['holder_tax_id'] = preg_replace('/\D/', '', $request->documentNumber);
        }

        $response = $this->request('POST', '/v1/transfers', $data);

        return TransferResponse::create(
            success: isset($response['id']),
            transferId: $response['id'] ?? '',
            amount: $request->money->amount(),
            status: $this->mapC6Status($response['status'] ?? 'PROCESSING')->value,
            rawResponse: $response
        );
    }
    
    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        $data = [
            'amount' => $request->money->cents(),
            'bank_account' => [
                'bank_code' => $request->bankCode,
                'agency' => $request->agencyNumber,
                'account' => $request->accountNumber,
                'type' => $request->accountType ?? 'checking',
            ],
            'scheduled_date' => $date,
            'description' => $request->description ?? 'Scheduled transfer',
        ];

        $response = $this->request('POST', '/v1/transfers/scheduled', $data);

        return TransferResponse::create(
            success: isset($response['id']),
            transferId: $response['id'] ?? '',
            amount: $request->money->amount(),
            status: 'scheduled',
            rawResponse: $response
        );
    }
    
    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        $response = $this->request('DELETE', "/v1/transfers/scheduled/{$transferId}");

        return TransferResponse::create(
            success: true,
            transferId: $transferId,
            amount: 0,
            status: 'cancelled',
            rawResponse: $response
        );
    }
    
    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $data = [
            'amount' => (int)($request->amount * 100),
            'description' => $request->description ?? 'Link de pagamento',
            'external_reference_id' => $request->externalReferenceId ?? uniqid('LINK_'),
        ];

        if ($request->customerName || $request->customerDocument || $request->customerEmail) {
            $data['payer'] = [
                'name' => $request->customerName ?? '',
                'tax_id' => preg_replace('/\D/', '', $request->customerDocument ?? ''),
                'email' => $request->customerEmail ?? '',
            ];
        }

        $data['payment_methods'] = [];

        $methods = $request->acceptedPaymentMethods ?? [];

        if (in_array('pix', $methods, true)) {
            $data['payment_methods']['pix'] = [
                'enabled' => true,
            ];
        }

        if (in_array('credit_card', $methods, true)) {
            $data['payment_methods']['credit_card'] = [
                'enabled' => true,
                'installments' => $request->maxInstallments ?? 1,
            ];
        }

        if (in_array('boleto', $methods, true)) {
            $data['payment_methods']['boleto'] = [
                'enabled' => true,
            ];
        }

        if ($request->expiresAt) {
            $data['expires_at'] = $request->expiresAt->format('Y-m-d\TH:i:s');
        }

        if ($request->redirectUrl) {
            $data['redirect_url'] = $request->redirectUrl;
        }

        $response = $this->request('POST', '/v1/payment-links', $data);

        return new PaymentLinkResponse(
            success: isset($response['id']),
            linkId: $response['id'] ?? '',
            url: $response['url'] ?? '',
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }
    
    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/v1/payment-links/{$linkId}");

        return new PaymentLinkResponse(
            success: isset($response['id']),
            linkId: $linkId,
            url: $response['url'] ?? '',
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }
    
    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('DELETE', "/v1/payment-links/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: '',
            status: 'expired',
            rawResponse: $response
        );
    }
    
    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        $data = [
            'name' => $request->name,
            'tax_id' => preg_replace('/\D/', '', $request->document?->value() ?? ''),
            'email' => $request->email->value(),
            'phone' => preg_replace('/\D/', '', $request->phone ?? ''),
        ];

        if (isset($request->address)) {
            $data['address'] = [
                'street' => $request->address['street'] ?? '',
                'number' => $request->address['number'] ?? '',
                'complement' => $request->address['complement'] ?? '',
                'neighborhood' => $request->address['neighborhood'] ?? '',
                'city' => $request->address['city'] ?? '',
                'state' => $request->address['state'] ?? '',
                'postal_code' => preg_replace('/\D/', '', $request->address['postal_code'] ?? ''),
            ];
        }

        $response = $this->request('POST', '/v1/customers', $data);

        return new CustomerResponse(
            success: isset($response['id']),
            customerId: $response['id'] ?? '',
            rawResponse: $response
        );
    }
    
    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        $response = $this->request('PATCH', "/v1/customers/{$customerId}", $data);

        return new CustomerResponse(
            success: isset($response['id']),
            customerId: $customerId,
            rawResponse: $response
        );
    }
    
    public function getCustomer(string $customerId): CustomerResponse
    {
        $response = $this->request('GET', "/v1/customers/{$customerId}");

        return new CustomerResponse(
            success: isset($response['id']),
            customerId: $customerId,
            rawResponse: $response
        );
    }
    
    public function listCustomers(array $filters = []): array
    {
        $queryParams = [];
        
        if (isset($filters['limit'])) {
            $queryParams['limit'] = $filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $queryParams['offset'] = $filters['offset'];
        }

        $response = $this->request('GET', '/v1/customers', [], $queryParams);
        
        return $response['data'] ?? [];
    }
    
    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        $response = $this->request('GET', "/v1/fraud-analysis/{$transactionId}");
        
        return [
            'score' => $response['score'] ?? 0,
            'status' => $response['status'] ?? 'unknown',
            'recommendations' => $response['recommendations'] ?? [],
            'raw' => $response,
        ];
    }
    
    public function addToBlacklist(string $identifier, string $type): bool
    {
        $data = [
            'identifier' => $identifier,
            'type' => $type,
        ];

        $this->request('POST', '/v1/blacklist', $data);
        
        return true;
    }
    
    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        $this->request('DELETE', '/v1/blacklist', [
            'identifier' => $identifier,
            'type' => $type,
        ]);
        
        return true;
    }
    
    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        $responses = [];
        
        foreach ($events as $event) {
            $data = [
                'url' => $url,
                'event' => $event,
            ];
            
            $response = $this->request('POST', '/v1/webhooks', $data);
            $responses[] = $response;
        }
        
        return $responses;
    }
    
    public function listWebhooks(): array
    {
        $response = $this->request('GET', '/v1/webhooks');
        
        return $response['data'] ?? [];
    }
    
    public function deleteWebhook(string $webhookId): bool
    {
        $this->request('DELETE', "/v1/webhooks/{$webhookId}");
        return true;
    }
    
    // ==================== SALDO E CONCILIAÇÃO ====================
    
    public function getBalance(): BalanceResponse
    {
        $response = $this->request('GET', '/v1/balance');

        $available = ($response['available'] ?? 0) / 100;
        $pending = ($response['pending'] ?? 0) / 100;

        return new BalanceResponse(
            success: isset($response['available']),
            balance: $available + $pending,
            availableBalance: $available,
            pendingBalance: $pending,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
    
    public function getSettlementSchedule(array $filters = []): array
    {
        $queryParams = [];
        
        if (isset($filters['start_date'])) {
            $queryParams['start_date'] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $queryParams['end_date'] = $filters['end_date'];
        }

        $response = $this->request('GET', '/v1/settlements', [], $queryParams);
        
        return $response['data'] ?? [];
    }
    
    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        $data = [
            'transaction_ids' => $transactionIds,
        ];

        $response = $this->request('POST', '/v1/receivables/anticipate', $data);

        return PaymentResponse::create(
            success: isset($response['id']),
            transactionId: $response['id'] ?? '',
            status: $this->mapC6Status($response['status'] ?? 'PROCESSING')->value,
            amount: ($response['amount'] ?? 0) / 100,
            currency: Currency::BRL->value,
            rawResponse: $response
        );
    }
}