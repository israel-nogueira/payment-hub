# 🎨 Enums - Type-Safety

Conheça todos os Enums do PaymentHub e aprenda a usá-los.

---

## 🎯 Por Que Usar Enums?

### ❌ Sem Enums (Perigoso)

```php
$status = 'paid';  // E se digitar 'piad'?
$currency = 'BRL'; // E se digitar 'BrL'?

if ($status === 'payd') {  // ❌ Typo! Nunca vai entrar aqui
    echo "Aprovado";
}
```

### ✅ Com Enums (Seguro)

```php
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Enums\Currency;

$status = PaymentStatus::PAID;    // ✅ Autocomplete
$currency = Currency::BRL;        // ✅ Impossível errar

if ($status === PaymentStatus::PAID) {  // ✅ Type-safe
    echo "Aprovado";
}
```

---

## 💰 Currency (Moedas)

### Moedas Disponíveis

```php
use IsraelNogueira\PaymentHub\Enums\Currency;

Currency::BRL;  // Real Brasileiro
Currency::USD;  // Dólar Americano
Currency::EUR;  // Euro
Currency::GBP;  // Libra Esterlina
Currency::ARS;  // Peso Argentino
Currency::CLP;  // Peso Chileno
Currency::COP;  // Peso Colombiano
Currency::MXN;  // Peso Mexicano
Currency::PEN;  // Sol Peruano
Currency::UYU;  // Peso Uruguaio
```

### Propriedades

```php
$currency = Currency::BRL;

echo $currency->value;      // 'BRL'
echo $currency->symbol();   // 'R$'
echo $currency->name();     // 'Real Brasileiro'
echo $currency->decimals(); // 2
```

### Formatação

```php
$currency = Currency::BRL;

echo $currency->format(1234.56);
// R$ 1.234,56

$currency = Currency::USD;
echo $currency->format(1234.56);
// $1,234.56
```

### Verificações

```php
$currency = Currency::BRL;

// É latino-americano?
if ($currency->isLatinAmerican()) {
    echo "Moeda da América Latina";
}

// Moedas latino-americanas
$latinCurrencies = Currency::latinAmerican();
// [BRL, ARS, CLP, COP, MXN, PEN, UYU]
```

### Exemplo Prático

```php
class PriceFormatter
{
    public function format(float $amount, Currency $currency): string
    {
        return match($currency) {
            Currency::BRL => "R$ " . number_format($amount, 2, ',', '.'),
            Currency::USD => "$" . number_format($amount, 2, '.', ','),
            Currency::EUR => "€" . number_format($amount, 2, ',', '.'),
            default => $currency->format($amount)
        };
    }
}

$formatter = new PriceFormatter();
echo $formatter->format(1234.56, Currency::BRL);
// R$ 1.234,56
```

---

## 📊 PaymentStatus

### Status Disponíveis

```php
use IsraelNogueira\PaymentHub\Enums\PaymentStatus;

PaymentStatus::PENDING;           // Pendente
PaymentStatus::AUTHORIZED;        // Autorizado
PaymentStatus::PAID;             // Pago/Aprovado
PaymentStatus::REFUNDED;         // Estornado
PaymentStatus::PARTIALLY_REFUNDED; // Estorno Parcial
PaymentStatus::CANCELLED;        // Cancelado
PaymentStatus::FAILED;           // Falhou
PaymentStatus::EXPIRED;          // Expirado
PaymentStatus::PROCESSING;       // Processando
PaymentStatus::UNDER_REVIEW;     // Em Análise
PaymentStatus::CHARGEBACK;       // Chargeback
```

### Propriedades

```php
$status = PaymentStatus::PAID;

echo $status->value;     // 'paid'
echo $status->label();   // 'Aprovado'
echo $status->color();   // 'green'
echo $status->icon();    // '✅'
```

### Métodos de Verificação

```php
$status = PaymentStatus::PAID;

if ($status->isPaid()) {
    echo "Pagamento aprovado!";
}

if ($status->isPending()) {
    echo "Aguardando pagamento...";
}

if ($status->isFailed()) {
    echo "Pagamento recusado!";
}

if ($status->isCancelled()) {
    echo "Pagamento cancelado";
}

if ($status->isRefunded()) {
    echo "Pagamento estornado";
}
```

### Todos os Métodos

```php
$status->isPaid()              // Pago?
$status->isPending()           // Pendente?
$status->isFailed()            // Falhou?
$status->isCancelled()         // Cancelado?
$status->isRefunded()          // Estornado?
$status->isExpired()           // Expirado?
$status->isProcessing()        // Processando?
$status->isUnderReview()       // Em análise?
$status->isChargeback()        // Chargeback?
$status->isAuthorized()        // Autorizado?
$status->isPartiallyRefunded() // Estorno parcial?
```

