# 🔄 Assinaturas e Recorrência

Cobre seus clientes automaticamente todo mês.

---

## 🚀 Assinatura Básica

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubscriptionRequest;
use IsraelNogueira\PaymentHub\Enums\{Currency, SubscriptionInterval};

$request = new SubscriptionRequest(
    amount: 99.90,
    currency: Currency::BRL->value,
    interval: SubscriptionInterval::MONTHLY->value,
    customerId: 'cust_123',
    cardToken: 'tok_abc123',
    description: 'Plano Premium'
);

$response = $hub->createSubscription($request);

if ($response->isSuccess()) {
    echo "Assinatura criada!\n";
    echo "ID: {$response->subscriptionId}\n";
}
```

---

## 📅 Intervalos

```php
use IsraelNogueira\PaymentHub\Enums\SubscriptionInterval;

// Diário
SubscriptionInterval::DAILY;

// Semanal
SubscriptionInterval::WEEKLY;

// Quinzenal
SubscriptionInterval::BIWEEKLY;

// Mensal (mais comum)
SubscriptionInterval::MONTHLY;

// Bimestral
SubscriptionInterval::BIMONTHLY;

// Trimestral
SubscriptionInterval::QUARTERLY;

// Semestral
SubscriptionInterval::SEMIANNUALLY;

// Anual
SubscriptionInterval::YEARLY;
```

---

## 🎁 Trial (Período Grátis)

```php
$request = new SubscriptionRequest(
    amount: 99.90,
    currency: Currency::BRL->value,
    interval: SubscriptionInterval::MONTHLY->value,
    customerId: 'cust_123',
    cardToken: 'tok_abc123',
    description: 'Plano Premium',
    trialDays: 7  // 7 dias grátis
);

// Primeira cobrança: 7 dias depois
// Cobranças seguintes: todo mês
```

---

## 🔢 Ciclos Limitados

```php
$request = new SubscriptionRequest(
    amount: 99.90,
    currency: Currency::BRL->value,
    interval: SubscriptionInterval::MONTHLY->value,
    customerId: 'cust_123',
    cardToken: 'tok_abc123',
    description: 'Plano 12 meses',
    cycles: 12  // Cancela após 12 cobranças
);

// Se null = ilimitado
```

---

## 🎛️ Gerenciar Assinatura

### Cancelar

```php
$response = $hub->cancelSubscription($subscriptionId);

if ($response->isSuccess()) {
    echo "Assinatura cancelada!";
}
```

### Suspender

```php
$response = $hub->suspendSubscription($subscriptionId);

if ($response->isSuccess()) {
    echo "Assinatura pausada!";
}
```

### Reativar

```php
$response = $hub->reactivateSubscription($subscriptionId);

if ($response->isSuccess()) {
    echo "Assinatura reativada!";
}
```

### Atualizar

```php
$response = $hub->updateSubscription($subscriptionId, [
    'amount' => 149.90,  // Novo valor
    'card_token' => 'tok_new'  // Novo cartão
]);
```

---

## 💡 Exemplo Completo - SaaS

```php
class SubscriptionController
{
    public function subscribe(Request $request)
    {
        $user = $request->user();
        $plan = Plan::find($request->plan_id);
        
        // 1. Salvar cartão
        $cardToken = $this->hub->tokenizeCard([
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_name,
            'card_expiry_month' => $request->card_month,
            'card_expiry_year' => $request->card_year,
        ]);
        
        // 2. Criar customer no gateway
        $customerResponse = $this->hub->createCustomer(
            new CustomerRequest(
                name: $user->name,
                email: $user->email,
                document: $user->document
            )
        );
        
        // 3. Criar assinatura
        $subscriptionRequest = new SubscriptionRequest(
            amount: $plan->price,
            currency: Currency::BRL->value,
            interval: SubscriptionInterval::MONTHLY->value,
            customerId: $customerResponse->customerId,
            cardToken: $cardToken,
            description: $plan->name,
            trialDays: $plan->trial_days,
            metadata: [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]
        );
        
        $response = $this->hub->createSubscription($subscriptionRequest);
        
        if ($response->isSuccess()) {
            // 4. Salvar no banco
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'subscription_id' => $response->subscriptionId,
                'customer_id' => $customerResponse->customerId,
                'status' => 'active',
                'trial_ends_at' => now()->addDays($plan->trial_days),
                'next_billing_date' => now()->addDays($plan->trial_days),
            ]);
            
            return response()->json([
                'success' => true,
                'subscription_id' => $subscription->id,
                'trial_ends_at' => $subscription->trial_ends_at,
            ]);
        }
        
        throw new \Exception('Erro ao criar assinatura');
    }
    
    public function cancel(Request $request)
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->where('id', $request->subscription_id)
            ->firstOrFail();
        
        $response = $this->hub->cancelSubscription($subscription->subscription_id);
        
        if ($response->isSuccess()) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
            
            return response()->json(['success' => true]);
        }
    }
}
```

---

## 🔔 Webhooks

```php
// webhook.php

