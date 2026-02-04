# 🎯 Seu Primeiro Pagamento

Vamos criar seu primeiro pagamento PIX em menos de 2 minutos!

---

## 📱 PIX em 5 Passos

### 1️⃣ Prepare o Ambiente

```php
<?php

require 'vendor/autoload.php';

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;
```

### 2️⃣ Instancie o PaymentHub

```php
// Para testes, use o FakeBankGateway
$hub = new PaymentHub(new FakeBankGateway());
```

### 3️⃣ Crie a Requisição

```php
$request = PixPaymentRequest::create(
    amount: 100.00,                     // R$ 100,00
    currency: Currency::BRL,            // Real brasileiro
    description: 'Meu primeiro PIX',   
    customerName: 'João Silva',
    customerDocument: '123.456.789-00', // Valida automaticamente
    customerEmail: 'joao@email.com',    // Valida automaticamente
    expiresInMinutes: 30                // Expira em 30 min
);
```

### 4️⃣ Processe o Pagamento

```php
$response = $hub->createPixPayment($request);
```

### 5️⃣ Use o Resultado

```php
if ($response->isSuccess()) {
    echo "✅ PIX criado com sucesso!\n\n";
    
    // Dados do pagamento
    echo "ID: " . $response->transactionId . "\n";
    echo "Status: " . $response->status->label() . "\n";
    echo "Valor: " . $response->getFormattedAmount() . "\n\n";
    
    // QR Code para o cliente
    $qrCode = $hub->getPixQrCode($response->transactionId);
    echo "QR Code (base64): " . substr($qrCode, 0, 50) . "...\n\n";
    
    // Copia e cola para o cliente
    $copiaCola = $hub->getPixCopyPaste($response->transactionId);
    echo "Copia e Cola: " . $copiaCola . "\n";
    
} else {
    echo "❌ Erro ao criar PIX\n";
    echo "Mensagem: " . $response->message . "\n";
}
```

---

## 💻 Código Completo

```php
<?php

require 'vendor/autoload.php';

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

// 1. Instancia
$hub = new PaymentHub(new FakeBankGateway());

// 2. Cria requisição
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    description: 'Meu primeiro PIX',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com',
    expiresInMinutes: 30
);

// 3. Processa
$response = $hub->createPixPayment($request);

// 4. Verifica resultado
if ($response->isSuccess()) {
    echo "✅ PIX criado!\n";
    echo "ID: {$response->transactionId}\n";
    echo "QR Code: " . $hub->getPixQrCode($response->transactionId) . "\n";
    echo "Copia e Cola: " . $hub->getPixCopyPaste($response->transactionId) . "\n";
} else {
    echo "❌ Erro: {$response->message}\n";
}
```

---

## 🎨 Exemplo com HTML

```php
<?php
require 'vendor/autoload.php';

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

$hub = new PaymentHub(new FakeBankGateway());

$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    description: 'Pagamento de teste',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com'
);

$response = $hub->createPixPayment($request);

if ($response->isSuccess()) {
    $qrCode = $hub->getPixQrCode($response->transactionId);
    $copiaCola = $hub->getPixCopyPaste($response->transactionId);
    ?>
    
    <!DOCTYPE html>
    <html>
    <head>
        <title>Pagamento PIX</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; }
            .qr-code { margin: 20px auto; max-width: 300px; }
            .copia-cola { 
                background: #f5f5f5; 
                padding: 15px; 
                border-radius: 5px;
                word-break: break-all;
                margin: 20px 0;
            }
            .info { color: #666; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h1>✅ Pagamento PIX Criado!</h1>
        
        <div class="info">
            <p><strong>Valor:</strong> <?= $response->getFormattedAmount() ?></p>
            <p><strong>ID:</strong> <?= $response->transactionId ?></p>
        </div>
        
        <h2>Escaneie o QR Code:</h2>
        <div class="qr-code">
            <img src="<?= $qrCode ?>" alt="QR Code PIX" style="width: 100%;">
        </div>
        
        <h2>Ou copie o código:</h2>
        <div class="copia-cola">
            <code id="copiaCola"><?= $copiaCola ?></code>
        </div>
        
        <button onclick="copiarCodigo()">📋 Copiar Código</button>
        
        <script>
            function copiarCodigo() {
                const texto = document.getElementById('copiaCola').textContent;
                navigator.clipboard.writeText(texto);
                alert('Código copiado!');
            }
        </script>
    </body>
    </html>
    
    <?php
}
?>
```

