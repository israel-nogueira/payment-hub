# 💳 Cartão de Débito

Aceite pagamentos com cartão de débito online.

---

## 🚀 Pagamento Básico

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\DebitCardPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

$request = new DebitCardPaymentRequest(
    amount: 150.00,
    currency: Currency::BRL->value,
    cardNumber: '4111 1111 1111 1111',
    cardHolderName: 'JOAO SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    description: 'Compra com débito',
    customerEmail: 'joao@email.com',
    customerDocument: '123.456.789-00'
);

$response = $hub->createDebitCardPayment($request);

if ($response->isSuccess()) {
    echo "✅ Pagamento aprovado!\n";
    echo "ID: {$response->transactionId}\n";
}
```

---

## 🔐 Características

### À Vista

Débito é **sempre à vista** - não tem parcelamento:

```php
// ✅ Correto
$request = new DebitCardPaymentRequest(
    amount: 100.00,
    currency: Currency::BRL->value,
    // ... dados do cartão
);

// ❌ Não existe parcelamento em débito
```

### Aprovação Instantânea

```php
$response = $hub->createDebitCardPayment($request);

// Se aprovado, é instantâneo
if ($response->status->isPaid()) {
    echo "Pagamento confirmado imediatamente!";
}
```

---

## 🛡️ Validações

```php
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;

// Validar cartão
$card = CardNumber::fromString('4111 1111 1111 1111');

if ($card->brand() === 'visa') {
    echo "Cartão Visa aceito para débito";
}
```

---

## 💡 Exemplo Prático

```php
class DebitPaymentController
{
    public function process(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'card_number' => 'required',
            'card_name' => 'required',
            'card_month' => 'required',
            'card_year' => 'required',
            'card_cvv' => 'required',
        ]);
        
        try {
            $paymentRequest = new DebitCardPaymentRequest(
                amount: $validated['amount'],
                currency: Currency::BRL->value,
                cardNumber: $validated['card_number'],
                cardHolderName: strtoupper($validated['card_name']),
                cardExpiryMonth: $validated['card_month'],
                cardExpiryYear: $validated['card_year'],
                cardCvv: $validated['card_cvv'],
                description: 'Compra com débito',
                customerEmail: $request->user()->email,
                customerDocument: $request->user()->document
            );
            
            $response = $this->hub->createDebitCardPayment($paymentRequest);
            
            if ($response->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'transaction_id' => $response->transactionId,
                ]);
            }
            
        } catch (GatewayException $e) {
            return response()->json([
                'error' => 'Pagamento recusado'
            ], 422);
        }
    }
}
```

---

## 📊 Diferenças: Débito vs Crédito

| Característica | Débito | Crédito |
|---------------|--------|---------|
| Parcelamento | ❌ Não | ✅ Sim |
| Aprovação | ⚡ Instantânea | ⏱️ Pode demorar |
| Pré-autorização | ❌ Não | ✅ Sim |
| Taxa | 💰 Menor | 💰 Maior |
| Saldo | 🏦 Precisa ter | ❌ Não precisa |

---

## 🎯 Quando Usar

### ✅ Use Débito

- Valores baixos a médios
- Precisa de confirmação instantânea
- Cliente prefere não parcelar
- Menor taxa de processamento

### ❌ Não Use Débito

- Valores altos (use crédito parcelado)
- Pré-autorização necessária
- Cliente pode não ter saldo

---

## 🔧 Tratamento de Erros

```php
try {
    $response = $hub->createDebitCardPayment($request);
    
} catch (InvalidCardNumberException $e) {
    return ['error' => 'Cartão inválido'];
    
} catch (GatewayException $e) {
    if (str_contains($e->getMessage(), 'insufficient_funds')) {
        return ['error' => 'Saldo insuficiente'];
    }
    
    return ['error' => 'Pagamento recusado'];
}
```

---

## 🎯 Próximos Passos

- [**Boleto**](boleto.md)
- [**PIX**](pix.md)
- [**Cartão de Crédito**](credit-card.md)
