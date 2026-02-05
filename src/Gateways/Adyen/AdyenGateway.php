<?php

namespace IsraelNogueira\PaymentHub\Gateways\Adyen;

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

class AdyenGateway implements PaymentGatewayInterface
{
    private const PRODUCTION_URL = 'https://checkout-live.adyen.com';
    private const SANDBOX_URL = 'https://checkout-test.adyen.com';
    
    private string $apiKey;
    private string $merchantAccount;
    private string $baseUrl;
    private bool $sandbox;

    public function __construct(
        string $apiKey, 
        string $merchantAccount,
        bool $sandbox = false
    ) {
        $this->apiKey = $apiKey;
        $this->merchantAccount = $merchantAccount;
        $this->sandbox = $sandbox;
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    // ==================== MÉTODOS PRIVADOS ====================
    
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
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

        if ($httpCode >= 400) {
            $errorMessage = $decoded['message'] ?? $decoded['errorCode'] ?? 'Request failed';
            throw new GatewayException(
                $errorMessage,
                $httpCode,
                null,
                ['response' => $decoded]
            );
        }

        return $decoded ?? [];
    }

    private function mapAdyenStatus(string $resultCode): PaymentStatus
    {
        $statusMap = [
            'Authorised' => PaymentStatus::APPROVED,
            'Refused' => PaymentStatus::FAILED,
            'Error' => PaymentStatus::FAILED,
            'Cancelled' => PaymentStatus::CANCELLED,
            'Pending' => PaymentStatus::PENDING,
            'Received' => PaymentStatus::PENDING,
        ];

        return $statusMap[$resultCode] ?? PaymentStatus::PENDING;
    }

    private function getAmountInMinorUnits(float $amount, string $currency = 'BRL'): int
    {
        // Adyen usa minor units (centavos para BRL, EUR, USD)
        return (int)($amount * 100);
    }

    private function getAmountFromMinorUnits(int $value, string $currency = 'BRL'): float
    {
        return $value / 100;
    }

    // ==================== CLIENTES ====================
    
    public function createCustomer(CustomerRequest $request): CustomerResponse
    {
        throw new GatewayException('Adyen uses shopperReference instead of customer management. Pass shopper data in payment requests.');
    }

    public function updateCustomer(string $customerId, array $data): CustomerResponse
    {
        throw new GatewayException('Customer management not available in Adyen. Use shopperReference.');
    }

    public function getCustomer(string $customerId): CustomerResponse
    {
        throw new GatewayException('Customer management not available in Adyen.');
    }

    public function listCustomers(array $filters = []): array
    {
        return [];
    }

    // ==================== PIX ====================
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        $data = [
            'amount' => [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($request->money->amount()),
            ],
            'merchantAccount' => $this->merchantAccount,
            'reference' => 'PIX_' . uniqid(),
            'paymentMethod' => [
                'type' => 'pix',
            ],
            'shopperName' => [
                'firstName' => $request->customerName ?? 'Cliente',
            ],
            'shopperEmail' => $request->customerEmail?->value() ?? 'cliente@example.com',
            'countryCode' => 'BR',
        ];

        if ($request->customerDocument) {
            $data['shopperReference'] = $request->customerDocument;
        }

        $response = $this->request('POST', '/v70/payments', $data);

