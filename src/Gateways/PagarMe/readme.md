# 💳 Pagar.me Gateway

Gateway de integração com a Pagar.me (Stone Pagamentos), uma das principais plataformas de pagamento do Brasil.

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Métodos Suportados](#-métodos-suportados)
- [Exemplos de Uso](#-exemplos-de-uso)
- [Webhooks](#-webhooks)
- [Limitações Conhecidas](#-limitações-conhecidas)

---

## ✨ Características

- ✅ **PIX** - Pagamentos instantâneos com QR Code
- ✅ **Cartão de Crédito** - À vista e parcelado
- ✅ **Cartão de Débito** - Com autenticação 3DS
- ✅ **Boleto Bancário** - Geração e consulta
- ✅ **Assinaturas** - Pagamentos recorrentes
- ✅ **Split de Pagamento** - Divisão entre múltiplos recebedores
- ✅ **Recipients** - Sub-contas para marketplaces
- ✅ **Refunds** - Estornos totais e parciais
- ✅ **Pre-authorization** - Captura manual de pagamentos
- ✅ **Antifraude** - Análise automática de transações
- ✅ **Webhooks** - Notificações em tempo real
- ✅ **Gestão de Clientes** - CRUD completo

---

## 📦 Instalação

```bash
composer require israel-nogueira/payment-hub
```

---

## 🔧 Configuração

### Obter Credenciais

1. Acesse [Dashboard Pagar.me](https://dashboard.pagar.me)
2. Vá em **Configurações** → **Chaves de API**
3. Copie sua **Secret Key** (sk_test_... ou sk_live_...)
4. Copie sua **Public Key** (opcional, para tokenização client-side)

### Inicialização

```php
use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\PagarMe\PagarMeGateway;

// Modo Sandbox (Teste)
$gateway = new PagarMeGateway(
    secretKey: 'sk_test_xxxxxxxxxxxxxx',
    publicKey: 'pk_test_xxxxxxxxxxxxxx', // Opcional
    sandbox: true
);

// Modo Produção
$gateway = new PagarMeGateway(
    secretKey: 'sk_live_xxxxxxxxxxxxxx',
    publicKey: 'pk_live_xxxxxxxxxxxxxx',
    sandbox: false
);

$hub = new PaymentHub($gateway);
```

---

## 🎯 Métodos Suportados

### Pagamentos

| Método | Status | Observações |
|--------|--------|-------------|
| `createPixPayment` | ✅ | QR Code gerado automaticamente |
| `createCreditCardPayment` | ✅ | Suporta parcelamento e captura manual |
| `createDebitCardPayment` | ✅ | Requer autenticação 3DS |
| `createBoleto` | ✅ | PDF e código de barras |
| `tokenizeCard` | ✅ | Para pagamentos futuros |
| `capturePreAuthorization` | ✅ | Captura total ou parcial |
| `cancelPreAuthorization` | ✅ | Cancela autorização |

### Assinaturas

| Método | Status | Observações |
|--------|--------|-------------|
| `createSubscription` | ✅ | Planos inline ou pré-criados |
| `cancelSubscription` | ✅ | Cancelamento imediato |
| `updateSubscription` | ✅ | Atualizar dados da assinatura |
| `suspendSubscription` | ❌ | Use cancelamento |
| `reactivateSubscription` | ❌ | Crie nova assinatura |

### Split & Recipients

| Método | Status | Observações |
|--------|--------|-------------|
| `createSplitPayment` | ✅ | Até 99 recebedores |
| `createSubAccount` | ✅ | Recipients para split |
| `updateSubAccount` | ✅ | Atualizar dados bancários |
| `getSubAccount` | ✅ | Consultar recipient |

### Gestão

| Método | Status | Observações |
|--------|--------|-------------|
| `createCustomer` | ✅ | Cadastro de clientes |
| `updateCustomer` | ✅ | Atualização de dados |
| `getCustomer` | ✅ | Consulta individual |
| `listCustomers` | ✅ | Listagem com filtros |
| `refund` | ✅ | Estorno total |
| `partialRefund` | ✅ | Estorno parcial |
| `getTransactionStatus` | ✅ | Status de pedidos |
| `getBalance` | ✅ | Saldo disponível |

---

## 💡 Exemplos de Uso

### PIX - Básico

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;

$payment = $hub->createPixPayment(
    PixPaymentRequest::create(
        amount: 100.00,
        customerName: 'João Silva',
        customerEmail: 'joao@email.com',
        customerDocument: '123.456.789-00',
        expiresInMinutes: 30
    )
);

echo "Transaction ID: {$payment->transactionId}\n";
echo "QR Code: {$payment->metadata['qr_code']}\n";
echo "QR Code URL: {$payment->metadata['qr_code_url']}\n";
```

### Cartão de Crédito - Parcelado

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;

$payment = $hub->createCreditCardPayment(
    CreditCardPaymentRequest::create(
        amount: 599.90,
        cardNumber: '4111 1111 1111 1111',
        cardHolderName: 'MARIA SANTOS',
        cardExpiryMonth: '12',
        cardExpiryYear: '2028',
        cardCvv: '123',
        installments: 3, // 3x
        customerEmail: 'maria@email.com',
        customerDocument: '987.654.321-00'
    )
);

echo "Status: {$payment->getStatusLabel()}\n";
echo "Parcelas: 3x de R$ " . number_format(599.90/3, 2, ',', '.') . "\n";
```

### Cartão de Crédito - Com Captura Manual (Pre-auth)

```php
$payment = $hub->createCreditCardPayment(
    CreditCardPaymentRequest::create(
        amount: 299.90,
        cardNumber: '4111 1111 1111 1111',
        cardHolderName: 'JOSE LIMA',
        cardExpiryMonth: '08',
        cardExpiryYear: '2027',
        cardCvv: '321',
        capture: false // Apenas autorizar, não capturar
    )
);

// Depois, quando quiser capturar
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;

$captured = $hub->capturePreAuthorization(
    $payment->transactionId,
    amount: Money::from(299.90, Currency::BRL) // Opcional: captura parcial
);
```

### Boleto

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;

$boleto = $hub->createBoleto(
    BoletoPaymentRequest::create(
        amount: 450.00,
        customerName: 'Pedro Oliveira',
        customerDocument: '111.222.333-44',
        customerEmail: 'pedro@email.com',
        dueDate: '2025-03-15',
        description: 'Mensalidade Março/2025'
    )
);

echo "Código de Barras: {$boleto->metadata['barcode']}\n";
echo "PDF: {$boleto->metadata['pdf_url']}\n";
echo "Linha Digitável: {$boleto->metadata['line']}\n";
```

### Assinatura Recorrente

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubscriptionRequest;

$subscription = $hub->createSubscription(
    SubscriptionRequest::create(
        amount: 49.90,
        interval: 'monthly',
        customerName: 'Ana Costa',
        customerEmail: 'ana@email.com',
        cardToken: 'card_xxxxxxxxxxxxx', // Token do cartão
        description: 'Plano Premium'
    )
);

echo "Subscription ID: {$subscription->subscriptionId}\n";
echo "Status: {$subscription->status}\n";
```

### Split de Pagamento (Marketplace)

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SplitPaymentRequest;

$payment = $hub->createSplitPayment(
    SplitPaymentRequest::create(
        amount: 1000.00,
        splits: [
            [
                'recipient_id' => 'rp_xxxxxxxxxxxxx', // Vendedor
                'amount' => 850.00,
                'charge_processing_fee' => true,
                'liable' => true,
            ],
            [
                'recipient_id' => 'rp_yyyyyyyyyyyyy', // Marketplace
                'amount' => 150.00,
                'charge_processing_fee' => false,
                'liable' => false,
            ],
        ],
        paymentMethod: 'credit_card'
    )
);
```

### Criar Recipient (Sub-conta)

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubAccountRequest;

$recipient = $hub->createSubAccount(
    SubAccountRequest::create(
        name: 'Loja do João',
        email: 'joao@loja.com',
        document: '12.345.678/0001-90',
        bankAccount: [
            'bank_code' => '341', // Itaú
            'branch' => '0001',
            'branch_digit' => '9',
            'account' => '12345',
            'account_digit' => '6',
            'type' => 'checking',
        ]
    )
);

echo "Recipient ID: {$recipient->subAccountId}\n";
echo "Status: {$recipient->status}\n";
```

### Tokenizar Cartão

```php
$token = $hub->tokenizeCard([
    'number' => '4111111111111111',
    'holder_name' => 'TESTE SILVA',
    'expiry_month' => '12',
    'expiry_year' => '2028',
    'cvv' => '123',
]);

echo "Card Token: {$token}\n";
```

### Estorno Total

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\RefundRequest;

$refund = $hub->refund(
    RefundRequest::create(
        transactionId: 'or_xxxxxxxxxxxxx',
        reason: 'Produto com defeito'
    )
);

echo "Refund ID: {$refund->refundId}\n";
echo "Valor: {$refund->money->formatted()}\n";
```

### Estorno Parcial

```php
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;

$refund = $hub->partialRefund(
    transactionId: 'or_xxxxxxxxxxxxx',
    amount: Money::from(50.00, Currency::BRL) // Estornar apenas R$ 50,00
);
```

### Consultar Status

```php
$status = $hub->getTransactionStatus('or_xxxxxxxxxxxxx');

echo "Status: {$status->status->label()}\n";
echo "Valor: {$status->money->formatted()}\n";
```

### Consultar Saldo

```php
$balance = $hub->getBalance();

echo "Disponível: R$ {$balance->availableBalance}\n";
echo "A receber: R$ {$balance->pendingBalance}\n";
```

---

## 🎣 Webhooks

### Configurar Webhook

```php
$webhook = $hub->registerWebhook(
    url: 'https://meusite.com/webhook/pagarme',
    events: [
        'order.paid',
        'order.payment_failed',
        'order.refunded',
        'subscription.created',
        'subscription.canceled',
    ]
);
```

### Processar Webhook

```php
use IsraelNogueira\PaymentHub\Webhooks\WebhookHandler;

$handler = new WebhookHandler($hub);

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

$event = $handler->process($payload, $signature);

switch ($event->type) {
    case 'order.paid':
        // Pagamento confirmado
        $orderId = $event->data['id'];
        break;
        
    case 'order.payment_failed':
        // Pagamento falhou
        break;
        
    case 'order.refunded':
        // Estorno realizado
        break;
}
```

### Eventos Disponíveis

- `order.created` - Pedido criado
- `order.paid` - Pedido pago
- `order.payment_failed` - Pagamento falhou
- `order.refunded` - Estorno realizado
- `order.canceled` - Pedido cancelado
- `subscription.created` - Assinatura criada
- `subscription.canceled` - Assinatura cancelada
- `subscription.payment_success` - Cobrança bem-sucedida
- `subscription.payment_failed` - Cobrança falhou

---

## ⚠️ Limitações Conhecidas

### Não Suportado via API

- ❌ **Payment Links** - Disponível apenas via Dashboard
- ❌ **Wallets** - Use Recipients para split
- ❌ **Escrow Dedicado** - Use pre-authorization
- ❌ **Suspensão de Assinaturas** - Use cancelamento
- ❌ **Transferências Agendadas** - Configure regras automáticas
- ❌ **Antecipação de Recebíveis** - Via Dashboard

### Observações Importantes

1. **Cartão de Débito**: Requer integração com URL de autenticação 3DS
2. **Split**: Máximo de 99 recebedores por transação
3. **Refund**: Disponível até 180 dias após a transação
4. **Boleto**: Cancelamento automático após vencimento
5. **Assinaturas**: Necessário ter cartão tokenizado

---

## 📊 Status de Transações

| Status Pagar.me | Status Payment Hub | Descrição |
|-----------------|-------------------|-----------|
| `paid` | `PAID` | Pagamento confirmado |
| `waiting_payment` | `PENDING` | Aguardando pagamento |
| `pending` | `PENDING` | Processando |
| `processing` | `PROCESSING` | Em processamento |
| `authorized` | `APPROVED` | Autorizado (pré-captura) |
| `refused` | `FAILED` | Recusado |
| `refunded` | `REFUNDED` | Estornado |
| `canceled` | `CANCELLED` | Cancelado |

---

## 🔗 Links Úteis

- [Documentação Oficial Pagar.me](https://docs.pagar.me)
- [Dashboard Pagar.me](https://dashboard.pagar.me)
- [API Reference](https://docs.pagar.me/reference)
- [Status da API](https://status.pagar.me)
- [Suporte](https://suporte.pagar.me)

---

## 💬 Suporte

Para questões específicas do Payment Hub:
- 📧 Email: contato@israelnogueira.com
- 🐛 Issues: [GitHub Issues](https://github.com/israel-nogueira/payment-hub/issues)

Para questões da Pagar.me:
- 📞 Telefone: 0800 591 0017
- 💬 Chat: Disponível no Dashboard
- 📧 Email: suporte@pagar.me

---

**Última atualização**: Agosto 2026