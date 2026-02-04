<?php

/**
 * Exemplo 1: Pagamento Básico com Cartão de Crédito
 * 
 * Este exemplo demonstra como processar um pagamento simples
 * com cartão de crédito usando o Payment Hub.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Factories\PaymentHubFactory;
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Events\PaymentCreated;
use IsraelNogueira\PaymentHub\Events\PaymentCompleted;
use IsraelNogueira\PaymentHub\Events\PaymentFailed;

// ============================================
// 1. CONFIGURAÇÃO INICIAL
// ============================================

// Criar instância do Payment Hub usando Factory
$paymentHub = PaymentHubFactory::create([
    'gateway' => 'fake_bank', // Usando gateway de teste
    'environment' => 'sandbox',
    'api_key' => 'test_key_123',
]);

// ============================================
// 2. CONFIGURAR LISTENERS DE EVENTOS
// ============================================

// Listener para quando o pagamento é criado
$paymentHub->on(PaymentCreated::class, function (PaymentCreated $event) {
    echo "\n✓ Pagamento criado com sucesso!\n";
    echo "  ID: {$event->getPayment()->id}\n";
    echo "  Valor: {$event->getPayment()->amount->format()}\n";
});

// Listener para quando o pagamento é aprovado
$paymentHub->on(PaymentCompleted::class, function (PaymentCompleted $event) {
    echo "\n✓ Pagamento aprovado!\n";
    echo "  ID: {$event->getPayment()->id}\n";
    echo "  Status: {$event->getPayment()->status->value}\n";
    echo "  Transaction ID: {$event->getPayment()->transactionId}\n";
    
    // Aqui você pode:
    // - Enviar e-mail de confirmação
    // - Atualizar banco de dados
    // - Liberar produto/serviço
    // - Gerar nota fiscal
});

// Listener para quando o pagamento falha
$paymentHub->on(PaymentFailed::class, function (PaymentFailed $event) {
    echo "\n✗ Pagamento falhou!\n";
    echo "  ID: {$event->getPayment()->id}\n";
    echo "  Motivo: {$event->getReason()}\n";
    
    // Aqui você pode:
    // - Notificar o usuário
    // - Logar o erro
    // - Tentar método alternativo
});

// ============================================
// 3. CRIAR REQUISIÇÃO DE PAGAMENTO
// ============================================

echo "\n" . str_repeat("=", 50) . "\n";
echo "PROCESSANDO PAGAMENTO COM CARTÃO DE CRÉDITO\n";
echo str_repeat("=", 50) . "\n";

try {
    // Criar objeto Money para o valor
    $amount = Money::fromCents(15000, Currency::BRL); // R$ 150,00
    
    // Validar número do cartão
    $cardNumber = new CardNumber('4111111111111111'); // Visa de teste
    
    // Criar requisição de pagamento
    $paymentRequest = new CreditCardPaymentRequest(
        amount: $amount,
        cardNumber: $cardNumber,
        cardHolderName: 'João Silva',
        expiryMonth: '12',
        expiryYear: '2026',
        cvv: '123',
        installments: 3, // Parcelado em 3x
        description: 'Compra na Loja Virtual - Pedido #12345',
        customerId: 'customer_abc123',
        customerEmail: 'joao.silva@example.com',
        customerDocument: '12345678900'
    );
    
    // ============================================
    // 4. PROCESSAR PAGAMENTO
    // ============================================
    
    echo "\nInformações do Pagamento:\n";
    echo "- Titular: {$paymentRequest->cardHolderName}\n";
    echo "- Valor Total: {$amount->format()}\n";
    echo "- Parcelas: {$paymentRequest->installments}x de ";
    echo "{$paymentRequest->getInstallmentAmount()->format()}\n";
    echo "- Cartão: {$cardNumber->getMasked()}\n";
    echo "\nProcessando...\n";
    
    // Processar o pagamento
    $response = $paymentHub->processPayment($paymentRequest);
    
    // ============================================
    // 5. TRATAR RESPOSTA
    // ============================================
    
    echo "\n" . str_repeat("-", 50) . "\n";
    echo "RESULTADO DO PROCESSAMENTO\n";
    echo str_repeat("-", 50) . "\n";
    
    if ($response->isSuccessful()) {
        echo "\n✓ PAGAMENTO APROVADO\n\n";
        echo "Detalhes:\n";
        echo "  • ID do Pagamento: {$response->id}\n";
        echo "  • Transaction ID: {$response->transactionId}\n";
        echo "  • Status: {$response->status->value}\n";
        echo "  • Valor: {$response->amount->format()}\n";
        echo "  • Método: {$response->paymentMethod->value}\n";
        echo "  • Data: {$response->createdAt->format('d/m/Y H:i:s')}\n";
        
        if ($response->authorizationCode) {
            echo "  • Código de Autorização: {$response->authorizationCode}\n";
        }
        
        if ($response->installments > 1) {
            echo "  • Parcelas: {$response->installments}x\n";
        }
        
        // Informações do cartão (mascarado)
        if ($response->cardBrand) {
            echo "\nCartão:\n";
            echo "  • Bandeira: {$response->cardBrand}\n";
            echo "  • Últimos 4 dígitos: {$response->cardLastFour}\n";
        }
        
    } else {
        echo "\n✗ PAGAMENTO RECUSADO\n\n";
        echo "Detalhes:\n";
        echo "  • Status: {$response->status->value}\n";
        echo "  • Mensagem: {$response->message}\n";
        
        if ($response->errorCode) {
            echo "  • Código de Erro: {$response->errorCode}\n";
        }
        
        // Sugestões baseadas no erro
        echo "\nSugestões:\n";
        
        switch ($response->status) {
            case PaymentStatus::DECLINED:
                echo "  • Verifique o limite disponível\n";
                echo "  • Tente outro cartão\n";
                echo "  • Entre em contato com o banco\n";
                break;
                
            case PaymentStatus::INSUFFICIENT_FUNDS:
                echo "  • Saldo insuficiente\n";
                echo "  • Tente com valor menor\n";
                echo "  • Use outro método de pagamento\n";
                break;
                
            case PaymentStatus::INVALID_CARD:
                echo "  • Verifique os dados do cartão\n";
                echo "  • Confirme a data de validade\n";
                echo "  • Verifique o CVV\n";
                break;
                
            default:
                echo "  • Tente novamente em alguns minutos\n";
                echo "  • Use outro método de pagamento\n";
        }
    }
    
    // ============================================
    // 6. AÇÕES PÓS-PAGAMENTO
    // ============================================
    
    if ($response->isSuccessful()) {
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "PRÓXIMOS PASSOS\n";
        echo str_repeat("-", 50) . "\n\n";
        
        // Exemplo: Salvar no banco de dados
        echo "1. Salvando informações no banco de dados...\n";
        // savePaymentToDatabase($response);
        
        // Exemplo: Enviar e-mail de confirmação
        echo "2. Enviando e-mail de confirmação...\n";
        // sendConfirmationEmail($response, $paymentRequest->customerEmail);
        
        // Exemplo: Gerar nota fiscal
        echo "3. Gerando nota fiscal...\n";
        // generateInvoice($response);
        
        // Exemplo: Liberar produto/serviço
        echo "4. Liberando acesso ao produto/serviço...\n";
        // releaseProduct($response->id);
        
        echo "\n✓ Todas as ações completadas!\n";
    }
    
} catch (\InvalidArgumentException $e) {
    echo "\n✗ ERRO DE VALIDAÇÃO\n\n";
    echo "Mensagem: {$e->getMessage()}\n";
    echo "\nVerifique os dados fornecidos e tente novamente.\n";
    
} catch (\IsraelNogueira\PaymentHub\Exceptions\GatewayException $e) {
    echo "\n✗ ERRO NO GATEWAY\n\n";
    echo "Mensagem: {$e->getMessage()}\n";
    echo "Código: {$e->getCode()}\n";
    echo "\nO serviço de pagamento está temporariamente indisponível.\n";
    echo "Tente novamente em alguns minutos.\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERRO INESPERADO\n\n";
    echo "Mensagem: {$e->getMessage()}\n";
    echo "\nPor favor, contate o suporte técnico.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// ============================================
// 7. FUNÇÕES AUXILIARES (EXEMPLOS)
// ============================================

/**
 * Exemplo de função para salvar pagamento no banco
 */
