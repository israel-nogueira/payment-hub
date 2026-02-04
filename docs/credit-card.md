# 💳 Cartão de Crédito

Aceite pagamentos com cartão de crédito de forma segura.

---

## 🚀 Pagamento Básico

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

$request = CreditCardPaymentRequest::create(
    amount: 250.00,
    currency: Currency::BRL,
    cardNumber: '4111 1111 1111 1111',
    cardHolderName: 'JOAO SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    installments: 1,
    capture: true,
    description: 'Compra na loja',
    customerEmail: 'joao@email.com'
);

$response = $hub->createCreditCardPayment($request);

if ($response->isSuccess()) {
    echo "✅ Pagamento aprovado!\n";
    echo "ID: {$response->transactionId}\n";
    echo "Bandeira: " . $request->getCardBrand() . "\n";
}
```

---

## 💰 Parcelamento

### À Vista

```php
$request = CreditCardPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    cardNumber: '4111 1111 1111 1111',
    cardHolderName: 'JOAO SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    installments: 1,  // À vista
    capture: true
);
```

### Parcelado (Sem Juros)

```php
$request = CreditCardPaymentRequest::create(
    amount: 300.00,
    currency: Currency::BRL,
    // ... dados do cartão
    installments: 3,  // 3x de R$ 100,00
    capture: true
);

// Cálculo automático
echo "Valor total: " . $request->getFormattedAmount() . "\n";
echo "Parcelas: " . $request->getFormattedDescription() . "\n";
// "3x de R$ 100,00"
```

### Com Juros

```php
$request = CreditCardPaymentRequest::create(
    amount: 300.00,
    currency: Currency::BRL,
    // ... dados do cartão
    installments: 6,
    interestRate: 2.5,  // 2,5% ao mês
    capture: true
);
```

---

## 🔐 Tokenização

### Salvar Cartão

```php
$cardToken = $hub->tokenizeCard([
    'card_number' => '4111 1111 1111 1111',
    'card_holder_name' => 'JOAO SILVA',
    'card_expiry_month' => '12',
    'card_expiry_year' => '2028',
    'card_cvv' => '123',
]);

// Salvar token no banco
User::find($userId)->update([
    'card_token' => $cardToken
]);
```

### Usar Token

```php
$user = User::find($userId);

$request = CreditCardPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    cardToken: $user->card_token,  // Usar token ao invés dos dados
    installments: 1,
    capture: true
);

$response = $hub->createCreditCardPayment($request);
```

---

## 🔒 Pré-Autorização

### Autorizar (Reservar)

```php
$request = CreditCardPaymentRequest::create(
    amount: 500.00,
    currency: Currency::BRL,
    // ... dados do cartão
    installments: 1,
    capture: false  // Não captura automaticamente
);

$response = $hub->createCreditCardPayment($request);

if ($response->isSuccess()) {
    echo "Valor reservado!\n";
    $transactionId = $response->transactionId;
}
```

### Capturar Total

```php
// Captura o valor total reservado
$capture = $hub->capturePreAuthorization($transactionId);

if ($capture->isSuccess()) {
    echo "Valor capturado!";
}
```

### Capturar Parcial

```php
// Captura apenas parte do valor
$capture = $hub->capturePreAuthorization(
    transactionId: $transactionId,
    amount: 300.00  // Captura R$ 300 de R$ 500 reservados
);
```

### Cancelar Reserva

```php
$cancel = $hub->cancelPreAuthorization($transactionId);

if ($cancel->isSuccess()) {
    echo "Reserva cancelada!";
}
```

---

## 🛡️ Validações

### Número do Cartão

```php
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;

try {
    $card = CardNumber::fromString('4111 1111 1111 1111');
    
    echo "✅ Cartão válido!\n";
    echo "Bandeira: " . $card->brand() . "\n";        // visa
    echo "Ícone: " . $card->brandIcon() . "\n";       // 💳 Visa
    echo "Mascarado: " . $card->masked() . "\n";      // ************1111
    
} catch (InvalidCardNumberException $e) {
    echo "❌ Cartão inválido!";
}
```

### Bandeiras Suportadas

```php
$card = CardNumber::fromString('4111 1111 1111 1111');

