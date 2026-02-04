# 💰 PIX - Guia Completo

Tudo sobre pagamentos PIX com PaymentHub.

---

## 🎯 O Que é PIX?

PIX é o sistema de pagamento instantâneo brasileiro:

- ⚡ **Instantâneo** - Aprovação em segundos
- 🕐 **24/7** - Funciona todos os dias, qualquer hora
- 💵 **Gratuito** - Para pessoa física
- 🔐 **Seguro** - Operado pelo Banco Central

---

## 🚀 Criando um PIX

### Básico

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    description: 'Pagamento do pedido #123',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com'
);

$response = $hub->createPixPayment($request);
```

### Com Todos os Parâmetros

```php
$request = PixPaymentRequest::create(
    amount: 150.50,
    currency: Currency::BRL,
    description: 'Compra na loja virtual',
    customerName: 'Maria Santos',
    customerDocument: '987.654.321-00',
    customerEmail: 'maria@email.com',
    customerPhone: '11999887766',
    expiresInMinutes: 30,           // Expira em 30 minutos
    metadata: [
        'pedido_id' => 12345,
        'origem' => 'mobile',
        'vendedor_id' => 678
    ]
);

$response = $hub->createPixPayment($request);

if ($response->isSuccess()) {
    // Sucesso!
    $transactionId = $response->transactionId;
    $qrCode = $hub->getPixQrCode($transactionId);
    $copiaCola = $hub->getPixCopyPaste($transactionId);
}
```

---

## 📱 Usando o QR Code

### Imagem Base64

```php
$qrCode = $hub->getPixQrCode($response->transactionId);

// É uma string base64 que pode ser usada direto no HTML
echo '<img src="' . $qrCode . '" alt="QR Code PIX">';
```

### Em HTML

```html
<!DOCTYPE html>
<html>
<head>
    <title>Pagamento PIX</title>
    <style>
        .pix-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .qr-code {
            margin: 20px 0;
        }
        .qr-code img {
            width: 100%;
            max-width: 300px;
        }
        .valor {
            font-size: 32px;
            font-weight: bold;
            color: #32BCAD;
            margin: 10px 0;
        }
        .info {
            color: #666;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="pix-container">
        <h1>Pagamento PIX</h1>
        
        <div class="valor">
            R$ <?= number_format($response->amount, 2, ',', '.') ?>
        </div>
        
        <p class="info">Escaneie o QR Code com o app do seu banco</p>
        
        <div class="qr-code">
            <img src="<?= $qrCode ?>" alt="QR Code PIX">
        </div>
        
        <p class="info">ID da transação: <?= $response->transactionId ?></p>
    </div>
</body>
</html>
```

---

## 📋 Copia e Cola

### Obtendo o Código

```php
$copiaCola = $hub->getPixCopyPaste($response->transactionId);

// Retorna algo como:
// "00020126330014BR.GOV.BCB.PIX0111FAKE_PIX_abc123..."
```

### Interface Completa

```html
<!DOCTYPE html>
<html>
<head>
    <title>PIX - Copia e Cola</title>
    <style>
        .copia-cola-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
        }
        .codigo {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            font-family: monospace;
            margin: 10px 0;
        }
        .btn-copiar {
            background: #32BCAD;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-copiar:hover {
            background: #28a89a;
        }
        .copiado {
            color: green;
            display: none;
        }
    </style>
</head>
<body>
    <div class="copia-cola-container">
        <h2>PIX Copia e Cola</h2>
        
        <p>Copie o código abaixo e cole no app do seu banco:</p>
        
        <div class="codigo" id="codigo">
            <?= $copiaCola ?>
        </div>
        
        <button class="btn-copiar" onclick="copiarCodigo()">
            📋 Copiar Código
        </button>
        
        <p class="copiado" id="mensagem">✅ Código copiado!</p>
    </div>
    
    <script>
        function copiarCodigo() {
            const codigo = document.getElementById('codigo').textContent;
            navigator.clipboard.writeText(codigo).then(() => {
                document.getElementById('mensagem').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('mensagem').style.display = 'none';
                }, 3000);
            });
        }
    </script>
