# 💳 Payment Hub

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Tests](https://img.shields.io/badge/Tests-Passing-success?style=flat-square)
![Type Safe](https://img.shields.io/badge/Type%20Safe-100%25-blue?style=flat-square)
![Gateways](https://img.shields.io/badge/Gateways-15+-orange?style=flat-square)

**A biblioteca PHP mais simples e elegante para pagamentos no Brasil e no mundo** 🇧🇷🌎

### 📚 Navegação Rápida
[Instalação](#-instalação) • [FakeBank](#-fakebankgateway---desenvolva-offline) • [Gateways](#-gateways-suportados) • [Exemplos](#-exemplos-práticos) • [Documentação](#-documentação-completa)

</div>

---

## 🎯 O Que é o Payment Hub?

**Payment Hub** é a solução definitiva para processar pagamentos em PHP sem dor de cabeça. Com uma **interface única e padronizada**, você integra 15+ gateways e pode trocar entre eles mudando apenas **1 linha de código**.

### 🏦 Gateways Bancários Oficiais

Além dos PSPs tradicionais (Asaas, PagarMe, Stripe), o Payment Hub integra **diretamente com bancos**:

| Banco | Funcionalidades | Documentação |
|-------|-----------------|--------------|
| **Banco do Brasil** | PIX, Boleto, Transferências, Saldo, Extrato, Webhooks | [📖 Docs](src/Gateways/Bancodobrasil/BancoDoBrasilGateway.md) |
| **Itaú Unibanco** | PIX, Boleto, Transferências, Saldo, Extrato, Webhooks, Clientes | [📖 Docs](src/Gateways/Itau/ItauGateway.md) |
| **Bank of America** | Zelle, ACH, Wire (EUA) | [📖 Docs](src/Gateways/BofACashPro/readme.md) |

> 💡 **Diferencial**: Integre diretamente com bancos oficiais, sem intermediários!

---

## 🚀 Gateways Suportados

| Gateway | Status | Métodos | Documentação |
|---------|--------|---------|--------------|
| 🧪 **FakeBank** | ✅ Pronto | **TODOS** (PIX, Cartões, Boleto, Assinaturas, Split, Escrow, Wallets, Sub-contas...) | [📖 Docs](src/Gateways/FakeBank/readme.md) |
| 🟣 **Asaas** | ✅ Pronto | PIX, Cartão Crédito, Boleto, Assinaturas, Split, Sub-contas, Wallets, Escrow, Transferências | [📖 Docs](src/Gateways/Asaas/readme.md) |
| 🟡 **Pagar.me** | ✅ Pronto | PIX, Cartão Crédito/Débito, Boleto, Assinaturas, Split, Recipients, Pre-auth | [📖 Docs](src/Gateways/PagarMe/readme.md) |
| 🟣 **C6 Bank** | ✅ Pronto | PIX, Cartão Crédito/Débito, Boleto, Assinaturas, Split, Sub-contas, Wallets, Escrow | [📖 Docs](src/Gateways/C6bank/readme.md) |
| 💚 **MercadoPago** | ✅ Pronto | PIX, Cartão Crédito/Débito, Boleto, Assinaturas, Split, Pre-auth | [📖 Docs](src/Gateways/MercadoPago/readme.md) |
| 🟠 **PagSeguro** | ✅ Pronto | PIX, Cartão Crédito/Débito, Boleto, Assinaturas, Links | [📖 Docs](src/Gateways/PagSeguro/readme.md) |
| 🔴 **Adyen** | ✅ Pronto | PIX, Cartão Crédito/Débito, Boleto, Payment Links, Pre-auth | [📖 Docs](src/Gateways/Adyen/readme.md) |
| 🔵 **Stripe** | ✅ Pronto | Cartão Crédito, Assinaturas, Payment Links, Pre-auth | [📖 Docs](src/Gateways/Stripe/readme.md) |
| 💙 **PayPal** | ✅ Pronto | Cartão Crédito, Assinaturas, Payouts, Checkout | [📖 Docs](src/Gateways/PayPal/readme.md) |
| 🌎 **EBANX** | ✅ Pronto | PIX, Cartão, Boleto, Multi-país (7 países) | [📖 Docs](src/Gateways/Ebanx/readme.md) |
| 🏦 **Banco do Brasil** | ✅ Pronto | PIX, Boleto, Transferências PIX/TED, Saldo, Extrato, Webhooks | [📖 Docs](src/Gateways/Bancodobrasil/BancoDoBrasilGateway.md) |
| 🏦 **Itaú Unibanco** | ✅ Pronto | PIX, Boleto, Transferências PIX/TED, Saldo, Extrato, Webhooks, Clientes | [📖 Docs](src/Gateways/Itau/ItauGateway.md) |
| 🏦 **BofA CashPro** | ✅ Pronto | Zelle, ACH Same-Day/Standard, Wire, Saldo, Webhooks | [📖 Docs](src/Gateways/BofACashPro/readme.md) |
| 🟣 **NuBank (NuPay)** | ✅ Pronto | Pagamento via app Nubank (redirect), Estornos | [📖 Docs](src/Gateways/NuBank/readme.md) |
| 🟢 **EtherGlobalAssets** | ✅ Pronto | PIX (depósitos e saques) | [📖 Docs](src/Gateways/EtherGlobalAssets/readme.md) |

---

## 🧪 FakeBankGateway - Desenvolva Offline

O **FakeBankGateway** é um gateway simulado que implementa **TODAS** as funcionalidades da biblioteca:

```php
// ✅ Funciona offline — sem internet, sem API keys, sem sandbox
$hub = new PaymentHub(new FakeBankGateway());

// ✅ TODOS os métodos funcionam como na vida real
$pix = $hub->createPixPayment($request);        // Simula PIX
$cc = $hub->createCreditCardPayment($request);  // Simula Cartão
$sub = $hub->createSubscription($request);      // Simula Assinatura
$escrow = $hub->holdInEscrow($request);         // Simula Escrow
$wallet = $hub->createWallet($request);         // Simula Wallet
$split = $hub->createSplitPayment($request);    // Simula Split

// ✅ Persistência opcional em JSON
$gateway = new FakeBankGateway('/tmp/meu-storage');
```

**🎯 Use para:**
- Desenvolver offline
- Testes automatizados
- Protótipos e demonstrações
- Validar fluxos antes de integrar APIs reais

---

## 💳 Exemplos Práticos

### PIX
```php
$pix = $hub->createPixPayment(
    PixPaymentRequest::create(
        amount: 150.00,
        customerName: 'João Silva',
        customerEmail: 'joao@email.com',
        description: 'Pedido #123',
        expiresInMinutes: 30  // Opcional
    )
);

// Pega QR Code e Copia e Cola
$qrCode = $hub->getPixQrCode($pix->transactionId);
$copiaECola = $hub->getPixCopyPaste($pix->transactionId);

echo "Pague com PIX: {$copiaECola}";
```

---

### 💳 Cartão de Crédito
```php
// Pagamento à vista
$payment = $hub->createCreditCardPayment(
    CreditCardPaymentRequest::create(
        amount: 299.90,
        cardNumber: '4111 1111 1111 1111',
        cardHolderName: 'MARIA SILVA',
        cardExpiryMonth: '12',
        cardExpiryYear: '2028',
        cardCvv: '123'
    )
);

// Parcelado em 3x
$payment = $hub->createCreditCardPayment(
    CreditCardPaymentRequest::create(
        amount: 899.90,
        installments: 3,
        // ... dados do cartão
    )
);

// Pré-autorização (captura depois)
$preAuth = $hub->createCreditCardPayment(
    CreditCardPaymentRequest::create(
        amount: 500.00,
        capture: false,  // Não captura automaticamente
        // ... dados do cartão
    )
);

// Captura depois
$captured = $hub->capturePreAuthorization($preAuth->transactionId);

// Ou captura parcial
$partial = $hub->capturePreAuthorization($preAuth->transactionId, 300.00);

// Ou cancela
$cancelled = $hub->cancelPreAuthorization($preAuth->transactionId);
```

---

### 💳 Cartão de Débito
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\DebitCardPaymentRequest;

$payment = $hub->createDebitCardPayment(
    DebitCardPaymentRequest::create(
        amount: 89.90,
        cardNumber: '5555 5555 5555 4444',
        cardHolderName: 'MARIA SILVA',
        cardExpiryMonth: '08',
        cardExpiryYear: '2027',
        cardCvv: '321',
        customerEmail: 'maria@email.com'
    )
);
```

---

### 📄 Boleto
```php
$boleto = $hub->createBoleto(
    BoletoPaymentRequest::create(
        amount: 450.00,
        customerName: 'João Silva',
        customerDocument: '123.456.789-00',
        customerEmail: 'joao@email.com',
        dueDate: '2025-03-15',
        description: 'Mensalidade',
        finePercentage: 2.0,
        interestPercentage: 1.0,
        discountAmount: 10.00,
        discountLimitDate: '2025-03-10'
    )
);

$urlPdf = $hub->getBoletoUrl($boleto->transactionId);
$hub->cancelBoleto($boleto->transactionId);  // Cancelar
```

---

### 🔁 Assinaturas
```php
$subscription = $hub->createSubscription(
    SubscriptionRequest::create(
        amount: 49.90,
        interval: 'monthly',
        customerId: 'cust_123',
        cardToken: 'tok_456',
        description: 'Plano Premium',
        trialDays: 7,
        cycles: 12  // null = ilimitado
    )
);

// Gerenciar assinatura
$hub->cancelSubscription($subscription->subscriptionId);
$hub->suspendSubscription($subscription->subscriptionId);
$hub->reactivateSubscription($subscription->subscriptionId);
$hub->updateSubscription($subscription->subscriptionId, ['amount' => 59.90]);
```

---

### 💸 Split de Pagamento
```php
$payment = $hub->createSplitPayment(
    SplitPaymentRequest::create(
        amount: 1000.00,
        splits: [
            ['recipient_id' => 'seller_1', 'amount' => 700.00],
            ['recipient_id' => 'marketplace', 'amount' => 300.00]
        ],
        paymentMethod: 'credit_card'
    )
);
```

---

### 🔒 Escrow (Custódia)
```php
$escrow = $hub->holdInEscrow(
    EscrowRequest::create(
        amount: 500.00,
        recipientId: 'seller_123',
        holdDays: 7,
        description: 'Aguardando entrega'
    )
);

// Liberações
$hub->releaseEscrow($escrow->escrowId);           // Total
$hub->partialReleaseEscrow($escrow->escrowId, 200.00);  // Parcial
$hub->cancelEscrow($escrow->escrowId);            // Cancelar
```

---

### 👛 Wallets
```php
$wallet = $hub->createWallet(
    WalletRequest::create(
        customerId: 'user_123',
        currency: 'BRL',
        initialBalance: 100.00
    )
);

$hub->addBalance($wallet->walletId, 50.00);
$hub->deductBalance($wallet->walletId, 30.00);
$balance = $hub->getWalletBalance($wallet->walletId);

// Transferir entre wallets
$transfer = $hub->transferBetweenWallets(
    fromWalletId: 'wallet_abc',
    toWalletId: 'wallet_xyz',
    amount: 75.00
);
```

---

### 🏢 Sub-contas
```php
$subAccount = $hub->createSubAccount(
    SubAccountRequest::create(
        name: 'Loja do João',
        documentNumber: '12.345.678/0001-90',
        email: 'joao@loja.com',
        phone: '11999999999',
        address: [
            'street' => 'Rua A',
            'number' => '100',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01234567'
        ]
    )
);

$hub->activateSubAccount($subAccount->subAccountId);
$hub->deactivateSubAccount($subAccount->subAccountId);
$info = $hub->getSubAccount($subAccount->subAccountId);
```

---

### 💸 Transferências
```php
// Transferência PIX
$transfer = $hub->transfer(
    TransferRequest::create(
        amount: 200.00,
        pixKey: 'carlos@email.com',
        recipientName: 'Carlos Mendes',
        description: 'Pagamento fornecedor'
    )
);

// Transferência TED
$ted = $hub->transfer(
    TransferRequest::create(
        amount: 1500.00,
        bankCode: '237',
        agency: '0001',
        account: '123456-7',
        accountType: 'checking',
        recipientName: 'Empresa XYZ',
        documentNumber: '12.345.678/0001-99',
        description: 'Pagamento NF 001'
    )
);

// Agendar transferência
$scheduled = $hub->scheduleTransfer($request, '2025-03-31');

// Cancelar agendamento
$hub->cancelScheduledTransfer($scheduled->transferId);
```

---

### 🔗 Links de Pagamento
```php
$link = $hub->createPaymentLink(
    PaymentLinkRequest::create(
        amount: 199.90,
        description: 'Curso Online',
        expiresAt: '2025-12-31',
        redirectUrl: 'https://seusite.com/sucesso',
        acceptedPaymentMethods: ['pix', 'credit_card', 'boleto']
    )
);

echo "Link: {$link->url}";
$hub->expirePaymentLink($link->linkId);
```

---

### 💰 Reembolsos
```php
// Total
$refund = $hub->refund(
    RefundRequest::create(
        transactionId: 'txn_123',
        reason: 'Cliente solicitou'
    )
);

// Parcial
$partial = $hub->partialRefund('txn_456', 50.00);

// Chargebacks
$chargebacks = $hub->getChargebacks();
$hub->disputeChargeback('chb_123', ['evidence' => ['nota.pdf']]);
```

---

### 🛡️ Antifraude
```php
// Análise
$analysis = $hub->analyzeTransaction('txn_123');

// Blacklist
$hub->addToBlacklist('12345678900', 'cpf');
$hub->removeFromBlacklist('12345678900', 'cpf');
```

---

### 🔔 Webhooks
```php
// Registrar webhook
$wh = $hub->registerWebhook(
    'https://seusite.com/webhook',
    ['payment.approved', 'payment.refunded']
);

$hub->listWebhooks();
$hub->deleteWebhook($wh['webhook_id']);
```

### 🏦 Webhook Handlers Dedicados

Para gateways bancários, use os handlers específicos:

```php
// Banco do Brasil - validação por token fixo
$handler = new BancoDoBrasilWebhookHandler(
    webhookToken: $_ENV['BB_WEBHOOK_TOKEN'],
    validateToken: true
);

$handler->onPixRecebido(function ($event) {
    // $event['txid'], $event['valor'], $event['pagador']
    Orders::confirm($event['txid'], $event['valor']);
});

$handler->onBoletoLiquidado(function ($event) {
    Orders::markPaid($event['nossoNumero']);
});

$result = $handler->handle();
$handler->respondOk($result);

// BofA CashPro - HMAC-SHA256 + IP whitelist
$handler = new BofACashProWebhookHandler(
    webhookSecret: $_ENV['BOFA_WEBHOOK_SECRET'],
    validateIp: true,
    allowedIps: ['198.51.100.10', '198.51.100.11']
);

$handler->onPaymentReceived(function ($event) {
    // $event['paymentType']: ZELLE | ACH_SAME_DAY | ACH_STANDARD | WIRE
    // $event['amount'], $event['senderEmail'], $event['memo']
    Ledger::credit($event['accountId'], $event['amount']);
});

$handler->handle();
$handler->respondOk();
```

---

## 🔄 Mudando para Gateway Real

**Troque apenas 1 linha:**

```php
// Desenvolvimento (offline)
$hub = new PaymentHub(new FakeBankGateway());

// Produção (Asaas)
$hub = new PaymentHub(new AsaasGateway(
    apiKey: $_ENV['ASAAS_KEY'],
    sandbox: false
));

// Produção (Banco do Brasil)
$hub = new PaymentHub(new BancoDoBrasilGateway(
    clientId: $_ENV['BB_CLIENT_ID'],
    clientSecret: $_ENV['BB_CLIENT_SECRET'],
    developerAppKey: $_ENV['BB_APP_KEY'],
    pixKey: $_ENV['BB_PIX_KEY'],
    convenio: (int) $_ENV['BB_CONVENIO'],
    sandbox: false,
    certPath: '/etc/ssl/bb/cert.pem',  // obrigatório em produção
));

// Todo o resto do código continua igual! 🎉
```

---

## 🎯 Funcionalidades Completas

| Categoria | Funcionalidades |
|-----------|-----------------|
| **💳 Pagamentos** | PIX (QR Code), Cartão Crédito (à vista/parcelado), Cartão Débito, Boleto, Link de Pagamento |
| **🔁 Recorrência** | Criar, Cancelar, Suspender, Reativar, Atualizar Assinaturas |
| **💸 Financeiro** | Reembolsos (total/parcial), Split, Transferências PIX/TED, Agendamento, Antecipação de Recebíveis |
| **🔒 Gestão** | Escrow (Custódia), Liberação parcial/total, Cancelamento |
| **🏢 Multi-tenant** | Sub-contas, Ativar/Desativar, Gestão de Permissões |
| **👛 Wallets** | Criar, Saldo, Adicionar/Deduzir, Transferir entre Wallets |
| **👤 Clientes** | Cadastrar, Atualizar, Listar, Buscar |
| **🛡️ Segurança** | Antifraude, Blacklist, Webhooks, Tokenização de Cartões |
| **🏦 Bancos** | BB: PIX, Boleto, Transferências, Saldo, Extrato; Itaú: PIX, Boleto, Transferências, Saldo, Extrato; BofA: Zelle, ACH, Wire |

---

## 🎨 ValueObjects - Validação Automática

```php
// CPF validado automaticamente
$request = PixPaymentRequest::create(
    amount: 100.00,
    customerDocument: '123.456.789-00'  // ✅ Válido
);

// ❌ Lança InvalidDocumentException
$request = PixPaymentRequest::create(
    amount: 100.00,
    customerDocument: '000.000.000-00'
);

// Cartão valida Luhn automaticamente
$request = CreditCardPaymentRequest::create(
    amount: 100.00,
    cardNumber: '4111 1111 1111 1111'  // ✅ Válido
);

// Money previne valores negativos
$money = Money::from(-50.00);  // ❌ InvalidAmountException
```

---

## 📚 Documentação Completa

- 📖 [Conceitos Principais](docs/core-concepts.md)
- 💳 [Pagamentos com Cartão](docs/credit-card.md)
- 💰 [PIX](docs/pix.md)
- 📄 [Boleto](docs/boleto.md)
- 🔁 [Assinaturas](docs/subscriptions.md)
- 💸 [Split de Pagamento](docs/split-payments.md)
- 🏦 [Banco do Brasil](src/Gateways/Bancodobrasil/BancoDoBrasilGateway.md)
- 🏦 [Itaú Unibanco](src/Gateways/Itau/ItauGateway.md)
- 🏦 [BofA CashPro](src/Gateways/BofACashPro/readme.md)
- 🟣 [NuBank (NuPay)](src/Gateways/NuBank/readme.md)
- 🎣 [Webhooks](docs/webhooks.md)
- 🏗️ [Criar Seu Próprio Gateway](docs/creating-gateway.md)
- ❓ [FAQ](docs/faq.md)

---

## 🧪 Testando

```bash
# Rodar todos os testes
composer test

# Com cobertura
composer test:coverage

# PHPStan (análise estática)
composer analyse
```

---

## 🤝 Contribuindo

1. Fork o projeto
2. Crie sua feature branch (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Add: MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

Veja [CONTRIBUTING.md](docs/contributing.md) para mais detalhes.

---

## 📄 Licença

MIT License. Veja [LICENSE](LICENSE) para mais detalhes.

---

## 💬 Suporte

- 📧 Email: contato@israelnogueira.com
- 🐛 Issues: [GitHub Issues](https://github.com/israel-nogueira/payment-hub/issues)
- 💬 Discussões: [GitHub Discussions](https://github.com/israel-nogueira/payment-hub/discussions)

---

<div align="center">

**Feito com ❤️ para a comunidade PHP brasileira** 🇧🇷

⭐ Se este projeto te ajudou, deixe uma estrela no GitHub!

</div>