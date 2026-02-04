<?php

/**
 * Exemplo Avançado: Sistema Completo de Webhooks
 * 
 * Este exemplo demonstra como implementar um sistema robusto de webhooks
 * para receber notificações automáticas de pagamentos.
 * 
 * IMPORTANTE: Este arquivo deve ser acessível via URL pública
 * Ex: https://seusite.com/webhooks/payment-hub.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use IsraelNogueira\PaymentHub\Webhooks\WebhookHandler;
use IsraelNogueira\PaymentHub\Webhooks\WebhookConfig;
use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;
use IsraelNogueira\PaymentHub\Webhooks\Processors\PaymentWebhookProcessor;
use IsraelNogueira\PaymentHub\Webhooks\Processors\RefundWebhookProcessor;
use IsraelNogueira\PaymentHub\Webhooks\Processors\SubscriptionWebhookProcessor;
use IsraelNogueira\PaymentHub\Webhooks\Signatures\HmacSignatureValidator;
use IsraelNogueira\PaymentHub\Webhooks\Storage\DatabaseWebhookStorage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// ============================================
// 1. CONFIGURAÇÃO
// ============================================

// Logger para registrar atividades do webhook
$logger = new Logger('webhooks');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/webhooks.log', Logger::DEBUG));

// Configuração do webhook
$config = new WebhookConfig([
    'secret' => getenv('WEBHOOK_SECRET') ?: 'your-webhook-secret-key',
    'signature_header' => 'X-Hub-Signature-256',
    'timeout' => 30,
    'verify_ssl' => true,
    'allowed_ips' => [
        // IPs permitidos do gateway (opcional)
        // '192.168.1.100',
        // '10.0.0.0/8',
    ],
]);

// Validador de assinatura
$signatureValidator = new HmacSignatureValidator();

// Storage para idempotência e auditoria
$storage = new DatabaseWebhookStorage([
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_NAME') ?: 'payment_hub',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
]);

// Criar handler principal
$webhookHandler = new WebhookHandler(
    config: $config,
    signatureValidator: $signatureValidator,
    storage: $storage,
    logger: $logger
);

// ============================================
// 2. REGISTRAR PROCESSADORES
// ============================================

// Processador de pagamentos
$webhookHandler->addProcessor(new PaymentWebhookProcessor(
    onPaymentCompleted: function ($payment) use ($logger) {
        $logger->info('Payment completed', ['payment_id' => $payment['id']]);
        
        // Atualizar banco de dados
        updateOrderStatus($payment['id'], 'paid');
        
        // Enviar e-mail de confirmação
        sendConfirmationEmail($payment);
        
        // Liberar produto/serviço
        releaseProduct($payment['order_id']);
        
        // Gerar nota fiscal
        generateInvoice($payment);
    },
    onPaymentFailed: function ($payment) use ($logger) {
        $logger->warning('Payment failed', ['payment_id' => $payment['id']]);
        
        // Notificar cliente sobre falha
        notifyPaymentFailure($payment);
        
        // Atualizar status do pedido
        updateOrderStatus($payment['id'], 'payment_failed');
    }
));

// Processador de reembolsos
$webhookHandler->addProcessor(new RefundWebhookProcessor(
    onRefundCompleted: function ($refund) use ($logger) {
        $logger->info('Refund completed', ['refund_id' => $refund['id']]);
        
        // Atualizar estoque
        restoreStock($refund['order_id']);
        
        // Notificar cliente
        notifyRefundCompleted($refund);
        
        // Atualizar contabilidade
        updateAccounting($refund);
    }
));

// Processador de assinaturas
$webhookHandler->addProcessor(new SubscriptionWebhookProcessor(
    onSubscriptionCreated: function ($subscription) use ($logger) {
        $logger->info('Subscription created', ['subscription_id' => $subscription['id']]);
        activateSubscription($subscription);
    },
    onSubscriptionCancelled: function ($subscription) use ($logger) {
        $logger->info('Subscription cancelled', ['subscription_id' => $subscription['id']]);
        deactivateSubscription($subscription);
    },
    onSubscriptionRenewed: function ($subscription) use ($logger) {
        $logger->info('Subscription renewed', ['subscription_id' => $subscription['id']]);
        renewSubscriptionAccess($subscription);
    }
));

// ============================================
// 3. RECEBER E PROCESSAR WEBHOOK
// ============================================

try {
    // Validar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['error' => 'Method not allowed']));
    }
    
    // Verificar IP (se configurado)
    if (!empty($config->getAllowedIps())) {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$config->isIpAllowed($clientIp)) {
            $logger->warning('Webhook from unauthorized IP', ['ip' => $clientIp]);
            http_response_code(403);
            die(json_encode(['error' => 'Forbidden']));
        }
    }
    
    // Obter corpo da requisição
    $rawBody = file_get_contents('php://input');
    
    if (empty($rawBody)) {
        http_response_code(400);
        die(json_encode(['error' => 'Empty request body']));
    }
    
    // Obter headers
    $headers = getallheaders();
    
    // Criar payload do webhook
    $payload = WebhookPayload::fromRequest(
        rawBody: $rawBody,
        headers: $headers,
        gateway: $_GET['gateway'] ?? null
    );
    
    $logger->info('Webhook received', [
        'id' => $payload->getId(),
        'type' => $payload->getEventType(),
        'gateway' => $payload->getGateway(),
    ]);
    
    // Processar webhook
    $result = $webhookHandler->handle($payload);
    
    // Responder ao gateway
    if ($result->isSuccess()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'webhook_id' => $payload->getId(),
            'processed_at' => date('c'),
            'duration' => $result->getDuration(),
        ]);
        
        $logger->info('Webhook processed successfully', [
            'id' => $payload->getId(),
            'duration' => $result->getDuration(),
        ]);
        
    } else {
        // Determinar código de erro apropriado
        $statusCode = match ($result->getReason()) {
            'invalid_signature' => 401,
            'validation_failed' => 400,
            'no_processor_found' => 422,
            'already_processed' => 200, // Retorna 200 para idempotência
            default => 500,
        };
        
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $result->getReason(),
            'webhook_id' => $payload->getId(),
        ]);
        
        $logger->error('Webhook processing failed', [
            'id' => $payload->getId(),
            'reason' => $result->getReason(),
            'error' => $result->getError()?->getMessage(),
        ]);
    }
    
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid webhook payload',
        'message' => $e->getMessage(),
    ]);
    
    $logger->error('Invalid webhook payload', [
        'error' => $e->getMessage(),
    ]);
    
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
    ]);
    
    $logger->error('Webhook processing exception', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}

// ============================================
// 4. FUNÇÕES DE PROCESSAMENTO
// ============================================

/**
 * Atualiza o status do pedido
 */