match($card->brand()) {
    'visa' => echo "💳 Visa",
    'mastercard' => echo "💳 Mastercard",
    'amex' => echo "💳 American Express",
    'elo' => echo "💳 Elo",
    'hipercard' => echo "💳 Hipercard",
    'discover' => echo "💳 Discover",
    'diners' => echo "💳 Diners Club",
    default => echo "💳 Desconhecida"
};
```

---

## 📝 Exemplo Completo - Checkout

```php
class CheckoutController
{
    public function processCard(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'card_number' => 'required',
            'card_name' => 'required',
            'card_expiry_month' => 'required|numeric|between:1,12',
            'card_expiry_year' => 'required|numeric',
            'card_cvv' => 'required|numeric|digits_between:3,4',
            'installments' => 'required|integer|min:1|max:12',
            'save_card' => 'boolean',
        ]);
        
        try {
            // Validar cartão
            $card = CardNumber::fromString($validated['card_number']);
            
            // Criar pagamento
            $paymentRequest = CreditCardPaymentRequest::create(
                amount: $validated['amount'],
                currency: Currency::BRL,
                cardNumber: $validated['card_number'],
                cardHolderName: strtoupper($validated['card_name']),
                cardExpiryMonth: $validated['card_expiry_month'],
                cardExpiryYear: $validated['card_expiry_year'],
                cardCvv: $validated['card_cvv'],
                installments: $validated['installments'],
                capture: true,
                description: "Pedido #{$request->user()->cart->id}",
                customerEmail: $request->user()->email,
                customerDocument: $request->user()->document,
                metadata: [
                    'user_id' => $request->user()->id,
                    'cart_id' => $request->user()->cart->id,
                ]
            );
            
            $response = $this->hub->createCreditCardPayment($paymentRequest);
            
            if ($response->isSuccess()) {
                // Criar pedido
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'transaction_id' => $response->transactionId,
                    'amount' => $response->amount,
                    'installments' => $validated['installments'],
                    'card_brand' => $card->brand(),
                    'card_last4' => substr($validated['card_number'], -4),
                    'status' => 'paid',
                ]);
                
                // Salvar cartão (se solicitado)
                if ($validated['save_card'] ?? false) {
                    $token = $this->hub->tokenizeCard([
                        'card_number' => $validated['card_number'],
                        'card_holder_name' => $validated['card_name'],
                        'card_expiry_month' => $validated['card_expiry_month'],
                        'card_expiry_year' => $validated['card_expiry_year'],
                    ]);
                    
                    $request->user()->update([
                        'card_token' => $token,
                        'card_brand' => $card->brand(),
                        'card_last4' => substr($validated['card_number'], -4),
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'transaction_id' => $response->transactionId,
                    'message' => 'Pagamento aprovado!',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->message ?? 'Pagamento recusado',
                ], 422);
            }
            
        } catch (InvalidCardNumberException $e) {
            return response()->json([
                'error' => 'Número de cartão inválido',
            ], 422);
            
        } catch (GatewayException $e) {
            Log::error('Card payment failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);
            
            return response()->json([
                'error' => 'Erro ao processar pagamento',
                'message' => 'Tente novamente',
            ], 500);
        }
    }
}
```

---

## 🎨 Formulário HTML

```html
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Cartão de Crédito</title>
    <style>
        .checkout-form {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .row {
            display: flex;
            gap: 10px;
        }
        .col-6 {
            flex: 1;
        }
        .col-3 {
            flex: 0 0 30%;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: #32BCAD;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn:hover {
            background: #28a89a;
        }
        .card-icon {
            font-size: 24px;
            float: right;
        }
    </style>
</head>
<body>
    <form class="checkout-form" id="checkoutForm">
        <h2>Pagamento com Cartão</h2>
        
        <div class="form-group">
            <label>Valor Total</label>
            <input type="text" value="R$ 250,00" readonly>
        </div>
        
        <div class="form-group">
            <label>
                Número do Cartão
                <span class="card-icon" id="cardBrand"></span>
            </label>
            <input 
                type="text" 
                id="cardNumber" 
                placeholder="1234 5678 9012 3456"
                maxlength="19"
                required
            >
        </div>
        
        <div class="form-group">
            <label>Nome no Cartão</label>
            <input 
                type="text" 
                id="cardName" 
                placeholder="JOAO SILVA"
                required
            >
        </div>
        
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Validade</label>
                    <div class="row">
                        <div class="col-6">
                            <select id="cardMonth" required>
                                <option value="">Mês</option>
                                <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>">
                                    <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <select id="cardYear" required>
                                <option value="">Ano</option>
                                <?php for($i=2024; $i<=2034; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="form-group">
                    <label>CVV</label>
                    <input 
                        type="text" 
                        id="cardCvv" 
                        placeholder="123"
                        maxlength="4"
                        required
                    >
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Parcelas</label>
            <select id="installments" required>
                <option value="1">1x de R$ 250,00 (sem juros)</option>
                <option value="2">2x de R$ 125,00 (sem juros)</option>
                <option value="3">3x de R$ 83,33 (sem juros)</option>
                <option value="4">4x de R$ 62,50 (sem juros)</option>
                <option value="5">5x de R$ 50,00 (sem juros)</option>
                <option value="6">6x de R$ 41,67 (sem juros)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" id="saveCard">
                Salvar cartão para próximas compras
            </label>
        </div>
        
        <button type="submit" class="btn">
            🔒 Pagar Agora
        </button>
    </form>
    
    <script>
        // Formatar número do cartão
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formatted;
            
            // Detectar bandeira
            detectCardBrand(value);
        });
        
        function detectCardBrand(number) {
            const brands = {
                'visa': /^4/,
                'mastercard': /^5[1-5]/,
                'amex': /^3[47]/,
                'elo': /^(4011|4312|4389|4514|4576|5041|5066|5067|6277|6362|6363|6504|6505|6516)/,
            };
            
            const icons = {
                'visa': '💳 Visa',
                'mastercard': '💳 Mastercard',
                'amex': '💳 Amex',
                'elo': '💳 Elo',
            };
            
            for (let [brand, regex] of Object.entries(brands)) {
                if (regex.test(number)) {
                    document.getElementById('cardBrand').textContent = icons[brand];
                    return;
                }
            }
            
            document.getElementById('cardBrand').textContent = '';
        }
        
        // Submit
        document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                amount: 250.00,
                card_number: document.getElementById('cardNumber').value.replace(/\s/g, ''),
                card_name: document.getElementById('cardName').value,
                card_expiry_month: document.getElementById('cardMonth').value,
                card_expiry_year: document.getElementById('cardYear').value,
                card_cvv: document.getElementById('cardCvv').value,
                installments: document.getElementById('installments').value,
                save_card: document.getElementById('saveCard').checked,
            };
            
            try {
                const response = await fetch('/api/checkout/card', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = `/order/${result.order_id}/success`;
                } else {
                    alert(result.message || 'Pagamento recusado');
                }
                
            } catch (error) {
                alert('Erro ao processar pagamento');
            }
        });
    </script>