### Agrupamentos

```php
// Status finais (não mudam mais)
if ($status->isFinal()) {
    echo "Status final";
}
// PAID, REFUNDED, CANCELLED, FAILED, EXPIRED

// Status que pode ser estornado
if ($status->canRefund()) {
    echo "Pode estornar";
}
// PAID, PARTIALLY_REFUNDED

// Status positivos
if ($status->isSuccessful()) {
    echo "Sucesso!";
}
// PAID, AUTHORIZED
```

### Exemplo com Badge HTML

```php
function statusBadge(PaymentStatus $status): string
{
    $color = $status->color();
    $label = $status->label();
    $icon = $status->icon();
    
    return "<span class='badge badge-{$color}'>{$icon} {$label}</span>";
}

echo statusBadge(PaymentStatus::PAID);
// <span class='badge badge-green'>✅ Aprovado</span>

echo statusBadge(PaymentStatus::PENDING);
// <span class='badge badge-yellow'>⏳ Pendente</span>
```

---

## 💳 PaymentMethod

### Métodos Disponíveis

```php
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;

PaymentMethod::PIX;
PaymentMethod::CREDIT_CARD;
PaymentMethod::DEBIT_CARD;
PaymentMethod::BOLETO;
PaymentMethod::BANK_TRANSFER;
PaymentMethod::WALLET;
PaymentMethod::CRYPTO;
PaymentMethod::CASH;
```

### Propriedades

```php
$method = PaymentMethod::CREDIT_CARD;

echo $method->value;  // 'credit_card'
echo $method->label(); // 'Cartão de Crédito'
echo $method->icon();  // '💳'
```

### Características

```php
$method = PaymentMethod::CREDIT_CARD;

// Suporta parcelamento?
if ($method->supportsInstallments()) {
    echo "Aceita parcelamento";
}

// Aprovação instantânea?
if ($method->isInstant()) {
    echo "Aprovação imediata";
}

// Requer validação extra?
if ($method->requiresValidation()) {
    echo "Precisa validar dados";
}

// Tempo típico de processamento
echo $method->typicalProcessingTime() . " minutos";
```

### Métodos por Moeda

```php
// Métodos disponíveis para BRL
$methods = PaymentMethod::availableFor(Currency::BRL);
// [PIX, CREDIT_CARD, DEBIT_CARD, BOLETO, ...]

// Métodos disponíveis para USD
$methods = PaymentMethod::availableFor(Currency::USD);
// [CREDIT_CARD, DEBIT_CARD, BANK_TRANSFER, ...]
```

### Exemplo de Seleção

```php
function paymentMethodSelector(Currency $currency): string
{
    $methods = PaymentMethod::availableFor($currency);
    
    $html = '<select name="payment_method">';
    foreach ($methods as $method) {
        $html .= sprintf(
            '<option value="%s">%s %s</option>',
            $method->value,
            $method->icon(),
            $method->label()
        );
    }
    $html .= '</select>';
    
    return $html;
}

echo paymentMethodSelector(Currency::BRL);
```

---

## 🔄 SubscriptionInterval

### Intervalos Disponíveis

```php
use IsraelNogueira\PaymentHub\Enums\SubscriptionInterval;

SubscriptionInterval::DAILY;       // Diário
SubscriptionInterval::WEEKLY;      // Semanal
SubscriptionInterval::BIWEEKLY;    // Quinzenal
SubscriptionInterval::MONTHLY;     // Mensal
SubscriptionInterval::BIMONTHLY;   // Bimestral
SubscriptionInterval::QUARTERLY;   // Trimestral
SubscriptionInterval::SEMIANNUALLY; // Semestral
SubscriptionInterval::YEARLY;      // Anual
```

### Propriedades

```php
$interval = SubscriptionInterval::MONTHLY;

echo $interval->value;      // 'monthly'
echo $interval->label();    // 'Mensal'
echo $interval->days();     // 30
echo $interval->months();   // 1
```

### Cálculos

```php
$interval = SubscriptionInterval::MONTHLY;

// Próxima cobrança
$next = $interval->nextBillingDate();
echo $next->format('d/m/Y');

// Data específica
$next = $interval->nextBillingDate(new DateTime('2024-01-15'));
echo $next->format('d/m/Y'); // 15/02/2024
```

### Exemplo de Assinatura