switch ($event['type']) {
    case 'subscription.created':
        // Assinatura criada
        break;
        
    case 'subscription.payment_succeeded':
        $subscriptionId = $event['data']['subscription_id'];
        
        $subscription = Subscription::where('subscription_id', $subscriptionId)->first();
        $subscription->update([
            'next_billing_date' => now()->addMonth(),
            'last_payment_at' => now(),
        ]);
        break;
        
    case 'subscription.payment_failed':
        $subscriptionId = $event['data']['subscription_id'];
        
        $subscription = Subscription::where('subscription_id', $subscriptionId)->first();
        $subscription->increment('failed_payments');
        
        // Notificar cliente
        Mail::to($subscription->user->email)
            ->send(new PaymentFailed($subscription));
        
        // Cancelar após 3 falhas
        if ($subscription->failed_payments >= 3) {
            $this->hub->cancelSubscription($subscriptionId);
            $subscription->update(['status' => 'cancelled']);
        }
        break;
        
    case 'subscription.cancelled':
        $subscriptionId = $event['data']['subscription_id'];
        
        $subscription = Subscription::where('subscription_id', $subscriptionId)->first();
        $subscription->update(['status' => 'cancelled']);
        break;
}
```

---

## 📊 Modelos de Negócio

### SaaS Básico

```php
class Plan
{
    const BASIC = [
        'name' => 'Básico',
        'price' => 29.90,
        'interval' => SubscriptionInterval::MONTHLY,
        'features' => ['1 usuário', '10 projetos'],
    ];
    
    const PRO = [
        'name' => 'Pro',
        'price' => 79.90,
        'interval' => SubscriptionInterval::MONTHLY,
        'features' => ['5 usuários', 'Projetos ilimitados'],
    ];
    
    const ENTERPRISE = [
        'name' => 'Enterprise',
        'price' => 299.90,
        'interval' => SubscriptionInterval::MONTHLY,
        'features' => ['Usuários ilimitados', 'Suporte 24/7'],
    ];
}
```

### Academia/Escola

```php
// Planos anuais com desconto
$monthly = 150.00;
$yearly = $monthly * 12 * 0.8; // 20% desconto

$request = new SubscriptionRequest(
    amount: $yearly,
    currency: Currency::BRL->value,
    interval: SubscriptionInterval::YEARLY->value,
    // ...
);
```

### Streaming

```php
// Família (múltiplos usuários)
$request = new SubscriptionRequest(
    amount: 39.90,
    currency: Currency::BRL->value,
    interval: SubscriptionInterval::MONTHLY->value,
    metadata: [
        'max_devices' => 4,
        'max_profiles' => 5,
    ]
);
```

---

## 🎨 Interface de Usuário

```html
<!DOCTYPE html>
<html>
<head>
    <title>Escolha seu Plano</title>
</head>
<body>
    <div class="plans">
        <div class="plan">
            <h3>Básico</h3>
            <div class="price">
                <span class="value">R$ 29,90</span>
                <span class="period">/mês</span>
            </div>
            <ul>
                <li>✅ 1 usuário</li>
                <li>✅ 10 projetos</li>
                <li>❌ Suporte básico</li>
            </ul>
            <button onclick="subscribe('basic')">Assinar</button>
        </div>
        
        <div class="plan featured">
            <div class="badge">Mais Popular</div>
            <h3>Pro</h3>
            <div class="price">
                <span class="value">R$ 79,90</span>
                <span class="period">/mês</span>
            </div>
            <div class="trial">7 dias grátis</div>
            <ul>
                <li>✅ 5 usuários</li>
                <li>✅ Projetos ilimitados</li>
                <li>✅ Suporte prioritário</li>
            </ul>
            <button onclick="subscribe('pro')">Assinar</button>
        </div>
        
        <div class="plan">
            <h3>Enterprise</h3>
            <div class="price">
                <span class="value">R$ 299,90</span>
                <span class="period">/mês</span>
            </div>
            <ul>
                <li>✅ Usuários ilimitados</li>
                <li>✅ Recursos avançados</li>
                <li>✅ Suporte 24/7</li>
            </ul>
            <button onclick="subscribe('enterprise')">Assinar</button>
        </div>
    </div>
</body>
</html>
```

---

## 📈 Métricas Importantes

```php
class SubscriptionMetrics
{
    public function getMRR(): float
    {
        // Monthly Recurring Revenue
        return Subscription::where('status', 'active')
            ->sum('amount');
    }
    
    public function getChurnRate(): float
    {
        $total = Subscription::count();
        $cancelled = Subscription::where('status', 'cancelled')
            ->whereMonth('cancelled_at', now()->month)
            ->count();
        
        return ($cancelled / $total) * 100;
    }
    
    public function getLTV(): float
    {
        // Lifetime Value
        $avgMonthlyRevenue = $this->getMRR() / Subscription::count();
        $avgLifetimeMonths = 24; // exemplo
        
        return $avgMonthlyRevenue * $avgLifetimeMonths;
    }
}
```

---

## 🎯 Boas Práticas

### ✅ Faça

- Ofereça trial (7-14 dias)
- Envie email antes da cobrança
- Permita trocar de plano facilmente
- Mostre próxima data de cobrança
- Implemente webhooks
- Armazene histórico de pagamentos
- Notifique falhas de pagamento

### ❌ Não Faça

- Cobrar sem avisar
- Dificultar cancelamento
- Ignorar falhas de pagamento
- Não ter plano gratuito/trial

---

## 🔧 Tratamento de Erros

```php
try {
    $response = $hub->createSubscription($request);
    
} catch (GatewayException $e) {
    if (str_contains($e->getMessage(), 'card_expired')) {
        return ['error' => 'Cartão expirado'];
    }
    
    if (str_contains($e->getMessage(), 'insufficient_funds')) {
        return ['error' => 'Saldo insuficiente'];
    }
    
    return ['error' => 'Erro ao criar assinatura'];
}
```

---

## 🎯 Próximos Passos

- [**Webhooks**](../advanced/webhooks.md)
- [**Exemplo SaaS**](../examples/saas.md)
- [**Cartão de Crédito**](credit-card.md)