</body>
</html>
```

---

## ⏱️ Expiração

### Configurar Tempo de Expiração

```php
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    customerDocument: '123.456.789-00',
    expiresInMinutes: 15  // Expira em 15 minutos
);
```

### Valores Recomendados

- **Checkout rápido**: 5-10 minutos
- **E-commerce padrão**: 30 minutos
- **Boleto alternativo**: 24 horas (1440 minutos)

### Verificar Expiração

```php
$status = $hub->getTransactionStatus($transactionId);

if ($status->status === PaymentStatus::EXPIRED) {
    echo "PIX expirado! Gere um novo.";
}
```

---

## 📊 Verificar Status

### Consultar Transação

```php
$status = $hub->getTransactionStatus($transactionId);

echo "Status: " . $status->status->label() . "\n";
echo "Valor: " . $status->getFormattedAmount() . "\n";

if ($status->status->isPaid()) {
    echo "✅ Pagamento confirmado!";
} elseif ($status->status->isPending()) {
    echo "⏳ Aguardando pagamento...";
} elseif ($status->status->isExpired()) {
    echo "⌛ PIX expirado";
}
```

### Polling (Verificação Periódica)

```javascript
// No front-end
function verificarPagamento(transactionId) {
    fetch(`/api/payment/status/${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'paid') {
                // Redirecionar para página de sucesso
                window.location.href = '/sucesso';
            } else if (data.status === 'pending') {
                // Verificar novamente em 3 segundos
                setTimeout(() => verificarPagamento(transactionId), 3000);
            } else {
                // Erro ou expirado
                alert('Pagamento não confirmado');
            }
        });
}

// Iniciar verificação
verificarPagamento('<?= $transactionId ?>');
```

---

## 🔔 Webhooks

### Configurar Webhook

```php
$hub->registerWebhook(
    url: 'https://seusite.com/webhooks/payment',
    events: ['payment.paid', 'payment.failed']
);
```

### Processar Webhook

```php
// webhooks/payment.php

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if ($event['type'] === 'payment.paid') {
    $transactionId = $event['data']['transaction_id'];
    
    // Atualizar pedido
    $order = Order::findByTransactionId($transactionId);
    $order->markAsPaid();
    $order->save();
    
    // Enviar email
    Mail::to($order->customer_email)->send(new PaymentConfirmed($order));
}

http_response_code(200);
```

[Veja guia completo de webhooks →](../advanced/webhooks.md)

---

## 💡 Exemplos Práticos

### E-commerce Básico

```php
class CheckoutController
{
    public function __construct(
        private PaymentHub $hub
    ) {}
    
    public function payWithPix(Request $request)
    {
        // Validar carrinho
        $cart = Cart::forUser($request->user());
        
        if ($cart->isEmpty()) {
            return response()->json(['error' => 'Carrinho vazio'], 400);
        }
        
        // Criar pagamento PIX
        $pixRequest = PixPaymentRequest::create(
            amount: $cart->total(),
            currency: Currency::BRL,
            description: "Pedido #{$cart->id}",
            customerName: $request->user()->name,
            customerDocument: $request->user()->document,
            customerEmail: $request->user()->email,
            expiresInMinutes: 30,
            metadata: [
                'cart_id' => $cart->id,
                'user_id' => $request->user()->id,
            ]
        );
        
        try {
            $response = $this->hub->createPixPayment($pixRequest);
            
            if ($response->isSuccess()) {
                // Criar pedido
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'transaction_id' => $response->transactionId,
                    'amount' => $response->amount,
                    'status' => 'pending',
                ]);
                
                // Limpar carrinho
                $cart->clear();
                
                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'transaction_id' => $response->transactionId,
                    'qr_code' => $this->hub->getPixQrCode($response->transactionId),
                    'copia_cola' => $this->hub->getPixCopyPaste($response->transactionId),
                    'expires_at' => now()->addMinutes(30),
                ]);
            }
            
        } catch (GatewayException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### Doação Qualquer Valor

```php
class DonationController
{
    public function donate(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'name' => 'required|string',
            'email' => 'required|email',
            'document' => 'required',
        ]);
        
        $pixRequest = PixPaymentRequest::create(
            amount: $validated['amount'],
            currency: Currency::BRL,
            description: 'Doação - Projeto XYZ',
            customerName: $validated['name'],
            customerDocument: $validated['document'],
            customerEmail: $validated['email'],
            metadata: [
                'type' => 'donation',
                'project' => 'XYZ',
            ]
        );
        
        $response = $this->hub->createPixPayment($pixRequest);
        
        if ($response->isSuccess()) {
            // Salvar doação
            Donation::create([
                'transaction_id' => $response->transactionId,
                'amount' => $response->amount,
                'donor_name' => $validated['name'],
                'donor_email' => $validated['email'],
                'status' => 'pending',
            ]);
            
            return view('donation.success', [
                'qrCode' => $this->hub->getPixQrCode($response->transactionId),
                'copiaCola' => $this->hub->getPixCopyPaste($response->transactionId),
                'amount' => $response->getFormattedAmount(),
            ]);
        }
    }
}
```

---

## 🎨 Templates Prontos

### Modal de Pagamento

```html
<div class="modal" id="pixModal">
    <div class="modal-content">
        <h2>Pagamento PIX</h2>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('qrcode')">
                QR Code
            </button>
            <button class="tab" onclick="showTab('copiaCola')">
                Copia e Cola
            </button>
        </div>
        
        <div id="qrcode-tab" class="tab-content active">
            <p>Escaneie com o app do seu banco:</p>
            <img src="<?= $qrCode ?>" alt="QR Code">
        </div>
        
        <div id="copiaCola-tab" class="tab-content">
            <p>Copie e cole no app do seu banco:</p>
            <textarea readonly><?= $copiaCola ?></textarea>
            <button onclick="copiarCodigo()">Copiar</button>
        </div>
        
        <div class="timer">
            Expira em: <span id="countdown">30:00</span>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });
    document.getElementById(tab + '-tab').classList.add('active');
}

