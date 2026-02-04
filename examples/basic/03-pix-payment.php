<?php

/**
 * Exemplo 3: Pagamento com PIX
 * 
 * Este exemplo demonstra como processar um pagamento instantâneo
 * via PIX, incluindo geração de QR Code e monitoramento de status.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Factories\PaymentHubFactory;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;

echo "\n" . str_repeat("=", 60) . "\n";
echo "EXEMPLO: PAGAMENTO VIA PIX\n";
echo str_repeat("=", 60) . "\n";

// ============================================
// 1. CONFIGURAÇÃO
// ============================================

$paymentHub = PaymentHubFactory::create([
    'gateway' => 'fake_bank',
    'environment' => 'sandbox',
]);

// ============================================
// 2. CRIAR PAGAMENTO PIX
// ============================================

try {
    echo "\n[1] Criando cobrança PIX...\n";
    
    $pixRequest = new PixPaymentRequest(
        amount: Money::fromCents(8500, Currency::BRL), // R$ 85,00
        pixKey: 'vendas@minhaempresa.com', // Chave PIX do recebedor
        description: 'Compra de Produto - Pedido #98765',
        expiresIn: 3600, // Expira em 1 hora
        customerName: 'Maria Santos',
        customerDocument: '98765432100',
        additionalInfo: [
            'order_id' => '98765',
            'product' => 'Kit Premium',
            'quantity' => 2,
        ]
    );
    
    echo "   ✓ Requisição criada\n";
    echo "   • Valor: {$pixRequest->amount->format()}\n";
    echo "   • Chave PIX: {$pixRequest->pixKey}\n";
    echo "   • Tipo de chave: {$pixRequest->getPixKeyType()}\n";
    echo "   • Expira em: " . ($pixRequest->expiresIn / 60) . " minutos\n";
    
    // ============================================
    // 3. PROCESSAR E GERAR QR CODE
    // ============================================
    
    echo "\n[2] Processando pagamento...\n";
    $response = $paymentHub->processPayment($pixRequest);
    
    if (!$response->isSuccessful()) {
        throw new \RuntimeException("Erro ao criar cobrança PIX: {$response->message}");
    }
    
    echo "   ✓ Cobrança criada com sucesso!\n";
    echo "   • ID: {$response->id}\n";
    echo "   • Status: {$response->status->value}\n";
    
    // ============================================
    // 4. EXIBIR QR CODE E PIX COPIA E COLA
    // ============================================
    
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "INFORMAÇÕES PARA PAGAMENTO\n";
    echo str_repeat("-", 60) . "\n\n";
    
    // QR Code (base64)
    if ($response->pixQrCode) {
        echo "📱 QR CODE PIX:\n\n";
        echo "   Escaneie o QR Code abaixo com o app do seu banco:\n\n";
        
        // Em produção, você exibiria a imagem do QR Code
        // Aqui mostramos o código base64
        echo "   [QR Code - Base64 Data]\n";
        echo "   " . substr($response->pixQrCode, 0, 50) . "...\n\n";
        
        // Salvar QR Code como imagem
        // file_put_contents('qrcode.png', base64_decode($response->pixQrCode));
    }
    
    // PIX Copia e Cola
    if ($response->pixCopyPaste) {
        echo "📋 PIX COPIA E COLA:\n\n";
        echo "   Copie o código abaixo e cole no seu app de pagamento:\n\n";
        echo "   ┌" . str_repeat("─", 56) . "┐\n";
        
        // Quebrar o código em linhas para melhor visualização
        $code = $response->pixCopyPaste;
        $chunks = str_split($code, 54);
        foreach ($chunks as $chunk) {
            echo "   │ " . str_pad($chunk, 54) . " │\n";
        }
        
        echo "   └" . str_repeat("─", 56) . "┘\n\n";
    }
    
    // Informações adicionais
    echo "ℹ️  INFORMAÇÕES:\n\n";
    echo "   • Valor: {$response->amount->format()}\n";
    echo "   • Favorecido: Minha Empresa LTDA\n";
    echo "   • Chave: {$pixRequest->pixKey}\n";
    
    if ($response->expiresAt) {
        echo "   • Válido até: {$response->expiresAt->format('d/m/Y H:i:s')}\n";
        
        $minutesLeft = $response->expiresAt->getTimestamp() - time();
        $minutesLeft = round($minutesLeft / 60);
        echo "   • Tempo restante: ~{$minutesLeft} minutos\n";
    }
    
    // ============================================
    // 5. MONITORAR PAGAMENTO
    // ============================================
    
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "AGUARDANDO CONFIRMAÇÃO DO PAGAMENTO\n";
    echo str_repeat("-", 60) . "\n\n";
    
    echo "Status atual: " . getStatusIcon($response->status) . " {$response->status->value}\n\n";
    echo "Monitorando pagamento (pressione Ctrl+C para cancelar)...\n\n";
    
    $maxAttempts = 60; // 5 minutos (verificando a cada 5 segundos)
    $attempt = 0;
    $previousStatus = $response->status;
    
    while ($attempt < $maxAttempts) {
        sleep(5); // Aguarda 5 segundos
        $attempt++;
        
        // Consultar status do pagamento
        $status = $paymentHub->getPaymentStatus($response->id);
        
        if ($status->status !== $previousStatus) {
            echo "\n⚡ Mudança de status detectada!\n";
            echo "   De: " . getStatusIcon($previousStatus) . " {$previousStatus->value}\n";
            echo "   Para: " . getStatusIcon($status->status) . " {$status->status->value}\n";
            
            $previousStatus = $status->status;
        }
        
        // Status final - pagamento aprovado
        if ($status->status === PaymentStatus::COMPLETED) {
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "✅ PAGAMENTO CONFIRMADO!\n";
            echo str_repeat("=", 60) . "\n\n";
            
            echo "Detalhes da Transação:\n";
            echo "   • ID: {$status->id}\n";
            echo "   • Transaction ID: {$status->transactionId}\n";
            echo "   • Valor: {$status->amount->format()}\n";
            echo "   • Pago em: {$status->paidAt->format('d/m/Y H:i:s')}\n";
            
            if ($status->endToEndId) {
                echo "   • End-to-End ID: {$status->endToEndId}\n";
            }
            
            // Ações pós-pagamento
            echo "\n📦 Processando pedido...\n";
            echo "   ✓ Enviando confirmação por e-mail\n";
            echo "   ✓ Atualizando estoque\n";
            echo "   ✓ Gerando nota fiscal\n";
            echo "   ✓ Preparando produto para envio\n";
            
            echo "\n✅ Pedido confirmado! Obrigado pela compra.\n";
            
            break;
        }
        
        // Status final - pagamento expirado/cancelado
        if (in_array($status->status, [
            PaymentStatus::EXPIRED,
            PaymentStatus::CANCELLED,
            PaymentStatus::FAILED
        ])) {
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "❌ PAGAMENTO NÃO REALIZADO\n";
            echo str_repeat("=", 60) . "\n\n";
            
            echo "Status: {$status->status->value}\n";
            
            if ($status->status === PaymentStatus::EXPIRED) {
                echo "Motivo: O tempo para pagamento expirou\n";
                echo "\nVocê pode gerar uma nova cobrança para tentar novamente.\n";
            }
            
            break;
        }
        
        // Indicador de progresso
        echo ".";
        flush();
        
        // Verificar se está próximo de expirar
        if ($response->expiresAt) {
            $timeLeft = $response->expiresAt->getTimestamp() - time();
            
            if ($timeLeft < 300 && $timeLeft > 240) { // 5 minutos restantes
                echo "\n⚠️  Atenção: Apenas 5 minutos restantes para pagamento!\n";
            }
        }
    }
    
    if ($attempt >= $maxAttempts) {
        echo "\n\n⏱️  Tempo limite de monitoramento atingido.\n";
        echo "O pagamento ainda pode ser realizado. Verifique o status manualmente.\n";
    }
    
} catch (\InvalidArgumentException $e) {
    echo "\n❌ ERRO DE VALIDAÇÃO\n\n";
    echo "Mensagem: {$e->getMessage()}\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO\n\n";
    echo "Mensagem: {$e->getMessage()}\n";
    echo "Tipo: " . get_class($e) . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

/**
 * Retorna um ícone visual para o status
 */
