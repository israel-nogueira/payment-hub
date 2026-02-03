# PaymentHub 💳

![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-active-success)

**PaymentHub** é um adaptador unificado para integração com múltiplos gateways de pagamento brasileiros e internacionais. Com uma interface única e padronizada, você pode alternar entre diferentes provedores de pagamento sem reescrever seu código.

---

## ✨ Características

- 🔌 **Plug & Play**: Interface única para múltiplos gateways
- 🎯 **Type-Safe**: PHP 8.3+ com Type Hints completos
- 📦 **DTOs**: Requisições e respostas tipadas e padronizadas
- 🧪 **Testável**: Gateway fake incluso para testes locais
- 🚀 **Extensível**: Fácil adicionar novos gateways
- 🇧🇷 **Brasil First**: Suporte completo a PIX, Boleto e métodos brasileiros
- 🌍 **Internacional**: Suporte a cartões internacionais e múltiplas moedas

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
- 🔄 **Transferências** (PIX, TED, agendadas)
- 👤 **Gestão de Clientes**
- 🛡️ **Antifraude**
- 🔔 **Webhooks**
- 💵 **Consulta de Saldo**

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

### 1️⃣ PIX
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;

$request = new PixPaymentRequest(
    amount: 100.00,
    currency: 'BRL',
    description: 'Pagamento do pedido #123',
    customerName: 'João Silva',
    customerDocument: '12345678900',
    customerEmail: 'joao@email.com',
    expiresInMinutes: 30
);

$response = $hub->createPixPayment($request);

if ($response->isSuccess()) {
    echo "Transaction ID: " . $response->transactionId . "\n";
    echo "QR Code: " . $hub->getPixQrCode($response->transactionId) . "\n";
    echo "Copia e Cola: " . $hub->getPixCopyPaste($response->transactionId) . "\n";
}
```

---

### 2️⃣ Cartão de Crédito
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;

$request = new CreditCardPaymentRequest(
    amount: 250.00,
    currency: 'BRL',
    cardNumber: '4111111111111111',
    cardHolderName: 'JOAO SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    installments: 3,
    capture: true,
    description: 'Compra parcelada',
    customerName: 'João Silva',
    customerEmail: 'joao@email.com'
);

$response = $hub->createCreditCardPayment($request);

if ($response->isSuccess()) {
    echo "Pagamento aprovado! ID: " . $response->transactionId;
}
```

---

### 3️⃣ Boleto
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;

