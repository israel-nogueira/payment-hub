# PaymentHub 💳

![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-active-success)
![Type Safe](https://img.shields.io/badge/type--safe-100%25-brightgreen)

**PaymentHub** é um adaptador unificado para integração com múltiplos gateways de pagamento brasileiros e internacionais. Com uma interface única e padronizada, você pode alternar entre diferentes provedores de pagamento sem reescrever seu código.

---

## ✨ Características

- 🔌 **Plug & Play**: Interface única para múltiplos gateways
- 🎯 **Type-Safe**: PHP 8.3+ com Enums e Type Hints completos
- 🛡️ **Validações Robustas**: ValueObjects com validação automática (CPF, CNPJ, Email, Cartão)
- 📦 **DTOs Imutáveis**: Requisições e respostas tipadas e padronizadas
- 💰 **Money Object**: Manipulação segura de valores monetários
- 🧪 **Testável**: Gateway fake incluso para testes locais
- 🚀 **Extensível**: Fácil adicionar novos gateways
- 🇧🇷 **Brasil First**: Suporte completo a PIX, Boleto e validação de documentos brasileiros
- 🌍 **Internacional**: Suporte a cartões internacionais e múltiplas moedas

---

## 🎯 Diferenciais

### 🔒 Type-Safety Completo
```php
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;

// Enums previnem typos e erros
$currency = Currency::BRL;  // ✅ Type-safe
$status = PaymentStatus::PAID;  // ✅ Autocomplete na IDE
```

### 🛡️ Validações Automáticas
```php
use IsraelNogueira\PaymentHub\ValueObjects\{CPF, CardNumber, Email};

// Lança exceção se inválido
$cpf = CPF::fromString('123.456.789-00');
$card = CardNumber::fromString('4111 1111 1111 1111');
$email = Email::fromString('joao@email.com');
```

### 💰 Money Object
```php
use IsraelNogueira\PaymentHub\ValueObjects\Money;

$price = Money::from(100.00, Currency::BRL);
$discount = $price->percentage(10);  // 10%
$total = $price->subtract($discount);

echo $total->formatted();  // R$ 90,00
```

---

## 📋 Funcionalidades

### Métodos de Pagamento
- ✅ **PIX** (QR Code dinâmico/estático, copia e cola)
- ✅ **Cartão de Crédito** (parcelado, tokenização, 3DS)
- ✅ **Cartão de Débito**
- ✅ **Boleto** (com juros, multa e desconto)

### Recursos Avançados
- 🔄 **Assinaturas/Recorrência** (com trial)
- 💰 **Split de Pagamento** (marketplace)
- 🏦 **Sub-contas** (onboarding de sellers)
- 👛 **Wallets** (carteiras digitais)
- 🔒 **Escrow** (custódia de valores)
- 🔗 **Links de Pagamento**
- ↩️ **Estornos** (total e parcial)
- 🚨 **Chargebacks** (disputa)
- 📤 **Transferências** (PIX, TED, agendadas)
- 👤 **Gestão de Clientes**
- 🛡️ **Antifraude**
- 📢 **Webhooks**
- 💵 **Consulta de Saldo**

### Validações e Type-Safety
- ✅ **Enums**: `Currency`, `PaymentStatus`, `PaymentMethod`, `SubscriptionInterval`
- ✅ **ValueObjects**: `Money`, `CardNumber`, `CPF`, `CNPJ`, `Email`
- ✅ **Exceptions**: Tratamento de erros específico e contextual

---

## 📦 Instalação

```bash
composer require israel-nogueira/payment-hub
```

---

## 🚀 Uso Rápido

### Configuração Básica
```php
<?php

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;

// Inicializa com gateway fake (para testes)
$hub = new PaymentHub(new FakeBankGateway());

// Ou troque para gateway real quando estiver pronto
// $hub = new PaymentHub(new StripeGateway($apiKey));
// $hub = new PaymentHub(new PagarMeGateway($apiKey));
```

---

## 💳 Exemplos de Uso

### 1️⃣ PIX com Validações Automáticas

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

// ✅ Cria com validações automáticas
$request = PixPaymentRequest::create(
    amount: 100.50,
    currency: Currency::BRL,  // Enum type-safe
    description: 'Pagamento do pedido #123',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',  // Valida CPF automaticamente
    customerEmail: 'joao@email.com',  // Valida email automaticamente
    expiresInMinutes: 30
);

$response = $hub->createPixPayment($request);

if ($response->status->isPaid()) {  // Enum method
    echo "Transaction ID: " . $response->transactionId . "\n";
    echo "Status: " . $response->status->label() . "\n";  // "Aprovado"
    echo "Valor: " . $response->getFormattedAmount() . "\n";  // "R$ 100,50"
    
    // Obter QR Code
    echo "QR Code: " . $hub->getPixQrCode($response->transactionId) . "\n";
    echo "Copia e Cola: " . $hub->getPixCopyPaste($response->transactionId) . "\n";
}
```

---

### 2️⃣ Cartão de Crédito com Validação de Cartão

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\Enums\Currency;

// ✅ Valida número do cartão automaticamente (Luhn algorithm)
$request = CreditCardPaymentRequest::create(
    amount: 250.00,
    currency: Currency::BRL,
    cardNumber: '4111 1111 1111 1111',  // Valida automaticamente
    cardHolderName: 'JOAO SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    installments: 3,
    capture: true,
    description: 'Compra parcelada',
    customerEmail: 'joao@email.com'  // Valida email
);

$response = $hub->createCreditCardPayment($request);

if ($response->isSuccess()) {
    echo "Pagamento aprovado! ID: " . $response->transactionId . "\n";
    echo "Bandeira: " . $request->getCardBrand() . "\n";  // "visa"
    echo "Cartão: " . $request->getCardMasked() . "\n";  // "**** **** **** 1111"
    echo "Parcelas: " . $request->getFormattedDescription() . "\n";
}
```

---

### 3️⃣ Boleto

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

$request = new BoletoPaymentRequest(
    amount: 500.00,
    currency: Currency::BRL->value,
    dueDate: '2026-03-15',
    description: 'Mensalidade',
    customerName: 'João Silva',
    customerDocument: '12345678900',
    customerEmail: 'joao@email.com',
    finePercentage: 2.0,
    interestPercentage: 1.0,
    discountAmount: 50.00,
    discountLimitDate: '2026-03-10'
);

$response = $hub->createBoleto($request);

if ($response->isSuccess()) {
    echo "Boleto criado!\n";
    echo "URL: " . $hub->getBoletoUrl($response->transactionId) . "\n";
    echo "Código de barras: " . $response->rawResponse['barcode'] . "\n";
}
```

---

### 4️⃣ Assinatura/Recorrência com Enums

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubscriptionRequest;
use IsraelNogueira\PaymentHub\Enums\{Currency, SubscriptionInterval};

$request = new SubscriptionRequest(
    amount: 99.90,
    currency: Currency::BRL->value,
    interval: SubscriptionInterval::MONTHLY->value,  // Type-safe
    customerId: 'cust_123',
    cardToken: 'tok_abc123',
    description: 'Plano Premium',
    trialDays: 7,
    cycles: 12  // null = ilimitado
);

$response = $hub->createSubscription($request);

if ($response->isSuccess()) {
    echo "Assinatura criada! ID: " . $response->subscriptionId . "\n";
    echo "Status: " . $response->status->label() . "\n";
}
```

---

### 5️⃣ Trabalhando com Money Object

```php
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;

// Criar valor monetário
$productPrice = Money::from(100.00, Currency::BRL);

// Calcular desconto
$discount = $productPrice->percentage(10);  // 10% = R$ 10,00

// Adicionar frete
$shipping = Money::from(15.50, Currency::BRL);

// Calcular total
$total = $productPrice
    ->subtract($discount)
    ->add($shipping);

echo "Produto: " . $productPrice->formatted() . "\n";  // R$ 100,00
echo "Desconto: " . $discount->formatted() . "\n";     // R$ 10,00
echo "Frete: " . $shipping->formatted() . "\n";        // R$ 15,50
echo "Total: " . $total->formatted() . "\n";           // R$ 105,50

// Dividir em parcelas
$installments = $total->split(3);
foreach ($installments as $i => $value) {
    echo "Parcela " . ($i + 1) . ": " . $value->formatted() . "\n";
}
```

---

### 6️⃣ Validação de Documentos Brasileiros

```php
use IsraelNogueira\PaymentHub\ValueObjects\{CPF, CNPJ};
use IsraelNogueira\PaymentHub\Exceptions\InvalidDocumentException;

try {
    // Valida CPF
    $cpf = CPF::fromString('123.456.789-00');
    echo "CPF válido: " . $cpf->formatted() . "\n";  // 123.456.789-00
    echo "Mascarado: " . $cpf->masked() . "\n";      // ***.456.789-00
    
    // Valida CNPJ
    $cnpj = CNPJ::fromString('12.345.678/0001-00');
    echo "CNPJ válido: " . $cnpj->formatted() . "\n";
    
} catch (InvalidDocumentException $e) {
    echo "Documento inválido: " . $e->getMessage();
}

// Helper para validar qualquer documento
function validateDocument(string $doc): CPF|CNPJ {
    $cleaned = preg_replace('/\D/', '', $doc);
    return strlen($cleaned) === 11 
        ? CPF::fromString($doc)
        : CNPJ::fromString($doc);
}
```

---

### 7️⃣ Validação de Cartão com Detecção de Bandeira

```php
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\Exceptions\InvalidCardNumberException;

try {
    $card = CardNumber::fromString('4111 1111 1111 1111');
    
    echo "Cartão válido!\n";
    echo "Bandeira: " . $card->brand() . "\n";              // visa
    echo "Ícone: " . $card->brandIcon() . "\n";             // 💳 Visa
    echo "Número mascarado: " . $card->masked() . "\n";     // ************1111
    echo "Formatado: " . $card->formattedMasked() . "\n";   // **** **** **** 1111
    
} catch (InvalidCardNumberException $e) {
    echo "Cartão inválido: " . $e->getMessage();
}

// Bandeiras suportadas:
// - Visa
// - Mastercard
// - Amex
// - Elo
// - Hipercard
// - Discover
// - Diners
```

---

### 8️⃣ Usando Enums de Status

```php
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;

$status = $hub->getTransactionStatus('txn_123');

// Métodos type-safe
if ($status->status->isPaid()) {
    echo "✅ " . $status->status->label() . "\n";  // "Aprovado"
    echo "Cor: " . $status->status->color() . "\n";  // "green"
    
    // Enviar notificação
    sendEmail("Pagamento confirmado!");
}

// Match pattern
$message = match(true) {
    $status->status->isPaid() => "Pagamento aprovado com sucesso!",
    $status->status->isPending() => "Aguardando confirmação do pagamento...",
    $status->status->isFailed() => "Pagamento recusado. Tente novamente.",
    $status->status->isCancelled() => "Pagamento cancelado.",
    default => "Status desconhecido"
};

echo $message;
```

---

### 9️⃣ Trabalhando com Moedas

```php
use IsraelNogueira\PaymentHub\Enums\Currency;

// Informações sobre moedas
$currency = Currency::BRL;

echo "Símbolo: " . $currency->symbol() . "\n";        // R$
echo "Nome: " . $currency->name() . "\n";             // Real Brasileiro
echo "Decimais: " . $currency->decimals() . "\n";    // 2
echo "Formatado: " . $currency->format(1234.56) . "\n";  // R$ 1.234,56

// Verificações
if ($currency->isLatinAmerican()) {
    echo "Moeda latino-americana";
}

// Moedas suportadas:
// BRL, USD, EUR, GBP, ARS, CLP, COP, MXN, PEN, UYU
```

---

### 🔟 Métodos de Pagamento com Enums

```php
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;

$method = PaymentMethod::CREDIT_CARD;

echo "Método: " . $method->label() . "\n";  // "Cartão de Crédito"
echo "Ícone: " . $method->icon() . "\n";    // 💳

// Verificações
if ($method->supportsInstallments()) {
    echo "Suporta parcelamento!\n";
}

if ($method->isInstant()) {
    echo "Aprovação instantânea!\n";
}

// Tempo típico de processamento
echo "Processamento: " . $method->typicalProcessingTime() . " minutos\n";

// Métodos disponíveis por moeda
$availableMethods = PaymentMethod::availableFor(Currency::BRL);
// Retorna: PIX, CREDIT_CARD, DEBIT_CARD, BOLETO, etc.
```

---

## 🛡️ Tratamento de Erros

```php
use IsraelNogueira\PaymentHub\Exceptions\{
    InvalidCardNumberException,
    InvalidDocumentException,
    InvalidEmailException,
    InvalidAmountException,
    GatewayException
};

try {
    // Validação de cartão
    $card = CardNumber::fromString('1234');  // ❌ Inválido
    
} catch (InvalidCardNumberException $e) {
    echo "Cartão inválido: " . $e->getMessage();
    // Retornar erro 422 para o cliente
}

try {
    // Criar pagamento
    $response = $hub->createPixPayment($request);
    
} catch (GatewayException $e) {
    echo "Erro no gateway: " . $e->getGateway() . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Contexto: ";
    print_r($e->getContext());
    
    // Log do erro
    Log::error('Payment failed', [
        'gateway' => $e->getGateway(),
        'response' => $e->getGatewayResponse()
    ]);
}

try {
    // Validação de valor
    $money = Money::from(-100, Currency::BRL);  // ❌ Negativo
    
} catch (InvalidAmountException $e) {
    echo "Valor inválido: " . $e->getMessage();
}
```

---

## 📌 Criando seu Próprio Adapter

```php
<?php

namespace MeuProjeto\Gateways;

use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Responses\PaymentResponse;
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Exceptions\GatewayException;

class MeuGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $apiKey,
        private bool $sandbox = false
    ) {}
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        try {
            // Sua implementação aqui
            $response = $this->apiCall('/pix/create', $request->toArray());
            
            return PaymentResponse::create(
                success: $response['status'] === 'success',
                transactionId: $response['id'],
                status: $response['status'],  // Convertido para Enum automaticamente
                amount: $request->getAmount(),
                currency: $request->getCurrency(),
                message: $response['message'] ?? null,
                rawResponse: $response
            );
            
        } catch (\Exception $e) {
            throw GatewayException::fromResponse([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 'MeuGateway');
        }
    }
    
    // Implemente os outros métodos da interface...
}
```

### Usando seu Gateway
```php
use MeuProjeto\Gateways\MeuGateway;

$hub = new PaymentHub(new MeuGateway('sua-api-key', sandbox: true));
```

---

## 🎯 Gateways Planejados

- [ ] **Stripe**
- [ ] **PagarMe**
- [ ] **MercadoPago**
- [ ] **Asaas**
- [ ] **PagSeguro**
- [ ] **PayPal**
- [ ] **Iugu**
- [ ] **Vindi**
- [ ] **Cielo**
- [ ] **Rede**

**Quer contribuir com um adapter?** Abra um PR! 🚀

---

## 🧪 Testes

```bash
# Executar testes
composer test

# Com coverage
composer test:coverage

# Análise estática
composer analyse
```

---

## 📚 Documentação Completa

### Estrutura do Projeto

```
src/
├── Contracts/           # Interfaces
├── DataObjects/
│   ├── Requests/       # DTOs de requisição
│   └── Responses/      # DTOs de resposta
├── Enums/              # Enums type-safe
│   ├── Currency.php
│   ├── PaymentMethod.php
│   ├── PaymentStatus.php
│   └── SubscriptionInterval.php
├── ValueObjects/       # Value Objects com validação
│   ├── CardNumber.php
│   ├── CNPJ.php
│   ├── CPF.php
│   ├── Email.php
│   └── Money.php
├── Exceptions/         # Exceptions customizadas
├── Gateways/          # Implementações de gateways
└── PaymentHub.php     # Classe principal
```

### Recursos Disponíveis

#### Enums
- ✅ `Currency` - Moedas suportadas
- ✅ `PaymentStatus` - Status de pagamento
- ✅ `PaymentMethod` - Métodos de pagamento
- ✅ `SubscriptionInterval` - Intervalos de assinatura

#### ValueObjects
- ✅ `Money` - Valores monetários seguros
- ✅ `CardNumber` - Validação de cartão (Luhn)
- ✅ `CPF` - Validação de CPF
- ✅ `CNPJ` - Validação de CNPJ
- ✅ `Email` - Validação de e-mail

#### Exceptions
- ✅ `PaymentHubException` - Base exception
- ✅ `GatewayException` - Erros de gateway
- ✅ `InvalidCardNumberException` - Cartão inválido
- ✅ `InvalidDocumentException` - CPF/CNPJ inválido
- ✅ `InvalidEmailException` - E-mail inválido
- ✅ `InvalidAmountException` - Valor inválido

---

## 🤝 Contribuindo

Contribuições são muito bem-vindas! 

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/NovoGateway`)
3. Commit suas mudanças (`git commit -m 'Adiciona gateway X'`)
4. Push para a branch (`git push origin feature/NovoGateway`)
5. Abra um Pull Request

### Diretrizes

- Siga PSR-12
- Adicione testes para novas features
- Documente usando PHPDoc
- Use type hints em tudo
- Valide com PHPStan level 8

---

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para mais detalhes.

---

## 👨‍💻 Autor

**Israel Nogueira**  
📧 israel@feats.com.br  
🐙 [GitHub](https://github.com/israel-nogueira)

---

## ⭐ Mostre seu Apoio

Se este projeto te ajudou, deixe uma ⭐ no repositório!

---

## 🔗 Links Úteis

- [Documentação do Composer](https://getcomposer.org/doc/)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)
- [PHP 8.3 Release Notes](https://www.php.net/releases/8.3/en.php)
- [PHP Enums Documentation](https://www.php.net/manual/en/language.types.enumerations.php)

---

## 🎓 Aprenda Mais

### Tutoriais
- [Como criar um gateway customizado](docs/creating-gateway.md)
- [Validações avançadas com ValueObjects](docs/value-objects.md)
- [Usando Enums efetivamente](docs/enums.md)
- [Tratamento de erros](docs/error-handling.md)

### Exemplos
- [Integração com Laravel](examples/laravel/)
- [Integração com Symfony](examples/symfony/)
- [API REST completa](examples/rest-api/)
- [Sistema de marketplace](examples/marketplace/)

---

**Feito com ❤️ para a comunidade PHP brasileira**

*Type-safe, validado e pronto para produção!* 🚀t e s t  
 