// Countdown
let seconds = 1800; // 30 minutos
setInterval(() => {
    seconds--;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    document.getElementById('countdown').textContent = 
        `${mins}:${secs.toString().padStart(2, '0')}`;
    
    if (seconds <= 0) {
        alert('PIX expirado!');
    }
}, 1000);
</script>
```

---

## 🔧 Tratamento de Erros

```php
use IsraelNogueira\PaymentHub\Exceptions\{
    InvalidDocumentException,
    InvalidEmailException,
    InvalidAmountException,
    GatewayException
};

try {
    $request = PixPaymentRequest::create(
        amount: $amount,
        currency: Currency::BRL,
        customerDocument: $document,
        customerEmail: $email,
        // ...
    );
    
    $response = $hub->createPixPayment($request);
    
} catch (InvalidDocumentException $e) {
    // CPF/CNPJ inválido
    return response()->json([
        'error' => 'Documento inválido',
        'message' => $e->getMessage()
    ], 422);
    
} catch (InvalidEmailException $e) {
    // Email inválido
    return response()->json([
        'error' => 'Email inválido',
        'message' => $e->getMessage()
    ], 422);
    
} catch (InvalidAmountException $e) {
    // Valor inválido
    return response()->json([
        'error' => 'Valor inválido',
        'message' => $e->getMessage()
    ], 422);
    
} catch (GatewayException $e) {
    // Erro no gateway
    Log::error('PIX payment failed', [
        'gateway' => $e->getGateway(),
        'error' => $e->getMessage(),
    ]);
    
    return response()->json([
        'error' => 'Erro ao processar pagamento',
        'message' => 'Tente novamente em alguns instantes'
    ], 500);
}
```

---

## 📈 Boas Práticas

### ✅ Faça

- Valide dados antes de criar o pagamento
- Configure tempo de expiração adequado
- Implemente webhooks para confirmação
- Armazene transaction_id no banco
- Use metadata para rastreamento
- Mostre QR Code e Copia e Cola
- Implemente polling no front-end
- Trate todos os erros possíveis

### ❌ Não Faça

- Confiar apenas em polling (use webhooks)
- Expor dados sensíveis no metadata
- Criar PIX sem expiração
- Deixar de validar documentos
- Ignorar tratamento de erros
- Usar valores hardcoded

---

## 🎯 Próximos Passos

- [**Cartão de Crédito**](credit-card.md)
- [**Webhooks**](../advanced/webhooks.md)
- [**Tratamento de Erros**](error-handling.md)

---

**Dúvidas sobre PIX?** Consulte o [FAQ](../help/faq.md)!
