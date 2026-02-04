# 📄 Boleto Bancário

Gere boletos com juros, multa e desconto.

---

## 🚀 Boleto Básico

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

$request = new BoletoPaymentRequest(
    amount: 500.00,
    currency: Currency::BRL->value,
    dueDate: '2026-03-15',
    description: 'Mensalidade Março/2026',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com'
);

$response = $hub->createBoleto($request);

if ($response->isSuccess()) {
    echo "Boleto gerado!\n";
    echo "URL: " . $hub->getBoletoUrl($response->transactionId) . "\n";
}
```

---

## 💰 Juros e Multa

```php
$request = new BoletoPaymentRequest(
    amount: 500.00,
    currency: Currency::BRL->value,
    dueDate: '2026-03-15',
    description: 'Mensalidade',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com',
    finePercentage: 2.0,      // 2% de multa
    interestPercentage: 1.0   // 1% ao mês
);
```

---

## 🎁 Desconto

```php
$request = new BoletoPaymentRequest(
    amount: 500.00,
    currency: Currency::BRL->value,
    dueDate: '2026-03-15',
    description: 'Mensalidade',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com',
    discountAmount: 50.00,           // R$ 50 de desconto
    discountLimitDate: '2026-03-10'  // Até 10/03
);

// Se pagar até 10/03: R$ 450,00
// Se pagar após 10/03: R$ 500,00
```

---

## 📊 Dados do Boleto

```php
$response = $hub->createBoleto($request);

if ($response->isSuccess()) {
    $transactionId = $response->transactionId;
    
    // URL do boleto (PDF)
    $url = $hub->getBoletoUrl($transactionId);
    
    // Código de barras
    $barcode = $response->rawResponse['barcode'] ?? null;
    
    echo "URL: {$url}\n";
    echo "Código: {$barcode}\n";
}
```

---

## 🔗 Enviando para Cliente

```php
// Email com link
Mail::to($customer->email)->send(new BoletoEmail([
    'url' => $hub->getBoletoUrl($transactionId),
    'barcode' => $response->rawResponse['barcode'],
    'due_date' => '15/03/2026',
    'amount' => 'R$ 500,00'
]));

// SMS
SMS::send($customer->phone, 
    "Boleto disponível: " . $hub->getBoletoUrl($transactionId)
);
```

---

## 🎨 Exemplo HTML

```html
<!DOCTYPE html>
<html>
<head>
    <title>Boleto - Pagamento</title>
</head>
<body>
    <div class="boleto-container">
        <h2>Boleto Bancário</h2>
        
        <div class="info">
            <p><strong>Valor:</strong> R$ 500,00</p>
            <p><strong>Vencimento:</strong> 15/03/2026</p>
            <p><strong>Desconto até 10/03:</strong> R$ 50,00</p>
        </div>
        
        <div class="barcode">
            <p>Código de barras:</p>
            <code><?= $barcode ?></code>
            <button onclick="copiarCodigo()">📋 Copiar</button>
        </div>
        
        <div class="actions">
            <a href="<?= $url ?>" class="btn" target="_blank">
                📄 Visualizar Boleto
            </a>
            <a href="<?= $url ?>" class="btn" download>
                💾 Baixar PDF
            </a>
        </div>
    </div>
    
    <script>
        function copiarCodigo() {
            const codigo = '<?= $barcode ?>';
            navigator.clipboard.writeText(codigo);
            alert('Código copiado!');
        }
    </script>
</body>
</html>
```

---

## ❌ Cancelar Boleto

```php
$cancel = $hub->cancelBoleto($transactionId);

if ($cancel->isSuccess()) {
    echo "Boleto cancelado!";
}
```

---

## 📅 Múltiplos Vencimentos

```php
// Mensalidades
$months = ['03', '04', '05', '06'];

