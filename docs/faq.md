# ❓ Perguntas Frequentes (FAQ)

Respostas para as dúvidas mais comuns sobre o PaymentHub.

---

## 🚀 Instalação e Configuração

### Como instalar o PaymentHub?

```bash
composer require israel-nogueira/payment-hub
```

Simples assim! Veja o [guia completo de instalação](../getting-started/installation.md).

### Qual versão do PHP preciso?

**PHP 8.3 ou superior**. O PaymentHub usa recursos modernos do PHP como Enums e Type Hints avançados.

### Posso usar em produção?

Sim! Mas lembre-se:
- Use gateways reais (não o FakeBankGateway)
- Configure variáveis de ambiente corretamente
- Implemente webhooks
- Teste tudo em ambiente de homologação primeiro

---

## 💳 Métodos de Pagamento

### Quais métodos de pagamento são suportados?

- ✅ PIX
- ✅ Cartão de Crédito
- ✅ Cartão de Débito
- ✅ Boleto
- ✅ Transferência Bancária
- ✅ Wallets Digitais

### Como aceitar PIX?

```php
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    customerDocument: '123.456.789-00',
    customerEmail: 'cliente@email.com'
);

$response = $hub->createPixPayment($request);
```

Veja o [guia completo de PIX](../guides/pix.md).

### Como fazer parcelamento?

```php
$request = CreditCardPaymentRequest::create(
    amount: 300.00,
    currency: Currency::BRL,
    // ... dados do cartão
    installments: 3  // 3 parcelas
);
```

[Guia de Cartão de Crédito →](../guides/credit-card.md)

---

## 🔧 Uso e Integração

### Como trocar de gateway?

Mude apenas a instanciação:

```php
// Desenvolvimento
$hub = new PaymentHub(new FakeBankGateway());

// Produção - Stripe
$hub = new PaymentHub(new StripeGateway($apiKey));

// Produção - PagarMe
$hub = new PaymentHub(new PagarMeGateway($apiKey));
```

Seu código continua igual!

### Posso usar múltiplos gateways?

Sim!

```php
$hubBrasil = new PaymentHub(new PagarMeGateway($key));
$hubInternacional = new PaymentHub(new StripeGateway($key));

// Use cada um conforme necessário
if ($customer->country === 'BR') {
    $response = $hubBrasil->createPixPayment($request);
} else {
    $response = $hubInternacional->createCreditCardPayment($request);
}
```

### Como salvar cartão do cliente?

```php
// 1. Tokenizar
$token = $hub->tokenizeCard([
    'card_number' => '4111111111111111',
    'card_holder_name' => 'JOAO SILVA',
    'card_expiry_month' => '12',
    'card_expiry_year' => '2028',
]);

// 2. Salvar no banco
$user->update(['card_token' => $token]);

// 3. Usar depois
$request = CreditCardPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    cardToken: $user->card_token  // Usar token
);
```

---

## 🛡️ Validações e Segurança

### Como validar CPF/CNPJ?

Automático! Só criar o ValueObject:

```php
use IsraelNogueira\PaymentHub\ValueObjects\CPF;

try {
    $cpf = CPF::fromString('123.456.789-00');
    echo "CPF válido!";
} catch (InvalidDocumentException $e) {
    echo "CPF inválido!";
}
```

### Como validar número de cartão?

```php
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;

try {
    $card = CardNumber::fromString('4111 1111 1111 1111');
    echo "Cartão válido! Bandeira: " . $card->brand();
} catch (InvalidCardNumberException $e) {
    echo "Cartão inválido!";
}
```

### Os dados do cartão são seguros?

- ✅ **Nunca** salve dados completos do cartão
- ✅ Use **tokenização** do gateway
- ✅ Implemente **PCI-DSS** se processar cartões
- ✅ Use **HTTPS** sempre
- ✅ Configure **webhooks** com assinatura

---

## 💰 Money e Valores

### Por que usar Money ao invés de float?

```php
// ❌ Problema com float
$total = 0.1 + 0.2;
echo $total; // 0.30000000000000004

// ✅ Preciso com Money
$total = Money::from(0.1, Currency::BRL)
    ->add(Money::from(0.2, Currency::BRL));
echo $total->formatted(); // R$ 0,30
```

### Como calcular desconto?

```php
$price = Money::from(100.00, Currency::BRL);
$discount = $price->percentage(10); // 10%

$final = $price->subtract($discount);
echo $final->formatted(); // R$ 90,00
```

### Como dividir em parcelas?

```php
$total = Money::from(100.00, Currency::BRL);
$installments = $total->split(3);

foreach ($installments as $i => $value) {
    echo "Parcela " . ($i + 1) . ": " . $value->formatted() . "\n";
}
```

[Guia completo de Money →](../guides/money.md)

---

## 🔄 Status e Webhooks

### Como verificar status do pagamento?

```php
$status = $hub->getTransactionStatus($transactionId);

if ($status->status->isPaid()) {
    echo "Pago!";
} elseif ($status->status->isPending()) {
    echo "Pendente...";
}
```

### Preciso usar webhooks?

**Sim!** Não confie apenas em polling:

```php
// ❌ Ruim - só polling
while (true) {
    $status = $hub->getTransactionStatus($id);
    if ($status->status->isPaid()) break;
    sleep(5);
}

// ✅ Bom - webhooks
$hub->registerWebhook('https://seu-site.com/webhook', [
    'payment.paid',
    'payment.failed'
]);
```

[Guia de Webhooks →](../advanced/webhooks.md)

