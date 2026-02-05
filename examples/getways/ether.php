<?php
use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\EtherGlobalAssetsGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\TransferRequest;

// ==================== 1. INICIALIZAR GATEWAY ====================

$clientId = 'seu_client_id_aqui';
$clientSecret = 'seu_client_secret_aqui';

$gateway = new EtherGlobalAssetsGateway($clientId, $clientSecret);
$paymentHub = new PaymentHub($gateway);

echo "🚀 Gateway Ether Global Assets inicializado!\n\n";

// ==================== 2. CRIAR PIX PARA DEPÓSITO ====================

echo "📥 CRIANDO PIX PARA DEPÓSITO\n";
echo str_repeat("-", 50) . "\n";

try {
    $pixRequest = PixPaymentRequest::create(
        amount: 150.00, // R$ 150,00
        currency: 'BRL',
        description: 'Depósito via PIX',
        metadata: [
            'order_id' => 'ORDER-12345',
            'customer_name' => 'João Silva'
        ]
    );

    $response = $paymentHub->createPixPayment($pixRequest);

    if ($response->isSuccess()) {
        echo "✅ PIX criado com sucesso!\n";
        echo "   Transaction ID: {$response->transactionId}\n";
        echo "   Status: {$response->getStatusLabel()}\n";
        echo "   Valor: {$response->getFormattedAmount()}\n";
        
        // Dados do QR Code
        $qrCodeId = $response->rawResponse['qrCodeId'] ?? null;
        $pixKey = $response->rawResponse['pixKey'] ?? null;
        $expireAt = $response->rawResponse['expireAt'] ?? null;
        
        echo "\n   📱 QR Code ID: {$qrCodeId}\n";
        echo "   🔑 PIX Copia e Cola:\n";
        echo "   {$pixKey}\n\n";
        echo "   ⏰ Expira em: {$expireAt}\n";
        echo "   ⚠️  Válido por 5 minutos!\n";
        
    } else {
        echo "❌ Erro ao criar PIX\n";
        echo "   Mensagem: {$response->message}\n";
    }

} catch (Exception $e) {
    echo "❌ Exceção: {$e->getMessage()}\n";
}

echo "\n\n";

// ==================== 3. CONSULTAR SALDO ====================

echo "💰 CONSULTANDO SALDO DA CONTA\n";
echo str_repeat("-", 50) . "\n";

try {
    $balance = $paymentHub->getBalance();

    if ($balance->success) {
        echo "✅ Saldo consultado com sucesso!\n";
        echo "   Saldo Total: R$ " . number_format($balance->balance, 2, ',', '.') . "\n";
        echo "   Saldo Disponível: R$ " . number_format($balance->availableBalance, 2, ',', '.') . "\n";
        echo "   Saldo Pendente: R$ " . number_format($balance->pendingBalance, 2, ',', '.') . "\n";
        echo "   Moeda: {$balance->currency}\n";
    } else {
        echo "❌ Erro ao consultar saldo\n";
    }

} catch (Exception $e) {
    echo "❌ Exceção: {$e->getMessage()}\n";
}

echo "\n\n";

// ==================== 4. REALIZAR SAQUE VIA PIX ====================

echo "📤 REALIZANDO SAQUE VIA PIX\n";
echo str_repeat("-", 50) . "\n";

try {
    $transferRequest = TransferRequest::create(
        amount: 50.00, // R$ 50,00
        pixKey: 'usuario@email.com', // Chave PIX destino
        description: 'Saque para conta pessoal',
        metadata: [
            'pixKeyType' => 'EMAIL', // EMAIL, CPF, CNPJ, PHONE, RANDOM
            'withdrawal_id' => 'WTH-67890'
        ]
    );

    $transferResponse = $paymentHub->transfer($transferRequest);

    if ($transferResponse->success) {
        echo "✅ Saque iniciado com sucesso!\n";
        echo "   Transfer ID: {$transferResponse->transferId}\n";
        echo "   Valor: R$ " . number_format($transferResponse->amount, 2, ',', '.') . "\n";
        echo "   Status: {$transferResponse->status}\n";
        echo "   Mensagem: {$transferResponse->message}\n";
        
        // Dados adicionais
        $pixId = $transferResponse->rawResponse['pixId'] ?? null;
        $e2e = $transferResponse->rawResponse['e2e'] ?? null;
        $executedAt = $transferResponse->rawResponse['executedAt'] ?? null;
        
        if ($pixId) {
            echo "\n   PIX ID: {$pixId}\n";
        }
        if ($e2e) {
            echo "   E2E: {$e2e}\n";
        }
        if ($executedAt) {
            echo "   Executado em: {$executedAt}\n";
        }
        
    } else {
        echo "❌ Erro ao realizar saque\n";
        echo "   Mensagem: {$transferResponse->message}\n";
    }

} catch (Exception $e) {
    echo "❌ Exceção: {$e->getMessage()}\n";
}

echo "\n\n";