function updateOrderStatus(string $paymentId, string $status): void
{
    // Exemplo de atualização no banco
    /*
    DB::table('orders')
        ->where('payment_id', $paymentId)
        ->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    */
}

/**
 * Envia e-mail de confirmação de pagamento
 */
function sendConfirmationEmail(array $payment): void
{
    // Exemplo de envio de e-mail
    /*
    $customer = DB::table('customers')->find($payment['customer_id']);
    
    Mail::to($customer->email)->send(new PaymentConfirmationEmail([
        'payment_id' => $payment['id'],
        'amount' => $payment['amount'],
        'order_number' => $payment['order_id'],
    ]));
    */
}

/**
 * Libera o produto ou serviço
 */
function releaseProduct(string $orderId): void
{
    // Exemplo de liberação de produto
    /*
    $order = Order::find($orderId);
    
    foreach ($order->items as $item) {
        if ($item->type === 'digital') {
            // Gerar link de download
            $downloadLink = DigitalProduct::generateDownloadLink($item->product_id);
            $order->download_links[] = $downloadLink;
        } elseif ($item->type === 'subscription') {
            // Ativar assinatura
            Subscription::activate($item->product_id, $order->customer_id);
        } elseif ($item->type === 'physical') {
            // Enviar para separação
            Warehouse::addToShippingQueue($orderId);
        }
    }
    
    $order->save();
    */
}

/**
 * Gera nota fiscal
 */
function generateInvoice(array $payment): void
{
    // Exemplo de geração de nota fiscal
    /*
    $invoice = Invoice::create([
        'payment_id' => $payment['id'],
        'customer_id' => $payment['customer_id'],
        'amount' => $payment['amount'],
        'items' => $payment['items'],
    ]);
    
    // Enviar para API da prefeitura/Sefaz
    InvoiceService::send($invoice);
    
    // Enviar PDF por e-mail
    Mail::to($payment['customer_email'])
        ->send(new InvoiceEmail($invoice));
    */
}