foreach ($months as $month) {
    $request = new BoletoPaymentRequest(
        amount: 500.00,
        currency: Currency::BRL->value,
        dueDate: "2026-{$month}-15",
        description: "Mensalidade {$month}/2026",
        customerName: 'João Silva',
        customerDocument: '123.456.789-00',
        customerEmail: 'joao@email.com'
    );
    
    $response = $hub->createBoleto($request);
    
    // Salvar no banco
    Boleto::create([
        'transaction_id' => $response->transactionId,
        'month' => $month,
        'url' => $hub->getBoletoUrl($response->transactionId),
    ]);
}
```

---

## 💡 Exemplo Completo - Escola

```php
class TuitionController
{
    public function generate(Request $request)
    {
        $student = Student::find($request->student_id);
        
        // Gerar 12 mensalidades
        $boletos = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $dueDate = now()->setMonth($month)->setDay(10);
            
            $boletoRequest = new BoletoPaymentRequest(
                amount: 500.00,
                currency: Currency::BRL->value,
                dueDate: $dueDate->format('Y-m-d'),
                description: "Mensalidade {$dueDate->format('m/Y')}",
                customerName: $student->name,
                customerDocument: $student->cpf,
                customerEmail: $student->email,
                finePercentage: 2.0,
                interestPercentage: 1.0,
                discountAmount: 50.00,
                discountLimitDate: $dueDate->copy()->subDays(5)->format('Y-m-d')
            );
            
            $response = $this->hub->createBoleto($boletoRequest);
            
            if ($response->isSuccess()) {
                $boleto = Tuition::create([
                    'student_id' => $student->id,
                    'transaction_id' => $response->transactionId,
                    'amount' => 500.00,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'url' => $this->hub->getBoletoUrl($response->transactionId),
                ]);
                
                $boletos[] = $boleto;
            }
        }
        
        // Enviar email com todos os boletos
        Mail::to($student->email)->send(new TuitionBoletos($boletos));
        
        return view('boletos.generated', compact('boletos'));
    }
}
```

---

## 🔔 Webhook - Confirmação

```php
// webhook.php

if ($event['type'] === 'boleto.paid') {
    $transactionId = $event['data']['transaction_id'];
    
    $tuition = Tuition::where('transaction_id', $transactionId)->first();
    
    if ($tuition) {
        $tuition->update(['status' => 'paid']);
        
        // Notificar aluno
        Mail::to($tuition->student->email)
            ->send(new PaymentConfirmed($tuition));
    }
}
```

---

## 📊 Vantagens do Boleto

- ✅ Não precisa cartão
- ✅ Aceito por todos os bancos
- ✅ Cliente paga quando quiser (até vencer)
- ✅ Menos taxas que cartão
- ✅ Bom para valores altos

---

## ❌ Desvantagens

- ⏱️ Confirmação em 1-3 dias úteis
- 📅 Cliente pode esquecer de pagar
- 🏦 Precisa ir ao banco ou app
- 💰 Taxa de emissão

---

## 🎯 Quando Usar

### ✅ Use Boleto Para

- Mensalidades recorrentes
- Valores altos (> R$ 500)
- Clientes sem cartão
- Pagamentos parcelados manualmente

### ❌ Prefira PIX/Cartão Para

- Urgência (PIX é instantâneo)
- Valores baixos (< R$ 100)
- E-commerce (cliente quer comprar agora)

---

## 🔧 Tratamento de Erros

```php
try {
    $response = $hub->createBoleto($request);
    
} catch (InvalidDocumentException $e) {
    return ['error' => 'CPF/CNPJ inválido'];
    
} catch (GatewayException $e) {
    Log::error('Boleto failed', [
        'error' => $e->getMessage(),
    ]);
    
    return ['error' => 'Erro ao gerar boleto'];
}
```

---

## 🎯 Próximos Passos

- [**PIX**](pix.md) - Alternativa instantânea
- [**Assinaturas**](subscriptions.md) - Recorrência automática
- [**Webhooks**](../advanced/webhooks.md) - Confirmação automática