### Como processar webhook?

```php
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if ($event['type'] === 'payment.paid') {
    $order = Order::findByTransactionId($event['data']['transaction_id']);
    $order->markAsPaid();
}

http_response_code(200);
```

---

## 🧪 Testes

### Como testar sem gastar dinheiro?

Use o **FakeBankGateway**:

```php
$hub = new PaymentHub(new FakeBankGateway());

$response = $hub->createPixPayment($request);
// Sempre aprovado, sem custo!
```

### Como testar erros?

```php
try {
    $cpf = CPF::fromString('111.111.111-11'); // CPF inválido
} catch (InvalidDocumentException $e) {
    echo "Teste passou!";
}
```

### Tem testes automatizados?

Sim! O projeto vem com suite completa:

```bash
composer test
```

---

## 🌍 Múltiplas Moedas

### Quais moedas são suportadas?

```php
Currency::BRL;  // Real
Currency::USD;  // Dólar
Currency::EUR;  // Euro
Currency::GBP;  // Libra
// E mais...
```

[Ver todas →](../guides/enums.md#currency)

### Como converter moedas?

O PaymentHub **não faz conversão**. Use uma API de câmbio:

```php
$usd = getExchangeRate('BRL', 'USD');
$priceUSD = $priceBRL->divide($usd);
```

### Posso misturar moedas?

```php
// ❌ Erro!
$brl = Money::from(100, Currency::BRL);
$usd = Money::from(20, Currency::USD);
$total = $brl->add($usd); // Exception!

// ✅ Converta antes
$usdInBrl = Money::from(20 * 5.5, Currency::BRL);
$total = $brl->add($usdInBrl);
```

---

## 🔌 Gateways

### Quais gateways estão implementados?

Atualmente:
- ✅ FakeBankGateway (testes)

Em desenvolvimento:
- 🚧 Stripe
- 🚧 PagarMe
- 🚧 MercadoPago

### Como criar meu próprio gateway?

```php
class MeuGateway implements PaymentGatewayInterface
{
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        // Sua implementação
    }
    
    // Implemente os outros métodos...
}
```

[Guia completo →](../advanced/creating-gateway.md)

---

## ⚠️ Erros Comuns

### "Class not found"

```bash
composer dump-autoload
```

### "Invalid document"

```php
// ❌ CPF inválido
$cpf = CPF::fromString('111.111.111-11');

// ✅ Use CPF válido
$cpf = CPF::fromString('123.456.789-00');
```

### "Gateway exception"

```php
try {
    $response = $hub->createPixPayment($request);
} catch (GatewayException $e) {
    Log::error('Payment failed', [
        'error' => $e->getMessage(),
        'gateway' => $e->getGateway(),
    ]);
}
```

### "Currency mismatch"

```php
// ❌ Moedas diferentes
$brl = Money::from(100, Currency::BRL);
$usd = Money::from(20, Currency::USD);
$total = $brl->add($usd); // Erro!

// ✅ Mesma moeda
$value1 = Money::from(100, Currency::BRL);
$value2 = Money::from(50, Currency::BRL);
$total = $value1->add($value2); // OK!
```

---

## 🚀 Performance

### PaymentHub é rápido?

Sim! O overhead é mínimo (< 1ms). O tempo depende do gateway externo.

### Posso cachear tokens?

```php
$token = Cache::remember("card_token_{$userId}", 3600, function() use ($hub) {
    return $hub->tokenizeCard($cardData);
});
```

### Como otimizar?

- Use **webhooks** ao invés de polling
- Cache quando possível
- Use **queue** para operações assíncronas
- Implemente **timeout** nas requisições

---

## 📱 Laravel

### Tem integração com Laravel?

Sim! Crie um Service Provider:

```php
$this->app->singleton(PaymentHub::class, function ($app) {
    $gateway = new StripeGateway(config('payment.stripe.key'));
    return new PaymentHub($gateway);
});
```

[Ver exemplo completo →](../advanced/laravel.md)

### Como usar no Controller?

```php
class CheckoutController extends Controller
{
    public function __construct(
        private PaymentHub $hub
    ) {}
    
    public function pay()
    {
        $response = $this->hub->createPixPayment($request);
        // ...
    }
}
```

---

## 🆘 Suporte

### Onde reportar bugs?

[GitHub Issues](https://github.com/israel-nogueira/payment-hub/issues)

### Como contribuir?

1. Fork o projeto
2. Crie uma branch
3. Faça suas mudanças
4. Abra um Pull Request

[Guia de Contribuição →](contributing.md)

### Tem Discord/Slack?

Em breve! Acompanhe o projeto no GitHub.

---

## 💡 Dicas

### Use Type Hints

```php
// ✅ Bom
function process(PaymentHub $hub, Money $amount): PaymentResponse

// ❌ Evite
function process($hub, $amount)
```

### Valide Cedo

```php
// Valide no início
$cpf = CPF::fromString($input); // Lança exceção se inválido

// Não no meio do processamento
$response = $hub->createPixPayment($request);
```

### Use Enums

```php
// ✅ Type-safe
$status = PaymentStatus::PAID;

// ❌ Perigoso
$status = 'paid';
```

---

**Não encontrou sua dúvida?** 

- 📖 [Consulte a documentação](../README.md)
- 🐛 [Abra uma issue](https://github.com/israel-nogueira/payment-hub/issues)
- 💬 [Inicie uma discussão](https://github.com/israel-nogueira/payment-hub/discussions)