function savePaymentToDatabase($payment): void
{
    // Exemplo de como você salvaria no seu banco de dados
    /*
    DB::table('payments')->insert([
        'payment_id' => $payment->id,
        'transaction_id' => $payment->transactionId,
        'amount' => $payment->amount->getCents(),
        'currency' => $payment->amount->getCurrency()->value,
        'status' => $payment->status->value,
        'payment_method' => $payment->paymentMethod->value,
        'customer_email' => $payment->customerEmail,
        'created_at' => $payment->createdAt,
    ]);
    */
}

/**
 * Exemplo de função para enviar e-mail de confirmação
 */
function sendConfirmationEmail($payment, string $email): void
{
    // Exemplo de como enviar e-mail
    /*
    Mail::to($email)->send(new PaymentConfirmation([
        'payment_id' => $payment->id,
        'amount' => $payment->amount->format(),
        'status' => $payment->status->value,
    ]));
    */
}

/**
 * Exemplo de função para gerar nota fiscal
 */
function generateInvoice($payment): void
{
    // Exemplo de geração de nota fiscal
    /*
    $invoice = InvoiceService::generate([
        'payment_id' => $payment->id,
        'amount' => $payment->amount->getCents() / 100,
        'customer_document' => $payment->customerDocument,
    ]);
    
    return $invoice->number;
    */
}

/**
 * Exemplo de função para liberar produto
 */
function releaseProduct(string $paymentId): void
{
    // Exemplo de liberação de produto/serviço
    /*
    $order = Order::findByPaymentId($paymentId);
    $order->status = 'confirmed';
    $order->save();
    
    // Enviar produto/ativar acesso
    ProductService::release($order->id);
    */
}
