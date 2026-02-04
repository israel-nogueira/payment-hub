# 🧠 Conceitos Básicos

Entenda como o PaymentHub funciona por baixo dos panos.

---

## 🎯 Arquitetura Simples

```
Seu Código
    ↓
PaymentHub (Orquestrador)
    ↓
Gateway (Stripe, PagarMe, etc)
    ↓
Processador de Pagamento
```

O PaymentHub funciona como um **tradutor universal** entre seu código e qualquer gateway de pagamento.

---

## 🔌 Gateway Pattern

### O Problema

Sem PaymentHub, trocar de gateway dói:

```php
// Com Stripe
$stripe = new \Stripe\StripeClient($key);
$payment = $stripe->paymentIntents->create([
    'amount' => 10000,
    'currency' => 'brl',
]);

// Quer trocar para PagarMe? Reescreve TUDO! 😱
$pagarme = new PagarMe\Client($key);
$transaction = $pagarme->transactions()->create([
    'amount' => 10000,
    // API completamente diferente...
]);
```

### A Solução

Com PaymentHub, você escreve uma vez:

```php
// Com qualquer gateway
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    // ...
);

$response = $hub->createPixPayment($request);

// Trocar gateway? Só muda a instanciação!
// $hub = new PaymentHub(new StripeGateway($key));
// $hub = new PaymentHub(new PagarMeGateway($key));
// Seu código continua igual! 🎉
```

---

## 🧱 Blocos de Construção

### 1. PaymentHub (Orquestrador)

O cérebro que coordena tudo:

```php
$hub = new PaymentHub($gateway);

// Delega para o gateway
$hub->createPixPayment($request);
$hub->createCreditCardPayment($request);
$hub->getTransactionStatus($id);
```

### 2. Gateway (Adaptador)

Implementa a interface e conversa com o provider:

```php
class StripeGateway implements PaymentGatewayInterface
{
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        // 1. Converte para formato do Stripe
        $stripeData = $this->convertToStripeFormat($request);
        
        // 2. Chama API do Stripe
        $result = $this->stripe->paymentIntents->create($stripeData);
        
        // 3. Converte resposta para PaymentResponse
        return PaymentResponse::create(/* ... */);
    }
}
```

### 3. Request (Entrada)

Dados que você envia:

```php
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    customerDocument: '123.456.789-00'
);

// É um DTO (Data Transfer Object) imutável
// Não dá para mudar depois de criado!
```

### 4. Response (Saída)

Dados que você recebe:

```php
$response = $hub->createPixPayment($request);

$response->isSuccess()      // true/false
$response->transactionId    // ID da transação
$response->status          // PaymentStatus enum
$response->message         // Mensagem
```

---

## 🛡️ Type-Safety

### Por Que Type-Safe?

```php
// ❌ Sem type-safety (perigo!)
$status = 'paid';  // E se digitar 'piad'? Bug!
$currency = 'BRL';  // E se digitar 'BRl'? Erro!

// ✅ Com type-safety (seguro!)
$status = PaymentStatus::PAID;  // Autocomplete na IDE
$currency = Currency::BRL;      // Impossível errar
```

### Enums

```php
// Enum = conjunto fixo de valores
enum Currency: string 
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';
}

// Uso
$currency = Currency::BRL;  // ✅
$currency = Currency::XYZ;  // ❌ Erro de compilação!
```

### Type Hints

```php
// Garante que só aceita o tipo certo
function processar(
    PixPaymentRequest $request,  // Só aceita PixPaymentRequest
    Currency $currency           // Só aceita Currency enum
): PaymentResponse {             // Só retorna PaymentResponse
    // ...
}
```

---

## 💎 ValueObjects

### O Que São?

Objetos que representam **valores** com **validação embutida**.

### CPF

```php
use IsraelNogueira\PaymentHub\ValueObjects\CPF;

// ✅ CPF válido
$cpf = CPF::fromString('123.456.789-00');
echo $cpf->formatted();  // 123.456.789-00
echo $cpf->masked();     // ***.456.789-00
echo $cpf->value();      // 12345678900

// ❌ CPF inválido - lança exceção!
$cpf = CPF::fromString('111.111.111-11');
```

### Email

```php
use IsraelNogueira\PaymentHub\ValueObjects\Email;

$email = Email::fromString('joao@email.com');
echo $email->value();     // joao@email.com
echo $email->domain();    // email.com
echo $email->local();     // joao
```

### CardNumber

```php
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;

$card = CardNumber::fromString('4111 1111 1111 1111');
echo $card->brand();           // visa
echo $card->masked();          // ************1111
echo $card->formattedMasked(); // **** **** **** 1111
```

### Money

```php
use IsraelNogueira\PaymentHub\ValueObjects\Money;

$price = Money::from(100.00, Currency::BRL);
$discount = $price->percentage(10);  // R$ 10,00
$total = $price->subtract($discount); // R$ 90,00

echo $total->formatted(); // R$ 90,00
```

---

## 📦 DTOs (Data Transfer Objects)

### O Que São?

Objetos que **transportam dados** entre camadas, sem lógica de negócio.

### Características