        $amount = $this->getAmountFromMinorUnits(
            $response['amount']['value'] ?? 0, 
            'BRL'
        );
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['pspReference'] ?? uniqid('adyen_'),
            status: $this->mapAdyenStatus($response['resultCode'] ?? 'Pending'),
            money: $money,
            message: $response['resultCode'] ?? 'Payment processed',
            rawResponse: $response,
            metadata: [
                'reference' => $data['reference'],
                'qr_code_data' => $response['action']['qrCodeData'] ?? null,
            ]
        );
    }

    public function getPixQrCode(string $transactionId): string
    {
        throw new GatewayException('QR Code returned in payment response metadata. Check metadata[qr_code_data]');
    }

    public function getPixCopyPaste(string $transactionId): string
    {
        throw new GatewayException('PIX copy-paste code returned in payment response metadata.');
    }

    // ==================== CARTÃO DE CRÉDITO ====================
    
    public function createCreditCardPayment(CreditCardPaymentRequest $request): PaymentResponse
    {
        $data = [
            'amount' => [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($request->money->amount()),
            ],
            'merchantAccount' => $this->merchantAccount,
            'reference' => 'CC_' . uniqid(),
            'paymentMethod' => [
                'type' => 'scheme',
                'encryptedCardNumber' => $request->cardNumber->value(),
                'encryptedExpiryMonth' => $request->cardExpiryMonth,
                'encryptedExpiryYear' => $request->cardExpiryYear,
                'encryptedSecurityCode' => $request->cardCvv,
                'holderName' => $request->cardHolderName,
            ],
            'shopperReference' => $request->customerDocument ?? uniqid('shopper_'),
            'shopperEmail' => $request->customerEmail?->value() ?? 'cliente@example.com',
            'shopperName' => [
                'firstName' => $request->customerName ?? 'Cliente',
            ],
            'countryCode' => 'BR',
        ];

        // Parcelamento
        if ($request->installments > 1) {
            $data['installments'] = [
                'value' => $request->installments,
            ];
        }

        // Captura
        if (!$request->capture) {
            $data['captureDelayHours'] = 0; // Manual capture
        }

        $response = $this->request('POST', '/v70/payments', $data);

        $amount = $this->getAmountFromMinorUnits(
            $response['amount']['value'] ?? 0,
            'BRL'
        );
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['pspReference'] ?? uniqid('adyen_'),
            status: $this->mapAdyenStatus($response['resultCode'] ?? 'Pending'),
            money: $money,
            message: $response['resultCode'] ?? 'Payment processed',
            rawResponse: $response,
            metadata: [
                'reference' => $data['reference'],
                'installments' => $request->installments,
            ]
        );
    }

    public function tokenizeCard(array $cardData): string
    {
        // Adyen usa client-side encryption
        throw new GatewayException('Adyen uses client-side encryption. Use Adyen Web SDK to encrypt card data.');
    }

    public function capturePreAuthorization(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $data = [
            'merchantAccount' => $this->merchantAccount,
            'originalReference' => $transactionId,
        ];

        if ($amount !== null) {
            $data['modificationAmount'] = [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($amount),
            ];
        }

        $response = $this->request('POST', '/v70/captures', $data);

        $capturedAmount = isset($response['amount']) 
            ? $this->getAmountFromMinorUnits($response['amount']['value'], 'BRL')
            : ($amount ?? 0);

        $money = Money::from($capturedAmount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['pspReference'] ?? $transactionId,
            status: PaymentStatus::APPROVED,
            money: $money,
            message: 'Capture successful',
            rawResponse: $response
        );
    }

    public function cancelPreAuthorization(string $transactionId): PaymentResponse
    {
        $data = [
            'merchantAccount' => $this->merchantAccount,
            'originalReference' => $transactionId,
        ];

        $response = $this->request('POST', '/v70/cancels', $data);

        return new PaymentResponse(
            success: true,
            transactionId: $response['pspReference'] ?? $transactionId,
            status: PaymentStatus::CANCELLED,
            money: Money::from(0, Currency::BRL),
            message: 'Authorization cancelled',
            rawResponse: $response
        );
    }

    // ==================== CARTÃO DE DÉBITO ====================
    
    public function createDebitCardPayment(DebitCardPaymentRequest $request): PaymentResponse
    {
        $data = [
            'amount' => [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($request->money->amount()),
            ],
            'merchantAccount' => $this->merchantAccount,
            'reference' => 'DC_' . uniqid(),
            'paymentMethod' => [
                'type' => 'scheme',
                'encryptedCardNumber' => $request->cardNumber->value(),
                'encryptedExpiryMonth' => $request->cardExpiryMonth,
                'encryptedExpiryYear' => $request->cardExpiryYear,
                'encryptedSecurityCode' => $request->cardCvv,
                'holderName' => $request->cardHolderName,
            ],
            'shopperReference' => $request->customerDocument ?? uniqid('shopper_'),
            'shopperEmail' => $request->customerEmail?->value() ?? 'cliente@example.com',
            'countryCode' => 'BR',
        ];

        $response = $this->request('POST', '/v70/payments', $data);

        $amount = $this->getAmountFromMinorUnits(
            $response['amount']['value'] ?? 0,
            'BRL'
        );
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['pspReference'] ?? uniqid('adyen_'),
            status: $this->mapAdyenStatus($response['resultCode'] ?? 'Pending'),
            money: $money,
            message: $response['resultCode'] ?? 'Payment processed',
            rawResponse: $response
        );
    }

    // ==================== BOLETO ====================
    
    public function createBoleto(BoletoPaymentRequest $request): PaymentResponse
    {
        $data = [
            'amount' => [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($request->money->amount()),
            ],
            'merchantAccount' => $this->merchantAccount,
            'reference' => 'BOL_' . uniqid(),
            'paymentMethod' => [
                'type' => 'boletobancario',
            ],
            'shopperName' => [
                'firstName' => explode(' ', $request->customerName)[0],
                'lastName' => explode(' ', $request->customerName)[1] ?? '',
            ],
            'shopperEmail' => $request->customerEmail?->value() ?? 'cliente@example.com',
            'countryCode' => 'BR',
            'deliveryDate' => $request->dueDate ?? date('Y-m-d', strtotime('+3 days')),
        ];

        if ($request->customerDocument) {
            $data['socialSecurityNumber'] = $request->customerDocument;
        }

        $response = $this->request('POST', '/v70/payments', $data);

        $amount = $this->getAmountFromMinorUnits(
            $response['amount']['value'] ?? 0,
            'BRL'
        );
        $money = Money::from($amount, Currency::BRL);

        return new PaymentResponse(
            success: true,
            transactionId: $response['pspReference'] ?? uniqid('adyen_'),
            status: $this->mapAdyenStatus($response['resultCode'] ?? 'Pending'),
            money: $money,
            message: $response['resultCode'] ?? 'Boleto created',
            rawResponse: $response,
            metadata: [
                'barcode' => $response['action']['barcodeData'] ?? null,
                'pdf_url' => $response['action']['url'] ?? null,
            ]
        );
    }

    public function getBoletoUrl(string $transactionId): string
    {
        throw new GatewayException('Boleto URL returned in payment response metadata. Check metadata[pdf_url]');
    }

    public function cancelBoleto(string $transactionId): PaymentResponse
    {
        return $this->cancelPreAuthorization($transactionId);
    }

    // ==================== ASSINATURAS/RECORRÊNCIA ====================
    
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        throw new GatewayException('Subscriptions managed via Adyen Recurring module. Use stored payment methods.');
    }

    public function cancelSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Manage subscriptions via Adyen Customer Area or Recurring API.');
    }

    public function suspendSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Subscription suspension not available via API.');
    }

    public function reactivateSubscription(string $subscriptionId): SubscriptionResponse
    {
        throw new GatewayException('Subscription reactivation not available via API.');
    }

    public function updateSubscription(string $subscriptionId, array $data): SubscriptionResponse
    {
        throw new GatewayException('Update subscriptions via Adyen Customer Area.');
    }

    // ==================== TRANSAÇÕES ====================
    
    public function getTransactionStatus(string $transactionId): TransactionStatusResponse
    {
        // Adyen não tem endpoint direto de consulta
        // Status vem via webhooks
        throw new GatewayException('Transaction status available via webhooks. Store status from webhook notifications.');
    }

    public function listTransactions(array $filters = []): array
    {
        throw new GatewayException('Transaction listing available via Adyen Customer Area or Report API.');
    }

    // ==================== ESTORNOS E CHARGEBACKS ====================
    
    public function refund(RefundRequest $request): RefundResponse
    {
        $data = [
            'merchantAccount' => $this->merchantAccount,
            'originalReference' => $request->transactionId,
        ];

        $response = $this->request('POST', '/v70/refunds', $data);

        $amount = isset($response['amount'])
            ? $this->getAmountFromMinorUnits($response['amount']['value'], 'BRL')
            : 0;

        $money = Money::from($amount, Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: $response['pspReference'] ?? uniqid('refund_'),
            transactionId: $request->transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            rawResponse: $response
        );
    }

    public function partialRefund(string $transactionId, float $amount): RefundResponse
    {
        $data = [
            'merchantAccount' => $this->merchantAccount,
            'originalReference' => $transactionId,
            'modificationAmount' => [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($amount),
            ],
        ];

        $response = $this->request('POST', '/v70/refunds', $data);

        $refundAmount = $this->getAmountFromMinorUnits(
            $response['amount']['value'] ?? 0,
            'BRL'
        );
        $money = Money::from($refundAmount, Currency::BRL);

        return new RefundResponse(
            success: true,
            refundId: $response['pspReference'] ?? uniqid('refund_'),
            transactionId: $transactionId,
            money: $money,
            status: PaymentStatus::REFUNDED,
            rawResponse: $response
        );
    }

    public function getChargebacks(array $filters = []): array
    {
        throw new GatewayException('Chargeback data available via Adyen Customer Area or Disputes API.');
    }

    public function disputeChargeback(string $chargebackId, array $evidence): PaymentResponse
    {
        throw new GatewayException('Dispute chargebacks via Adyen Customer Area.');
    }

    // ==================== SPLIT DE PAGAMENTO ====================
    
    public function createSplitPayment(SplitPaymentRequest $request): PaymentResponse
    {
        throw new GatewayException('Split payments available via Adyen for Platforms. Contact Adyen sales.');
    }

    // ==================== SUB-CONTAS ====================
    
    public function createSubAccount(SubAccountRequest $request): SubAccountResponse
    {
        throw new GatewayException('Sub-accounts (Balance Accounts) available via Adyen for Platforms.');
    }

    public function updateSubAccount(string $subAccountId, array $data): SubAccountResponse
    {
        throw new GatewayException('Manage via Adyen for Platforms API.');
    }

    public function getSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Manage via Adyen for Platforms API.');
    }

    public function activateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Manage via Adyen for Platforms API.');
    }

    public function deactivateSubAccount(string $subAccountId): SubAccountResponse
    {
        throw new GatewayException('Manage via Adyen for Platforms API.');
    }

    // ==================== WALLETS ====================
    
    public function createWallet(WalletRequest $request): WalletResponse
    {
        throw new GatewayException('Wallets available via Adyen for Platforms.');
    }

    public function addBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Balance management via Adyen for Platforms.');
    }

    public function deductBalance(string $walletId, float $amount): WalletResponse
    {
        throw new GatewayException('Balance management via Adyen for Platforms.');
    }

    public function getWalletBalance(string $walletId): BalanceResponse
    {
        throw new GatewayException('Balance inquiry via Adyen for Platforms.');
    }

    public function transferBetweenWallets(string $fromWalletId, string $toWalletId, float $amount): TransferResponse
    {
        throw new GatewayException('Transfers via Adyen for Platforms.');
    }

    // ==================== ESCROW (CUSTÓDIA) ====================
    
    public function holdInEscrow(EscrowRequest $request): EscrowResponse
    {
        throw new GatewayException('Use manual capture for escrow-like behavior.');
    }

    public function releaseEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Use capture endpoint to release held funds.');
    }

    public function partialReleaseEscrow(string $escrowId, float $amount): EscrowResponse
    {
        throw new GatewayException('Use partial capture for partial release.');
    }

    public function cancelEscrow(string $escrowId): EscrowResponse
    {
        throw new GatewayException('Use cancel endpoint to release held funds.');
    }

    // ==================== TRANSFERÊNCIAS E SAQUES ====================
    
    public function transfer(TransferRequest $request): TransferResponse
    {
        throw new GatewayException('Transfers managed via Adyen for Platforms.');
    }

    public function scheduleTransfer(TransferRequest $request, string $date): TransferResponse
    {
        throw new GatewayException('Scheduled transfers not available.');
    }

    public function cancelScheduledTransfer(string $transferId): TransferResponse
    {
        throw new GatewayException('Transfer cancellation not available.');
    }

    // ==================== LINK DE PAGAMENTO ====================
    
    public function createPaymentLink(PaymentLinkRequest $request): PaymentLinkResponse
    {
        $data = [
            'amount' => [
                'currency' => 'BRL',
                'value' => $this->getAmountInMinorUnits($request->money->amount()),
            ],
            'merchantAccount' => $this->merchantAccount,
            'reference' => 'LINK_' . uniqid(),
            'description' => $request->description ?? 'Payment Link',
            'countryCode' => 'BR',
        ];

        if ($request->expiresAt) {
            $data['expiresAt'] = $request->expiresAt;
        }

        $response = $this->request('POST', '/v70/paymentLinks', $data);

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'] ?? uniqid('link_'),
            url: $response['url'] ?? '',
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }

    public function getPaymentLink(string $linkId): PaymentLinkResponse
    {
        $response = $this->request('GET', "/v70/paymentLinks/{$linkId}");

        return new PaymentLinkResponse(
            success: true,
            linkId: $response['id'],
            url: $response['url'] ?? '',
            status: $response['status'] ?? 'active',
            rawResponse: $response
        );
    }

    public function expirePaymentLink(string $linkId): PaymentLinkResponse
    {
        $data = ['status' => 'expired'];
        $response = $this->request('PATCH', "/v70/paymentLinks/{$linkId}", $data);

        return new PaymentLinkResponse(
            success: true,
            linkId: $linkId,
            url: '',
            status: 'expired',
            rawResponse: $response
        );
    }

    // ==================== ANTIFRAUDE ====================
    
    public function analyzeTransaction(string $transactionId): array
    {
        throw new GatewayException('Fraud analysis automatic. Check risk scores in payment response.');
    }

    public function addToBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management via Adyen Customer Area.');
    }

    public function removeFromBlacklist(string $identifier, string $type): bool
    {
        throw new GatewayException('Blacklist management via Adyen Customer Area.');
    }

    // ==================== WEBHOOKS ====================
    
    public function registerWebhook(string $url, array $events): array
    {
        throw new GatewayException('Configure webhooks via Adyen Customer Area → Developers → Webhooks.');
    }

    public function listWebhooks(): array
    {
        return [];
    }

    public function deleteWebhook(string $webhookId): bool
    {
        throw new GatewayException('Manage webhooks via Adyen Customer Area.');
    }

    // ==================== SALDO E CONCILIAÇÃO ====================
    
    public function getBalance(): BalanceResponse
    {
        throw new GatewayException('Balance available via Adyen for Platforms or Accounting Reports.');
    }

    public function getSettlementSchedule(array $filters = []): array
    {
        throw new GatewayException('Settlement schedule via Adyen Customer Area or Report API.');
    }

    public function anticipateReceivables(array $transactionIds): PaymentResponse
    {
        throw new GatewayException('Receivables anticipation not available.');
    }
}