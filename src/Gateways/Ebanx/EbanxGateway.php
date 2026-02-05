<?php

namespace IsraelNogueira\PaymentHub\Gateways\Ebanx;

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

class EbanxGateway implements PaymentGatewayInterface
{
    private const PRODUCTION_URL = 'https://api.ebanx.com';
    private const SANDBOX_URL = 'https://sandbox.ebanx.com';
    
    private string $integrationKey;
    private string $publicKey;
    private string $baseUrl;
    private bool $sandbox;
    private string $country;

    public function __construct(
        string $integrationKey,
        string $publicKey = '',
        bool $sandbox = false,
        string $country = 'br' // br, mx, co, cl, ar, pe, ec
    ) {
        $this->integrationKey = $integrationKey;
        $this->publicKey = $publicKey;
        $this->sandbox = $sandbox;
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        $this->country = strtolower($country);
    }

    // ==================== MÉTODOS PRIVADOS ====================

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        // EBANX usa integration_key no body
        if ($method === 'POST') {
            $data['integration_key'] = $this->integrationKey;
        } else {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'integration_key=' . $this->integrationKey;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

        if ($httpCode >= 400 || ($decoded['status'] ?? '') === 'ERROR') {
            $errorMessage = $decoded['status_message'] ?? $decoded['message'] ?? 'Request failed';
            throw new GatewayException(
                $errorMessage,
                $httpCode,
                null,
                ['response' => $decoded]
            );
        }

        return $decoded ?? [];
    }

    private function mapEbanxStatus(string $status): PaymentStatus
    {
        $statusMap = [
            'CO' => PaymentStatus::PAID, // Confirmed
            'CA' => PaymentStatus::CANCELLED, // Cancelled
            'PE' => PaymentStatus::PENDING, // Pending
            'OP' => PaymentStatus::PROCESSING, // Open (waiting payment)
            'ND' => PaymentStatus::FAILED, // Not Declined
        ];

        return $statusMap[$status] ?? PaymentStatus::PENDING;
    }