/**
 * Notifica cliente sobre falha no pagamento
 */
function notifyPaymentFailure(array $payment): void
{
    // Notificar sobre falha
    /*
    Notification::send([
        'type' => 'payment_failed',
        'customer_id' => $payment['customer_id'],
        'payment_id' => $payment['id'],
        'reason' => $payment['failure_reason'],
        'channels' => ['email', 'sms'],
    ]);
    */
}

/**
 * Restaura estoque após reembolso
 */
function restoreStock(string $orderId): void
{
    // Restaurar estoque
    /*
    $order = Order::find($orderId);
    
    foreach ($order->items as $item) {
        Product::increment($item->product_id, 'stock', $item->quantity);
    }
    */
}

/**
 * Notifica cliente sobre reembolso completado
 */
function notifyRefundCompleted(array $refund): void
{
    // Notificar sobre reembolso
    /*
    Mail::to($refund['customer_email'])
        ->send(new RefundCompletedEmail($refund));
    */
}

/**
 * Atualiza sistema de contabilidade
 */
function updateAccounting(array $refund): void
{
    // Registrar no sistema contábil
    /*
    AccountingEntry::create([
        'type' => 'refund',
        'amount' => -$refund['amount'],
        'payment_id' => $refund['payment_id'],
        'date' => now(),
    ]);
    */
}

/**
 * Ativa assinatura
 */
function activateSubscription(array $subscription): void
{
    // Ativar assinatura
    /*
    Subscription::create([
        'customer_id' => $subscription['customer_id'],
        'plan_id' => $subscription['plan_id'],
        'status' => 'active',
        'started_at' => now(),
        'next_billing_at' => now()->addMonth(),
    ]);
    
    // Dar acesso ao conteúdo/serviço
    SubscriptionService::grantAccess(
        $subscription['customer_id'],
        $subscription['plan_id']
    );
    */
}

/**
 * Desativa assinatura
 */
function deactivateSubscription(array $subscription): void
{
    // Desativar assinatura
    /*
    Subscription::where('id', $subscription['id'])
        ->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    
    // Remover acesso
    SubscriptionService::revokeAccess(
        $subscription['customer_id'],
        $subscription['plan_id']
    );
    */
}

/**
 * Renova acesso da assinatura
 */
function renewSubscriptionAccess(array $subscription): void
{
    // Renovar assinatura
    /*
    Subscription::where('id', $subscription['id'])
        ->update([
            'last_renewed_at' => now(),
            'next_billing_at' => now()->addMonth(),
        ]);
    */
}

// ============================================
// 5. DOCUMENTAÇÃO DE USO
// ============================================

/**
 * CONFIGURAÇÃO NO GATEWAY:
 * 
 * 1. Acesse o painel do seu gateway de pagamento
 * 2. Vá em Configurações > Webhooks
 * 3. Adicione a URL deste arquivo:
 *    https://seusite.com/webhooks/payment-hub.php
 * 4. Selecione os eventos que deseja receber
 * 5. Configure o secret (mesmo valor do .env)
 * 6. Teste a conexão
 * 
 * EVENTOS SUPORTADOS:
 * 
 * Pagamentos:
 * - payment.created
 * - payment.completed
 * - payment.failed
 * - payment.cancelled
 * 
 * Reembolsos:
 * - refund.created
 * - refund.completed
 * - refund.failed
 * 
 * Assinaturas:
 * - subscription.created
 * - subscription.activated
 * - subscription.cancelled
 * - subscription.renewed
 * - subscription.payment_failed
 * 
 * SEGURANÇA:
 * 
 * ✓ Validação de assinatura HMAC
 * ✓ Verificação de IP (opcional)
 * ✓ Idempotência (evita processamento duplo)
 * ✓ Timeout configurável
 * ✓ Logs detalhados
 * ✓ SSL/TLS recomendado
 * 
 * MONITORAMENTO:
 * 
 * - Verifique os logs em /logs/webhooks.log
 * - Configure alertas para falhas consecutivas
 * - Monitore tempo de processamento
 * - Acompanhe taxa de sucesso/falha
 * 
 * TESTANDO WEBHOOKS:
 * 
 * Use ferramentas como:
 * - ngrok (para desenvolvimento local)
 * - Postman
 * - curl
 * - Interface de teste do gateway
 */