function getStatusIcon(PaymentStatus $status): string
{
    return match($status) {
        PaymentStatus::PENDING => '⏳',
        PaymentStatus::PROCESSING => '⚙️',
        PaymentStatus::COMPLETED => '✅',
        PaymentStatus::FAILED => '❌',
        PaymentStatus::CANCELLED => '🚫',
        PaymentStatus::EXPIRED => '⏰',
        PaymentStatus::REFUNDED => '↩️',
        default => '❓',
    };
}

// ============================================
// INFORMAÇÕES ADICIONAIS SOBRE PIX
// ============================================

/**
 * TIPOS DE CHAVE PIX SUPORTADOS:
 * 
 * 1. CPF: 12345678900 ou 123.456.789-00
 * 2. CNPJ: 12345678000100 ou 12.345.678/0001-00
 * 3. E-mail: usuario@exemplo.com
 * 4. Telefone: +5511999887766 ou 11999887766
 * 5. Chave Aleatória: UUID (ex: 123e4567-e89b-12d3-a456-426614174000)
 * 
 * VANTAGENS DO PIX:
 * 
 * ✓ Transferência instantânea (segundos)
 * ✓ Disponível 24/7/365
 * ✓ Sem taxas para pessoa física
 * ✓ Confirmação imediata
 * ✓ Seguro e rastreável
 * 
 * FLUXO TÍPICO:
 * 
 * 1. Loja cria cobrança PIX
 * 2. Gera QR Code e código Copia e Cola
 * 3. Cliente escaneia QR Code ou cola código no app
 * 4. Cliente confirma pagamento no app do banco
 * 5. Pagamento processado instantaneamente
 * 6. Webhook notifica a loja (opcional)
 * 7. Loja confirma pedido
 * 
 * BOAS PRÁTICAS:
 * 
 * • Sempre definir tempo de expiração (30min - 1h é comum)
 * • Monitorar via webhook para confirmação instantânea
 * • Exibir QR Code E código Copia e Cola
 * • Informar claramente o tempo de expiração
 * • Permitir gerar nova cobrança se expirar
 * • Salvar end-to-end ID para rastreamento
 */
