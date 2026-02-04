<!-- agenciafeats@gmail.com a partir das 16:00 -->


# PaymentHub 💳
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-active-success)
![Type Safe](https://img.shields.io/badge/type--safe-100%25-brightgreen)

**PaymentHub** é um adaptador unificado para integração com múltiplos gateways de pagamento brasileiros e internacionais. Com uma interface única e padronizada, você pode alternar entre diferentes provedores de pagamento sem reescrever seu código.

---

## 📚 Documentação Completa

**[Acesse a documentação completa →](docs/README.md)**

### 🚀 Início Rápido
- [Instalação](docs/getting-started/installation.md) - Configure em 5 minutos
- [Primeiro Pagamento](docs/getting-started/first-payment.md) - PIX em 2 minutos
- [Conceitos Básicos](docs/getting-started/core-concepts.md) - Entenda a arquitetura
- [Configuração](docs/getting-started/configuration.md) - Ambiente de produção

### 📖 Guias de Uso
- [PIX](docs/guides/pix.md) - QR Code e Copia e Cola
- [Cartão de Crédito](docs/guides/credit-card.md) - Parcelamento e Tokenização
- [Boleto](docs/guides/boleto.md) - Juros e Multa
- [Money](docs/guides/money.md) - Valores monetários seguros
- [Enums](docs/guides/enums.md) - Type-safety completo

### 🎯 Exemplos Práticos
- [E-commerce Completo](docs/examples/ecommerce.md) - Checkout ponta a ponta
- [Marketplace](docs/examples/marketplace.md) - Split de pagamento
- [SaaS](docs/examples/saas.md) - Assinaturas recorrentes

### 🆘 Ajuda
- [FAQ](docs/help/faq.md) - Perguntas frequentes
- [Troubleshooting](docs/help/troubleshooting.md) - Resolva problemas

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

## 📦 Instalação

```bash
composer require israel-nogueira/payment-hub
```

---

## 🚀 Exemplo Rápido

```php
<?php

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;

// 1. Instancia
$hub = new PaymentHub(new FakeBankGateway());

// 2. Cria pagamento PIX
$request = PixPaymentRequest::create(
    amount: 100.00,
    currency: Currency::BRL,
    description: 'Meu primeiro PIX',
    customerName: 'João Silva',
    customerDocument: '123.456.789-00',
    customerEmail: 'joao@email.com'
);

// 3. Processa
$response = $hub->createPixPayment($request);

// 4. Usa resultado
if ($response->isSuccess()) {
    echo "✅ PIX criado!\n";
    echo "QR Code: " . $hub->getPixQrCode($response->transactionId) . "\n";
    echo "Copia e Cola: " . $hub->getPixCopyPaste($response->transactionId) . "\n";
}
```

**[Ver exemplo completo →](docs/getting-started/first-payment.md)**

---

## 🎯 Diferenciais

### 🔒 Type-Safety Completo
```php
use IsraelNogueira\PaymentHub\Enums\{Currency, PaymentStatus};

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

**[Saiba mais sobre ValueObjects →](docs/guides/value-objects.md)**

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

---

## 💳 Exemplos de Uso

### PIX com Validações

```php
$request = PixPaymentRequest::create(
    amount: 100.50,
    currency: Currency::BRL,
    customerDocument: '123.456.789-00',  // Valida CPF automaticamente
    customerEmail: 'joao@email.com',     // Valida email automaticamente
);

$response = $hub->createPixPayment($request);

if ($response->status->isPaid()) {
    echo "Valor: " . $response->getFormattedAmount() . "\n";  // "R$ 100,50"
}
```

**[Guia completo de PIX →](docs/guides/pix.md)**

### Cartão de Crédito Parcelado

```php
$request = CreditCardPaymentRequest::create(
    amount: 300.00,
    currency: Currency::BRL,
    cardNumber: '4111 1111 1111 1111',  // Valida automaticamente
    cardHolderName: 'JOAO SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    installments: 3,  // 3x sem juros
);

$response = $hub->createCreditCardPayment($request);

if ($response->isSuccess()) {
    echo "Bandeira: " . $request->getCardBrand() . "\n";  // "visa"
    echo "Parcelas: " . $request->getFormattedDescription() . "\n";  // "3x de R$ 100,00"
}
```

**[Guia completo de Cartão →](docs/guides/credit-card.md)**

### Trabalhando com Money

```php
use IsraelNogueira\PaymentHub\ValueObjects\Money;

$price = Money::from(100.00, Currency::BRL);
$discount = $price->percentage(10);
$shipping = Money::from(15.50, Currency::BRL);

$total = $price
    ->subtract($discount)
    ->add($shipping);

echo $total->formatted();  // R$ 105,50

// Dividir em parcelas
$installments = $total->split(3);
foreach ($installments as $i => $value) {
    echo "Parcela " . ($i + 1) . ": " . $value->formatted() . "\n";
}
```

**[Guia completo de Money →](docs/guides/money.md)**

---

## 🔐 Validações

### CPF/CNPJ

```php
use IsraelNogueira\PaymentHub\ValueObjects\{CPF, CNPJ};

// CPF
$cpf = CPF::fromString('123.456.789-00');
echo $cpf->formatted();  // 123.456.789-00
echo $cpf->masked();     // ***.456.789-00

// CNPJ
$cnpj = CNPJ::fromString('12.345.678/0001-00');
echo $cnpj->formatted();  // 12.345.678/0001-00
```

### Cartão

```php
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;

$card = CardNumber::fromString('4111 1111 1111 1111');

echo $card->brand();           // visa
echo $card->masked();          // ************1111
echo $card->brandIcon();       // 💳 Visa
```

### Email

```php
use IsraelNogueira\PaymentHub\ValueObjects\Email;

$email = Email::fromString('joao@email.com');

echo $email->value();   // joao@email.com
echo $email->domain();  // email.com
```

**[Mais sobre validações →](docs/guides/value-objects.md)**

---

## 🎨 Usando Enums

### Status de Pagamento

```php
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;

$status = $response->status;

if ($status->isPaid()) {
    echo "✅ " . $status->label();  // "Aprovado"
}

// Match pattern
$message = match(true) {
    $status->isPaid() => "Pagamento aprovado!",
    $status->isPending() => "Aguardando confirmação...",
    $status->isFailed() => "Pagamento recusado.",
    default => "Status desconhecido"
};
```

### Moedas

```php
use IsraelNogueira\PaymentHub\Enums\Currency;

$currency = Currency::BRL;

echo $currency->symbol();    // R$
echo $currency->name();      // Real Brasileiro
echo $currency->format(1234.56);  // R$ 1.234,56
```

**[Guia completo de Enums →](docs/guides/enums.md)**

---

## 🔧 Tratamento de Erros

```php
use IsraelNogueira\PaymentHub\Exceptions\{
    InvalidCardNumberException,
    InvalidDocumentException,
    GatewayException
};

try {
    $request = PixPaymentRequest::create(
        amount: 100.00,
        customerDocument: '123.456.789-00',  // Pode ser inválido
    );
    
    $response = $hub->createPixPayment($request);
    
} catch (InvalidDocumentException $e) {
    echo "CPF/CNPJ inválido: " . $e->getMessage();
    
} catch (GatewayException $e) {
    Log::error('Payment failed', [
        'gateway' => $e->getGateway(),
        'error' => $e->getMessage(),
    ]);
    
} catch (\Exception $e) {
    echo "Erro inesperado: " . $e->getMessage();
}
```

**[Guia de tratamento de erros →](docs/guides/error-handling.md)**

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

**[Guia de testes →](docs/advanced/testing.md)**

---

## 📌 Criando seu Gateway

```php
<?php

namespace MeuProjeto\Gateways;

use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;

class MeuGateway implements PaymentGatewayInterface
{
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        // Sua implementação aqui
    }
    
    // Implemente os outros métodos...
}
```

**[Guia completo →](docs/advanced/creating-gateway.md)**

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

## 🤝 Contribuindo

Contribuições são muito bem-vindas!

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/NovoGateway`)
3. Commit suas mudanças (`git commit -m 'Adiciona gateway X'`)
4. Push para a branch (`git push origin feature/NovoGateway`)
5. Abra um Pull Request

**[Guia de contribuição →](docs/help/contributing.md)**

---

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para mais detalhes.

---

## 👨‍💻 Autor

**Israel Nogueira**  
📧 israel@feats.com.br  
🐙 [GitHub](https://github.com/israel-nogueira)

---

## 🔗 Links Úteis

- 📦 [Packagist](https://packagist.org/packages/israel-nogueira/payment-hub)
- 📖 [Documentação Completa](docs/README.md)
- 🐛 [Reportar Bug](https://github.com/israel-nogueira/payment-hub/issues)
- 💬 [Discussões](https://github.com/israel-nogueira/payment-hub/discussions)
- ❓ [FAQ](docs/help/faq.md)

---

## ⭐ Mostre seu Apoio

Se este projeto te ajudou, deixe uma ⭐ no repositório!

---

**Feito com ❤️ para a comunidade PHP brasileira**

*Type-safe, validado e pronto para produção!* 🚀
