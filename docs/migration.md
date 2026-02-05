# 🔄 Guia de Migração entre Gateways

Migre entre gateways de pagamento **sem alterar seu código**.

---

## 🎯 Filosofia

O Payment Hub foi projetado para permitir que você:

1. **Comece com FakeBankGateway** (desenvolvimento)
2. **Teste com gateway sandbox**
3. **Vá para produção** mudando apenas a instância do gateway

---

## 🚀 Exemplo Completo

### 1. Desenvolvimento (FakeBank)

```php
use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;

$hub = new PaymentHub(new FakeBankGateway());

$payment = $hub->createPixPayment(
    PixPaymentRequest::create(
        amount: 100.00,
        customerEmail: 'teste@email.com'
    )
);
```

### 2. Teste (Asaas Sandbox)

```php
use IsraelNogueira\PaymentHub\Gateways\AsaasGateway;

// APENAS ESTA LINHA MUDA!
$hub = new PaymentHub(new AsaasGateway(
    apiKey: $_ENV['ASAAS_API_KEY'],
    sandbox: true  // Modo teste
));

// Resto do código permanece IDÊNTICO
$payment = $hub->createPixPayment(
    PixPaymentRequest::create(
        amount: 100.00,
        customerEmail: 'teste@email.com'
    )
);
```

### 3. Produção

```php
// Apenas muda o gateway e sandbox = false
$hub = new PaymentHub(new AsaasGateway(
    apiKey: $_ENV['ASAAS_API_KEY'],
    sandbox: false  // Produção
));

// Código permanece IDÊNTICO
$payment = $hub->createPixPayment(
    PixPaymentRequest::create(
        amount: 100.00,
        customerEmail: 'teste@email.com'
    )
);
```

---

## 🔀 Trocar de Gateway

### Asaas → Pagar.me

```php
// De:
$hub = new PaymentHub(new AsaasGateway(
    apiKey: $_ENV['ASAAS_API_KEY'],
    sandbox: false
));

// Para:
$hub = new PaymentHub(new PagarMeGateway(
    secretKey: $_ENV['PAGARME_SECRET_KEY'],
    publicKey: $_ENV['PAGARME_PUBLIC_KEY'],
    sandbox: false
));

// TODO O RESTO DO CÓDIGO PERMANECE IGUAL! 🎉
```

---

## ⚙️ Usando Factory Pattern

Para facilitar ainda mais:

```php
use IsraelNogueira\PaymentHub\Factories\PaymentHubFactory;

// Em desenvolvimento
$hub = PaymentHubFactory::create('fakebank');

// Em staging
$hub = PaymentHubFactory::create('asaas', [
    'api_key' => $_ENV['ASAAS_API_KEY'],
    'sandbox' => true
]);

// Em produção
$hub = PaymentHubFactory::create('asaas', [
    'api_key' => $_ENV['ASAAS_API_KEY'],
    'sandbox' => false
]);
```

---

## 🗺️ Matriz de Compatibilidade

| Funcionalidade | FakeBank | Asaas | Pagar.me | EBANX | Mercado Pago | PagSeguro | Adyen | Stripe | PayPal | Ether |
|----------------|----------|-------|----------|-------|--------------|-----------|-------|--------|--------|-------|
| PIX | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| Cartão Crédito | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Boleto | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Assinaturas | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ |
| Split | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Wallets | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 📋 Checklist de Migração

### Antes de Migrar

- [ ] Testar no sandbox do novo gateway
- [ ] Verificar matriz de compatibilidade
- [ ] Atualizar variáveis de ambiente
- [ ] Configurar webhooks no novo gateway
- [ ] Revisar taxas e prazos

### Durante a Migração

- [ ] Manter gateway antigo ativo
- [ ] Redirecionar novos pagamentos
- [ ] Monitorar erros
- [ ] Testar todos os fluxos

### Após a Migração

- [ ] Verificar webhooks
- [ ] Validar relatórios
- [ ] Confirmar recebimentos
- [ ] Desativar gateway antigo (após período de segurança)

---

## 💡 Dicas

1. **Use variáveis de ambiente**
   ```php
   $gateway = $_ENV['PAYMENT_GATEWAY'] ?? 'fakebank';
   $hub = PaymentHubFactory::create($gateway);
   ```

2. **Mantenha fallback**
   ```php
   try {
       $payment = $hub->createPixPayment($request);
   } catch (GatewayException $e) {
       // Use gateway alternativo
       $fallbackHub = new PaymentHub(new BackupGateway());
       $payment = $fallbackHub->createPixPayment($request);
   }
   ```

3. **Teste TUDO antes de produção**

---

Migração fácil e segura! 🚀