</body>
</html>
```

---

## 🔧 Tratamento de Erros

```php
try {
    $response = $hub->createCreditCardPayment($request);
    
} catch (InvalidCardNumberException $e) {
    // Cartão inválido
    return ['error' => 'Número do cartão inválido'];
    
} catch (GatewayException $e) {
    // Erro no gateway
    if (str_contains($e->getMessage(), 'insufficient_funds')) {
        return ['error' => 'Saldo insuficiente'];
    }
    if (str_contains($e->getMessage(), 'card_declined')) {
        return ['error' => 'Cartão recusado'];
    }
    
    return ['error' => 'Erro ao processar pagamento'];
}
```

---

## 📊 Casos de Uso

### Hotel - Pré-Autorização

```php
// Check-in: reservar valor
$request = CreditCardPaymentRequest::create(
    amount: 500.00,
    currency: Currency::BRL,
    // ... dados do cartão
    capture: false  // Só autoriza
);

$auth = $hub->createCreditCardPayment($request);

// Check-out: capturar valor real
$capture = $hub->capturePreAuthorization(
    $auth->transactionId,
    amount: 387.50  // Valor real da estadia
);
```

### E-commerce - One Click Buy

```php
// Usar cartão salvo
$user = auth()->user();

if ($user->card_token) {
    $request = CreditCardPaymentRequest::create(
        amount: 99.90,
        currency: Currency::BRL,
        cardToken: $user->card_token,
        installments: 1,
        capture: true
    );
    
    $response = $hub->createCreditCardPayment($request);
}
```

---

## 🎯 Próximos Passos

- [**Assinaturas**](subscriptions.md)
- [**Antifraude**](../advanced/antifraud.md)
- [**3DS**](../advanced/3ds.md)