---

## 🔍 Entendendo o Código

### PixPaymentRequest

```php
PixPaymentRequest::create(
    amount: 100.00,              // Valor em reais
    currency: Currency::BRL,     // Moeda (enum)
    description: 'Descrição',    // Aparece no extrato
    customerName: 'Nome',        // Nome do pagador
    customerDocument: 'CPF',     // CPF ou CNPJ (valida auto)
    customerEmail: 'email',      // Email (valida auto)
    expiresInMinutes: 30,        // Opcional: tempo de expiração
    metadata: ['pedido' => 123]  // Opcional: dados extras
)
```

### PaymentResponse

```php
$response->isSuccess()           // true/false
$response->transactionId         // ID único da transação
$response->status                // Enum PaymentStatus
$response->status->label()       // "Aprovado", "Pendente", etc
$response->getFormattedAmount()  // "R$ 100,00"
$response->message               // Mensagem do gateway
$response->rawResponse           // Resposta completa do gateway
$response->metadata              // Seus dados extras
```

---

## ✨ Validações Automáticas

O PaymentHub valida automaticamente:

### CPF/CNPJ
```php
// ✅ Válido
customerDocument: '123.456.789-00'
customerDocument: '12345678900'
customerDocument: '12.345.678/0001-00'

// ❌ Inválido - lança InvalidDocumentException
customerDocument: '111.111.111-11'
customerDocument: '123'
```

### Email
```php
// ✅ Válido
customerEmail: 'joao@email.com'

// ❌ Inválido - lança InvalidEmailException
customerEmail: 'joao@'
customerEmail: 'nao-eh-email'
```

### Valor
```php
// ✅ Válido
amount: 100.00
amount: 0.50

// ❌ Inválido - lança InvalidAmountException
amount: -10.00
amount: 0
```

---

## 🎯 Próximos Passos

Agora que você criou seu primeiro PIX:

1. [**Teste com Cartão de Crédito**](../guides/credit-card.md)
2. [**Aprenda sobre Status**](../guides/enums.md#paymentstatus)
3. [**Configure Webhooks**](../advanced/webhooks.md)
4. [**Use em Produção**](configuration.md)

---

## 💡 Dicas

### Sempre Trate Erros

```php
use IsraelNogueira\PaymentHub\Exceptions\InvalidDocumentException;
use IsraelNogueira\PaymentHub\Exceptions\GatewayException;

try {
    $request = PixPaymentRequest::create(
        amount: 100.00,
        currency: Currency::BRL,
        customerDocument: '123.456.789-00', // Pode ser inválido
        // ...
    );
    
    $response = $hub->createPixPayment($request);
    
} catch (InvalidDocumentException $e) {
    echo "CPF/CNPJ inválido: " . $e->getMessage();
    
} catch (GatewayException $e) {
    echo "Erro no gateway: " . $e->getMessage();
    
} catch (\Exception $e) {
    echo "Erro inesperado: " . $e->getMessage();
}
```

### Use Metadata

```php
$request = PixPaymentRequest::create(
    // ... outros campos
    metadata: [
        'pedido_id' => 12345,
        'cliente_id' => 678,
        'origem' => 'mobile'
    ]
);

// Depois recupere
$response = $hub->createPixPayment($request);
echo "Pedido: " . $response->metadata['pedido_id'];
```

### Consulte Status

```php
// Depois de criar o pagamento
$status = $hub->getTransactionStatus($response->transactionId);

if ($status->status->isPaid()) {
    echo "✅ Pagamento confirmado!";
} elseif ($status->status->isPending()) {
    echo "⏳ Aguardando pagamento...";
}
```

---

**Parabéns! 🎉** Você criou seu primeiro pagamento com PaymentHub!