// ==================== 5. EXEMPLO COM DIFERENTES TIPOS DE CHAVE PIX ====================

echo "🔑 EXEMPLOS DE DIFERENTES TIPOS DE CHAVE PIX\n";
echo str_repeat("-", 50) . "\n";

$exemploChaves = [
    [
        'tipo' => 'EMAIL',
        'chave' => 'cliente@exemplo.com.br',
        'descricao' => 'Email válido'
    ],
    [
        'tipo' => 'CPF',
        'chave' => '12345678901',
        'descricao' => 'CPF sem formatação (11 dígitos)'
    ],
    [
        'tipo' => 'CNPJ',
        'chave' => '12345678000199',
        'descricao' => 'CNPJ sem formatação (14 dígitos)'
    ],
    [
        'tipo' => 'PHONE',
        'chave' => '11999999999',
        'descricao' => 'Telefone com DDD (sem +55)'
    ],
    [
        'tipo' => 'RANDOM',
        'chave' => '550e8400-e29b-41d4-a716-446655440000',
        'descricao' => 'Chave aleatória (UUID)'
    ]
];

foreach ($exemploChaves as $exemplo) {
    echo "\n📌 Tipo: {$exemplo['tipo']}\n";
    echo "   Chave: {$exemplo['chave']}\n";
    echo "   Descrição: {$exemplo['descricao']}\n";
}

echo "\n\n";

// ==================== 6. TRATAMENTO DE ERROS COMUNS ====================

echo "⚠️  TRATAMENTO DE ERROS COMUNS\n";
echo str_repeat("-", 50) . "\n";

echo "\n1. Autenticação falhou:\n";
echo "   - Verifique clientId e clientSecret\n";
echo "   - Token expira em 1 hora (renovação automática)\n";

echo "\n2. Valor inválido no PIX:\n";
echo "   - Valores são convertidos para centavos automaticamente\n";
echo "   - Mínimo: R$ 1,00 (100 centavos)\n";
echo "   - Máximo: R$ 500.000,00 (50.000.000 centavos)\n";

echo "\n3. PIX expirado:\n";
echo "   - PIX dinâmico expira em 5 minutos\n";
echo "   - Crie um novo PIX se expirar\n";

echo "\n4. Saldo insuficiente:\n";
echo "   - Consulte o saldo antes de fazer saque\n";
echo "   - Considere as taxas na transferência\n";

echo "\n5. Chave PIX inválida:\n";
echo "   - EMAIL: deve ser email válido\n";
echo "   - CPF: 11 dígitos sem formatação\n";
echo "   - CNPJ: 14 dígitos sem formatação\n";
echo "   - PHONE: DDD + número sem espaços\n";

echo "\n\n";

// ==================== 7. EXEMPLO DE USO REAL (FLUXO COMPLETO) ====================

echo "🎯 FLUXO COMPLETO: RECEBER E ENVIAR PIX\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // 1. Consultar saldo inicial
    echo "1️⃣  Consultando saldo inicial...\n";
    $saldoInicial = $paymentHub->getBalance();
    echo "   Saldo: R$ " . number_format($saldoInicial->balance, 2, ',', '.') . "\n\n";

    // 2. Criar PIX para receber
    echo "2️⃣  Criando PIX para receber R$ 100,00...\n";
    $pixReceber = PixPaymentRequest::create(
        amount: 100.00,
        currency: 'BRL',
        description: 'Recebimento de cliente'
    );
    
    $pixResponse = $paymentHub->createPixPayment($pixReceber);
    echo "   ✅ PIX criado: {$pixResponse->transactionId}\n";
    echo "   Chave PIX: {$pixResponse->rawResponse['pixKey']}\n\n";

    // 3. Simular pagamento (na prática, cliente pagaria o QR Code)
    echo "3️⃣  💳 Cliente pagaria o QR Code aqui...\n";
    echo "   (Webhook 'pix.deposit.confirmed' seria recebido)\n\n";

    // 4. Após receber, fazer um saque
    echo "4️⃣  Realizando saque de R$ 30,00...\n";
    $saque = TransferRequest::create(
        amount: 30.00,
        pixKey: 'fornecedor@empresa.com',
        description: 'Pagamento a fornecedor',
        metadata: ['pixKeyType' => 'EMAIL']
    );
    
    $saqueResponse = $paymentHub->transfer($saque);
    echo "   ✅ Saque iniciado: {$saqueResponse->transferId}\n";
    echo "   Status: {$saqueResponse->status}\n\n";

    // 5. Consultar saldo final
    echo "5️⃣  Consultando saldo após operações...\n";
    $saldoFinal = $paymentHub->getBalance();
    echo "   Saldo: R$ " . number_format($saldoFinal->balance, 2, ',', '.') . "\n";

} catch (Exception $e) {
    echo "❌ Erro no fluxo: {$e->getMessage()}\n";
}

echo "\n\n";
echo "✅ Exemplo completo finalizado!\n";
echo str_repeat("=", 50) . "\n";