```php
class SubscriptionPlan
{
    public function __construct(
        public string $name,
        public Money $price,
        public SubscriptionInterval $interval
    ) {}
    
    public function describe(): string
    {
        return sprintf(
            "%s - %s por %s",
            $this->name,
            $this->price->formatted(),
            strtolower($this->interval->label())
        );
    }
    
    public function annualCost(): Money
    {
        $paymentsPerYear = 12 / $this->interval->months();
        return $this->price->multiply($paymentsPerYear);
    }
}

$plan = new SubscriptionPlan(
    'Premium',
    Money::from(29.90, Currency::BRL),
    SubscriptionInterval::MONTHLY
);

echo $plan->describe();
// Premium - R$ 29,90 por mensal

echo $plan->annualCost()->formatted();
// R$ 358,80
```

---

## 🎨 Usando Match

```php
$status = PaymentStatus::PAID;

$message = match($status) {
    PaymentStatus::PAID => "✅ Pagamento aprovado!",
    PaymentStatus::PENDING => "⏳ Aguardando confirmação...",
    PaymentStatus::FAILED => "❌ Pagamento recusado",
    PaymentStatus::CANCELLED => "🚫 Pagamento cancelado",
    PaymentStatus::REFUNDED => "↩️ Valor estornado",
    default => "Status desconhecido"
};

echo $message;
```

---

## 🎯 Exemplo Completo - Dashboard

```php
class PaymentDashboard
{
    public function getStats(array $payments): array
    {
        $stats = [
            'total' => Money::zero(Currency::BRL),
            'paid' => Money::zero(Currency::BRL),
            'pending' => Money::zero(Currency::BRL),
            'failed' => 0,
            'by_method' => [],
        ];
        
        foreach ($payments as $payment) {
            $amount = Money::from($payment->amount, Currency::BRL);
            
            // Total geral
            $stats['total'] = $stats['total']->add($amount);
            
            // Por status
            if ($payment->status->isPaid()) {
                $stats['paid'] = $stats['paid']->add($amount);
            } elseif ($payment->status->isPending()) {
                $stats['pending'] = $stats['pending']->add($amount);
            } elseif ($payment->status->isFailed()) {
                $stats['failed']++;
            }
            
            // Por método
            $method = $payment->method->value;
            if (!isset($stats['by_method'][$method])) {
                $stats['by_method'][$method] = [
                    'count' => 0,
                    'total' => Money::zero(Currency::BRL),
                    'label' => $payment->method->label(),
                    'icon' => $payment->method->icon(),
                ];
            }
            
            $stats['by_method'][$method]['count']++;
            $stats['by_method'][$method]['total'] = 
                $stats['by_method'][$method]['total']->add($amount);
        }
        
        return $stats;
    }
}
```

---

## 💡 Dicas

### Use Match ao Invés de Switch

```php
// ✅ Bom - Match expression
$color = match($status) {
    PaymentStatus::PAID => 'green',
    PaymentStatus::PENDING => 'yellow',
    PaymentStatus::FAILED => 'red',
    default => 'gray'
};

// ❌ Evite - Switch statement
switch ($status) {
    case PaymentStatus::PAID:
        $color = 'green';
        break;
    case PaymentStatus::PENDING:
        $color = 'yellow';
        break;
    // ...
}
```

### Type Hints

```php
// ✅ Type-safe
function processPayment(
    PaymentStatus $status,
    Currency $currency
): void {
    // ...
}

// ❌ Evite strings
function processPayment(
    string $status,
    string $currency
): void {
    // ...
}
```

### Validação

```php
// Enum já valida!
try {
    $status = PaymentStatus::from('invalid'); // ❌
} catch (ValueError $e) {
    echo "Status inválido!";
}

// Verificar se existe
if (PaymentStatus::tryFrom('paid')) {
    echo "Status válido!";
}
```

---

## 📚 Referência Rápida

```php
// Currency
Currency::BRL->symbol()      // R$
Currency::BRL->format(100)   // R$ 100,00
Currency::BRL->decimals()    // 2

// PaymentStatus
PaymentStatus::PAID->label()    // Aprovado
PaymentStatus::PAID->color()    // green
PaymentStatus::PAID->isPaid()   // true

// PaymentMethod
PaymentMethod::PIX->label()                 // PIX
PaymentMethod::PIX->isInstant()             // true
PaymentMethod::PIX->supportsInstallments()  // false

// SubscriptionInterval
SubscriptionInterval::MONTHLY->days()    // 30
SubscriptionInterval::MONTHLY->months()  // 1
```

---

## 🎯 Próximos Passos

- [**Money**](money.md) - Trabalhando com valores
- [**ValueObjects**](value-objects.md) - CPF, CNPJ, Email
- [**API Reference**](../api-reference/enums/)