- ✅ **Imutáveis** - Não mudam depois de criados
- ✅ **Validados** - Validação automática na criação
- ✅ **Tipados** - Type hints em tudo
- ✅ **Serializáveis** - Podem virar JSON facilmente

### Exemplo

```php
// Request DTO
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    customerDocument: '123.456.789-00'
);

// Não dá para mudar!
// $request->amount = 200; // ❌ Erro!

// Response DTO
$response = $hub->createPixPayment($request);

// Também imutável
// $response->status = PaymentStatus::PAID; // ❌ Erro!
```

---

## 🔄 Fluxo de um Pagamento

### PIX

```
1. Você cria PixPaymentRequest
   ↓
2. PaymentHub valida os dados
   ↓
3. Gateway converte para formato do provider
   ↓
4. Provider processa e retorna resposta
   ↓
5. Gateway converte resposta para PaymentResponse
   ↓
6. Você recebe PaymentResponse
```

### Código

```php
// 1. Criar request
$request = PixPaymentRequest::create(/* ... */);

// 2-6. PaymentHub faz tudo
$response = $hub->createPixPayment($request);

// 7. Usar resposta
if ($response->isSuccess()) {
    $qrCode = $hub->getPixQrCode($response->transactionId);
}
```

---

## 🎭 FakeBankGateway

### Para Que Serve?

Gateway **fake** para testes **sem gastar dinheiro real**!

```php
// Teste sem custo
$hub = new PaymentHub(new FakeBankGateway());

$response = $hub->createPixPayment($request);
// ✅ Sempre aprova
// ✅ Gera IDs fake
// ✅ Retorna dados fictícios
```

### Quando Usar?

- ✅ Desenvolvimento local
- ✅ Testes automatizados
- ✅ CI/CD
- ✅ Demonstrações

### Quando NÃO Usar?

- ❌ Produção
- ❌ Homologação com cliente
- ❌ Sandbox do provider

---

## 🔐 Validações em Cascata

O PaymentHub valida em múltiplas camadas:

### Camada 1: ValueObjects

```php
// Valida CPF
$cpf = CPF::fromString('123.456.789-00');
// ↓ Se inválido, lança InvalidDocumentException
```

### Camada 2: Request DTOs

```php
$request = PixPaymentRequest::create(
    amount: 100.00,        // Valida se > 0
    customerDocument: $cpf // Já validado!
);
// ↓ Se inválido, lança InvalidAmountException
```

### Camada 3: Gateway

```php
$response = $hub->createPixPayment($request);
// ↓ Gateway valida regras específicas
// ↓ Se erro, lança GatewayException
```

---

## 🎯 Design Patterns Usados

### 1. Adapter Pattern

Gateway adapta APIs diferentes para interface única:

```php
interface PaymentGatewayInterface {
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse;
}

class StripeGateway implements PaymentGatewayInterface { /* ... */ }
class PagarMeGateway implements PaymentGatewayInterface { /* ... */ }
```

### 2. Factory Pattern

```php
// PaymentHubFactory cria instâncias configuradas
$hub = PaymentHubFactory::create('stripe', [
    'api_key' => 'sk_test_xxx'
]);
```

### 3. Value Object Pattern

```php
// Objetos que representam valores
$money = Money::from(100.00, Currency::BRL);
$cpf = CPF::fromString('123.456.789-00');
```

### 4. DTO Pattern

```php
// Objetos que transportam dados
$request = PixPaymentRequest::create(/* ... */);
$response = PaymentResponse::create(/* ... */);
```

---

## 🚀 Benefícios

### Troca Fácil de Gateway

```php
// Hoje
$hub = new PaymentHub(new StripeGateway($key));

// Amanhã (só muda 1 linha!)
$hub = new PaymentHub(new PagarMeGateway($key));

// Seu código não muda! 🎉
```

### Testes Simples

```php
// Teste sem API externa
$hub = new PaymentHub(new FakeBankGateway());
$response = $hub->createPixPayment($request);
assert($response->isSuccess());
```

### Type-Safety

```php
// IDE autocompleta
$status = PaymentStatus::PAID;
$currency = Currency::BRL;

// Erros em tempo de compilação, não runtime!
```

### Validação Automática

```php
// Não precisa validar manualmente
$cpf = CPF::fromString('123.456.789-00');
// ↓ Já validado!

$request = PixPaymentRequest::create(
    customerDocument: '123.456.789-00'
    // ↓ Já validado!
);
```

---

## 🎓 Princípios SOLID

PaymentHub segue SOLID:

- **S** - Cada classe tem uma responsabilidade
- **O** - Extensível sem modificar código existente
- **L** - Gateways são substituíveis
- **I** - Interfaces segregadas por funcionalidade
- **D** - Depende de abstrações (interface), não implementações

---

## 🔍 Próximos Passos

Agora que entendeu os conceitos:

1. [**Configure para produção**](configuration.md)
2. [**Explore os métodos de pagamento**](../guides/pix.md)
3. [**Aprenda sobre Enums**](../guides/enums.md)
4. [**Crie seu próprio Gateway**](../advanced/creating-gateway.md)

---

**Dúvidas?** Consulte o [FAQ](../help/faq.md)!