    // ==================== CLIENTES ====================

    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        // EBANX não tem endpoint específico de clientes
        // Dados do cliente são enviados junto com o pagamento
        throw new GatewayException('EBANX does not have a dedicated customer management API. Customer data is sent with payment requests.');
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        throw new GatewayException('Customer management not available in EBANX');
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        throw new GatewayException('Customer management not available in EBANX');
    }

    public function listCustomers(array $filters = []): array
    {
        return [];
    }

    // ==================== PIX ====================

    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $data = [
            'operation' => 'request',
            'mode' => 'full',
            'payment' => [
                'amount_total' => number_format($request->money->amount(), 2, '.', ''),
                'currency_code' => 'BRL',
                'country' => 'br',
                'merchant_payment_code' => 'PIX_' . uniqid(),
                'payment_type_code' => 'pix',
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail?->value() ?? 'cliente@email.com',
            ],
        ];

        if ($request->customerDocument) {
            $document = preg_replace('/\D/', '', $request->customerDocument->value());
            $data['payment']['document'] = $document;
        }

        if ($request->metadata) {
            $data['payment']['metadata'] = $request->metadata;
        }

        $response = $this->request('POST', '/ws/direct', $data);

        $payment = $response['payment'] ?? [];
        $money = Money::from((float)($payment['amount_br'] ?? 0), Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $payment['hash'] ?? uniqid('ebanx_'),
            status: $this->mapEbanxStatus($payment['status'] ?? 'PE'),
            money: $money,
            message: $response['status_message'] ?? 'PIX payment created',
            rawResponse: $response,
            metadata: [
                'pix_code' => $payment['pix_code'] ?? null,
                'pix_qrcode' => $payment['pix_qrcode'] ?? null,
                'pix_emv' => $payment['pix_emv'] ?? null,
            ]
        );
    }

    public function getPixQrCode(string $transactionId): string
    {
        $status = $this->getTransactionStatus($transactionId);
        return $status->rawResponse['payment']['pix_qrcode'] ?? '';
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        $status = $this->getTransactionStatus($transactionId);
        return $status->rawResponse['payment']['pix_emv'] ?? '';
    }

    // ==================== CARTÃO DE CRÉDITO ====================

    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $data = [
            'operation' => 'request',
            'mode' => 'full',
            'payment' => [
                'amount_total' => number_format($request->money->amount(), 2, '.', ''),
                'currency_code' => 'BRL',
                'country' => 'br',
                'merchant_payment_code' => 'CC_' . uniqid(),
                'payment_type_code' => 'creditcard',
                'instalments' => $request->installments,
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail?->value() ?? 'cliente@email.com',
                'creditcard' => [
                    'card_number' => preg_replace('/\D/', '', $request->cardNumber->value()),
                    'card_name' => $request->cardHolderName,
                    'card_due_date' => $request->cardExpiryMonth . '/' . $request->cardExpiryYear,
                    'card_cvv' => $request->cardCvv,
                ],
            ],
        ];

        if ($request->customerDocument) {
            $document = preg_replace('/\D/', '', $request->customerDocument);
            $data['payment']['document'] = $document;
        }

        // Auto capture
        if (isset($request->capture) && !$request->capture) {
            $data['payment']['auto_capture'] = false;
        }

        if ($request->metadata) {
            $data['payment']['metadata'] = $request->metadata;
        }

        $response = $this->request('POST', '/ws/direct', $data);

        $payment = $response['payment'] ?? [];
        $money = Money::from((float)($payment['amount_br'] ?? 0), Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $payment['hash'] ?? uniqid('ebanx_'),
            status: $this->mapEbanxStatus($payment['status'] ?? 'PE'),
            money: $money,
            message: $response['status_message'] ?? 'Credit card payment created',
            rawResponse: $response,
            metadata: [
                'installments' => $request->installments,
                'merchant_payment_code' => $data['payment']['merchant_payment_code'],
            ]
        );
    }

    public function tokenizeCard(array $cardData): string
    {
        $data = [
            'public_integration_key' => $this->publicKey,
            'card_number' => preg_replace('/\D/', '', $cardData['number']),
            'card_name' => $cardData['holder_name'],
            'card_due_date' => $cardData['expiry_month'] . '/' . $cardData['expiry_year'],
            'card_cvv' => $cardData['cvv'],
        ];

        $response = $this->request('POST', '/ws/token', $data);
        return $response['token'] ?? '';
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $data = [
            'hash' => $transactionId,
        ];

        if ($amount !== null) {
            $data['amount'] = number_format($amount, 2, '.', '');
        }

        $response = $this->request('POST', '/ws/capture', $data);

        $payment = $response['payment'] ?? [];
        $money = Money::from((float)($payment['amount_br'] ?? 0), Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $payment['hash'] ?? $transactionId,
            status: PaymentStatus::PAID,
            money: $money,
            message: 'Capture successful',
            rawResponse: $response
        );
    }

    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $data = [
            'hash' => $transactionId,
        ];

        $response = $this->request('POST', '/ws/cancel', $data);

        return new PaymentResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $transactionId,
            status: PaymentStatus::CANCELLED,
            money: Money::from(0, Currency::BRL),
            message: 'Authorization cancelled',
            rawResponse: $response
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================

    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        // EBANX usa "Online Debit" para débito
        $data = [
            'operation' => 'request',
            'mode' => 'full',
            'payment' => [
                'amount_total' => number_format($request->money->amount(), 2, '.', ''),
                'currency_code' => 'BRL',
                'country' => 'br',
                'merchant_payment_code' => 'DC_' . uniqid(),
                'payment_type_code' => 'banktransfer', // Débito online
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail?->value() ?? 'cliente@email.com',
            ],
        ];

        if ($request->customerDocument) {
            $document = preg_replace('/\D/', '', $request->customerDocument);
            $data['payment']['document'] = $document;
        }

        $response = $this->request('POST', '/ws/direct', $data);

        $payment = $response['payment'] ?? [];
        $money = Money::from((float)($payment['amount_br'] ?? 0), Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $payment['hash'] ?? uniqid('ebanx_'),
            status: $this->mapEbanxStatus($payment['status'] ?? 'PE'),
            money: $money,
            message: $response['status_message'] ?? 'Debit payment created',
            rawResponse: $response,
            metadata: [
                'redirect_url' => $payment['redirect_url'] ?? null,
            ]
        );
    }

    // ==================== BOLETO ====================

    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $data = [
            'operation' => 'request',
            'mode' => 'full',
            'payment' => [
                'amount_total' => number_format($request->money->amount(), 2, '.', ''),
                'currency_code' => 'BRL',
                'country' => 'br',
                'merchant_payment_code' => 'BOL_' . uniqid(),
                'payment_type_code' => 'boleto',
                'name' => $request->customerName,
                'email' => $request->customerEmail?->value() ?? 'cliente@email.com',
            ],
        ];

        if ($request->customerDocument) {
            $document = preg_replace('/\D/', '', $request->customerDocument->value());
            $data['payment']['document'] = $document;
        }

        if ($request->dueDate) {
            $data['payment']['due_date'] = date('d/m/Y', strtotime($request->dueDate));
        }

        if ($request->address) {
            $data['payment']['address'] = $request->address['street'] ?? '';
            $data['payment']['city'] = $request->address['city'] ?? '';
            $data['payment']['state'] = $request->address['state'] ?? '';
            $data['payment']['zipcode'] = preg_replace('/\D/', '', $request->address['zipcode'] ?? '');
        }

        if ($request->metadata) {
            $data['payment']['metadata'] = $request->metadata;
        }

        $response = $this->request('POST', '/ws/direct', $data);

        $payment = $response['payment'] ?? [];
        $money = Money::from((float)($payment['amount_br'] ?? 0), Currency::BRL);

        return new PaymentResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $payment['hash'] ?? uniqid('ebanx_'),
            status: $this->mapEbanxStatus($payment['status'] ?? 'PE'),
            money: $money,
            message: $response['status_message'] ?? 'Boleto created',
            rawResponse: $response,
            metadata: [
                'boleto_url' => $payment['boleto_url'] ?? null,
                'boleto_barcode' => $payment['boleto_barcode'] ?? null,
                'due_date' => $payment['due_date'] ?? null,
            ]
        );
    }

    public function getBoletoUrl(string $transactionId): string
    {
        $status = $this->getTransactionStatus($transactionId);
        return $status->rawResponse['payment']['boleto_url'] ?? '';
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        return $this->cancelPreAuthorization($transactionId);
    }

    // ==================== ASSINATURAS/RECORRÊNCIA ====================

    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        // EBANX suporta recorrência via token de cartão
        $data = [
            'operation' => 'request',
            'mode' => 'full',
            'payment' => [
                'amount_total' => number_format($request->money->amount(), 2, '.', ''),
                'currency_code' => 'BRL',
                'country' => 'br',
                'merchant_payment_code' => 'SUB_' . uniqid(),
                'payment_type_code' => 'creditcard',
                'name' => $request->customerName ?? 'Cliente',
                'email' => $request->customerEmail ?? 'cliente@email.com',
                'recurrent' => true,
            ],
        ];

        if ($request->cardToken) {
            $data['payment']['creditcard'] = [
                'token' => $request->cardToken,
            ];
        }

        if ($request->metadata) {
            $data['payment']['metadata'] = $request->metadata;
        }

        $response = $this->request('POST', '/ws/direct', $data);

        $payment = $response['payment'] ?? [];

        return new SubscriptionResponse(
            success: $response['status'] === 'SUCCESS',
            subscriptionId: $payment['hash'] ?? uniqid('sub_'),
            status: $payment['status'] ?? 'active',
            message: 'Subscription created successfully',
            rawResponse: $response
        );
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        $response = $this->cancelPreAuthorization($subscriptionId);

        return new SubscriptionResponse(
            success: true,
            subscriptionId: $subscriptionId,
            status: 'canceled',
            message: 'Subscription cancelled successfully',
            rawResponse: $response->rawResponse
        );
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Subscription suspension not supported - use cancelSubscription');
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Subscription reactivation not supported - create new subscription');
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        throw new GatewayException('Subscription update not supported via API');
    }

    // ==================== TRANSAÇÕES ====================

    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        $url = "/ws/query?hash={$transactionId}";
        $response = $this->request('GET', $url);

        $payment = $response['payment'] ?? [];
        $money = Money::from((float)($payment['amount_br'] ?? 0), Currency::BRL);

        return new TransactionStatusResponse(
            success: $response['status'] === 'SUCCESS',
            transactionId: $payment['hash'] ?? $transactionId,
            status: $this->mapEbanxStatus($payment['status'] ?? 'PE'),
            money: $money,
            rawResponse: $response
        );
    }

    public function listTransactions(array $filters = []): array
    {
        // EBANX não tem endpoint de listagem
        // Use o dashboard ou relatórios
        return [];
    }

    // ==================== ESTORNOS E CHARGEBACKS ====================

    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [
            'operation' => 'request',
            'hash' => $request->transactionId,
        ];

        if ($request->metadata) {
            $data['description'] = $request->metadata['reason'] ?? 'Refund requested';
        }

        $response = $this->request('POST', '/ws/refund', $data);

        $payment = $response['payment'] ?? [];
        $refundAmount = (float)($payment['amount_refunded'] ?? 0);
        $money = Money::from($refundAmount, Currency::BRL);

        return new RefundResponse(
            success: $response['status'] === 'SUCCESS',
            refundId: $payment['hash'] ?? uniqid('refund_'),
            transactionId: $request->transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: $response['status_message'] ?? 'Refund successful',
            rawResponse: $response
        );
    }

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $data = [
            'operation' => 'request',
            'hash' => $transactionId,
            'amount' => number_format($amount, 2, '.', ''),
        ];

        $response = $this->request('POST', '/ws/refund', $data);

        $payment = $response['payment'] ?? [];
        $money = Money::from($amount, Currency::BRL);

        return new RefundResponse(
            success: $response['status'] === 'SUCCESS',
            refundId: $payment['hash'] ?? uniqid('refund_'),
            transactionId: $transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            message: 'Partial refund successful',
            rawResponse: $response
        );
    }

    public function getChargebacks(array $filters = []): array
    {
        // Chargebacks disponíveis via dashboard
        return [];
    }

    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        throw new GatewayException('Chargeback disputes must be managed via EBANX Dashboard');
    }

    // ==================== SPLIT DE PAGAMENTO ====================

    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Split payments available via EBANX Marketplace solution. Contact EBANX sales.');
    }

    // ==================== SUB-CONTAS ====================

    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts available via EBANX Marketplace. Contact EBANX sales.');
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts available via EBANX Marketplace');
    }

    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts available via EBANX Marketplace');
    }

    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts available via EBANX Marketplace');
    }

    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts available via EBANX Marketplace');
    }

    // ==================== WALLETS ====================

    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('Wallets not available in EBANX');
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not available in EBANX');
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Wallets not available in EBANX');
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Wallets not available in EBANX');
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Wallets not available in EBANX');
    }

    // ==================== ESCROW (CUSTÓDIA) ====================

    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        throw new GatewayException('Use pre-authorization (auto_capture=false) for escrow-like behavior');
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Use capturePreAuthorization to release funds');
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Use capturePreAuthorization with amount parameter');
    }

    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Use cancelPreAuthorization to cancel held funds');
    }

    // ==================== TRANSFERÊNCIAS E SAQUES ====================

    public function transfer(TransferRequest $request): TransferResponse
    {
        throw new GatewayException('Transfers managed via EBANX Dashboard');
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        throw new GatewayException('Scheduled transfers not available');
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        throw new GatewayException('Transfer cancellation not available');
    }

    // ==================== LINK DE PAGAMENTO ====================

    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        throw new GatewayException('Payment links available via EBANX Checkout - use Dashboard');
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        throw new GatewayException('Payment links not available via API');
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        throw new GatewayException('Payment links not available via API');
    }

    // ==================== ANTIFRAUDE ====================

    public function analyzeTransaction(string $transactionId): array
    {
        $status = $this->getTransactionStatus($transactionId);
        $payment = $status->rawResponse['payment'] ?? [];
        
        return [
            'fraud_status' => $payment['fraud_status'] ?? 'unknown',
            'fraud_analysis' => $payment['fraud_analysis'] ?? null,
        ];
    }

    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management available via EBANX Dashboard');
    }

    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management available via EBANX Dashboard');
    }

    // ==================== WEBHOOKS ====================

    public function registerWebhook(string $url, array $events): array
    {
        throw new GatewayException('Configure webhooks via EBANX Dashboard → Settings → Notifications');
    }

    public function listWebhooks(): array
    {
        return [];
    }

    public function deleteWebhook(string $webhookId): bool
    {
        throw new GatewayException('Manage webhooks via EBANX Dashboard');
    }

    // ==================== SALDO E CONCILIAÇÃO ====================

    public function getBalance(): BalanceResponse
    {
        throw new GatewayException('Balance available via EBANX Dashboard → Reports');
    }

    public function getSettlementSchedule(array $filters = []): array
    {
        throw new GatewayException('Settlement schedule available via EBANX Dashboard');
    }

    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        throw new GatewayException('Receivables anticipation not available');
    }
}