$request = new BoletoPaymentRequest(
    amount: 500.00,
    currency: 'BRL',
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

### 4️⃣ Assinatura/Recorrência
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubscriptionRequest;

$request = new SubscriptionRequest(
    amount: 99.90,
    currency: 'BRL',
    interval: 'monthly', // monthly, yearly, weekly
    customerId: 'cust_123',
    cardToken: 'tok_abc123',
    description: 'Plano Premium',
    trialDays: 7,
    cycles: 12 // null = ilimitado
);

$response = $hub->createSubscription($request);

if ($response->isSuccess()) {
    echo "Assinatura criada! ID: " . $response->subscriptionId;
}
```

---

### 5️⃣ Split de Pagamento (Marketplace)
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SplitPaymentRequest;

$request = new SplitPaymentRequest(
    amount: 1000.00,
    currency: 'BRL',
    splits: [
        [
            'recipient_id' => 'seller_1',
            'amount' => 700.00,
            'percentage' => null
        ],
        [
            'recipient_id' => 'marketplace',
            'amount' => 300.00,
            'percentage' => null
        ]
    ],
    paymentMethod: 'credit_card',
    description: 'Venda no marketplace'
);

$response = $hub->createSplitPayment($request);
```

---

### 6️⃣ Wallet (Carteira Digital)
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\WalletRequest;

// Criar wallet
$request = new WalletRequest(
    customerId: 'cust_123',
    currency: 'BRL',
    description: 'Carteira do João',
    initialBalance: 100.00
);

$walletResponse = $hub->createWallet($request);
$walletId = $walletResponse->walletId;

// Adicionar saldo
$hub->addBalance($walletId, 50.00);

// Consultar saldo
$balance = $hub->getWalletBalance($walletId);
echo "Saldo: R$ " . $balance->balance;

// Transferir entre wallets
$hub->transferBetweenWallets('wallet_1', 'wallet_2', 25.00);
```

---

### 7️⃣ Escrow (Custódia)
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\EscrowRequest;

// Segurar em custódia
$request = new EscrowRequest(
    amount: 1000.00,
    currency: 'BRL',
    transactionId: 'txn_123',
    recipientId: 'seller_1',
    holdDays: 7,
    description: 'Aguardando confirmação de entrega'
);

$escrow = $hub->holdInEscrow($request);

// Liberar após confirmação
$hub->releaseEscrow($escrow->escrowId);

// Ou cancelar e devolver ao comprador
// $hub->cancelEscrow($escrow->escrowId);
```

---

### 8️⃣ Link de Pagamento
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\PaymentLinkRequest;

$request = new PaymentLinkRequest(
    amount: 150.00,
    currency: 'BRL',
    description: 'Pagamento do Curso',
    acceptedPaymentMethods: ['pix', 'credit_card', 'boleto'],
    maxUses: 1,
    expiresAt: '2026-12-31 23:59:59',
    reusable: false,
    redirectUrl: 'https://meusite.com/obrigado'
);

$response = $hub->createPaymentLink($request);

if ($response->isSuccess()) {
    echo "Link de pagamento: " . $response->url;
}
```

---

### 9️⃣ Estorno
```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\RefundRequest;

// Estorno total
$request = new RefundRequest(
    transactionId: 'txn_123',
    reason: 'Cliente solicitou cancelamento'
);

$refund = $hub->refund($request);

// Estorno parcial
$partialRefund = $hub->partialRefund('txn_123', 50.00);

if ($refund->isCompleted()) {
    echo "Estorno processado!";
}
```

---

### 🔟 Consultas e Status
```php
// Status da transação
$status = $hub->getTransactionStatus('txn_123');

if ($status->isPaid()) {
    echo "Pagamento confirmado!";
} elseif ($status->isPending()) {
    echo "Aguardando pagamento...";
} elseif ($status->isFailed()) {
    echo "Pagamento recusado!";
}

// Listar transações
$transactions = $hub->listTransactions([
    'start_date' => '2026-01-01',
    'end_date' => '2026-01-31',
    'status' => 'approved'
]);

// Consultar saldo
$balance = $hub->getBalance();
echo "Disponível: R$ " . $balance->availableBalance;
echo "A receber: R$ " . $balance->pendingBalance;
```

---

## 🔌 Criando seu Próprio Adapter
```php
<?php

namespace MeuProjeto\Gateways;

use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Responses\PaymentResponse;

class MeuGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $apiKey,
        private bool $sandbox = false
    ) {}
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        // Sua implementação aqui
        $response = $this->apiCall('/pix/create', $request->toArray());
        
        return new PaymentResponse(
            success: $response['status'] === 'success',
            transactionId: $response['id'],
            status: $response['status'],
            amount: $request->amount,
            currency: $request->currency,
            message: $response['message'] ?? null,
            rawResponse: $response
        );
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
# Em breve
composer test
```

---

## 📚 Documentação Completa

Em desenvolvimento! Por enquanto, explore os exemplos acima e os PHPDocs no código.

---

## 🤝 Contribuindo

Contribuições são muito bem-vindas! 

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/NovoGateway`)
3. Commit suas mudanças (`git commit -m 'Adiciona gateway X'`)
4. Push para a branch (`git push origin feature/NovoGateway`)
5. Abra um Pull Request

---

## 📝 Licença

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
- [PHP 8.3 Release Notes](https://www.php.net/releases/8.3/en.php)

---

**Feito com ❤️ para a comunidade PHP brasileira**