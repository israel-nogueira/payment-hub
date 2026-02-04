# 💰 Money - Trabalhando com Valores

Manipule valores monetários de forma segura com o ValueObject Money.

---

## 🎯 Por Que Usar Money?

### ❌ Problema com float

```php
// Perigo! Problemas de precisão
$price = 0.1 + 0.2;
echo $price; // 0.30000000000000004

$total = 10.50 * 3;
echo $total; // 31.499999999999996
```

### ✅ Solução com Money

```php
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;

$price = Money::from(10.50, Currency::BRL);
$total = $price->multiply(3);

echo $total->formatted(); // R$ 31,50 (preciso!)
```

---

## 🚀 Criando Money

### Básico

```php
// A partir de número
$money = Money::from(100.00, Currency::BRL);

// A partir de centavos
$money = Money::fromCents(10000, Currency::BRL);

// Com outras moedas
$dollars = Money::from(50.00, Currency::USD);
$euros = Money::from(75.50, Currency::EUR);
```

### Zero

```php
$zero = Money::zero(Currency::BRL);
echo $zero->value(); // 0.00
```

---

## 🧮 Operações Matemáticas

### Adição

```php
$price1 = Money::from(100.00, Currency::BRL);
$price2 = Money::from(50.00, Currency::BRL);

$total = $price1->add($price2);
echo $total->formatted(); // R$ 150,00
```

### Subtração

```php
$total = Money::from(100.00, Currency::BRL);
$discount = Money::from(20.00, Currency::BRL);

$final = $total->subtract($discount);
echo $final->formatted(); // R$ 80,00
```

### Multiplicação

```php
$price = Money::from(25.50, Currency::BRL);
$total = $price->multiply(3);

echo $total->formatted(); // R$ 76,50
```

### Divisão

```php
$total = Money::from(100.00, Currency::BRL);
$perPerson = $total->divide(4);

echo $perPerson->formatted(); // R$ 25,00
```

---

## 📊 Porcentagens

### Calcular Porcentagem

```php
$price = Money::from(100.00, Currency::BRL);

// 10% do valor
$discount = $price->percentage(10);
echo $discount->formatted(); // R$ 10,00

// 15% de desconto
$discount = $price->percentage(15);
echo $discount->formatted(); // R$ 15,00
```

### Aplicar Desconto

```php
$price = Money::from(100.00, Currency::BRL);
$discount = $price->percentage(10); // R$ 10,00

$final = $price->subtract($discount);
echo $final->formatted(); // R$ 90,00

// Ou direto:
$final = $price->subtract($price->percentage(10));
```

### Aplicar Acréscimo

```php
$price = Money::from(100.00, Currency::BRL);
$tax = $price->percentage(5); // R$ 5,00

$withTax = $price->add($tax);
echo $withTax->formatted(); // R$ 105,00
```

---

## ✂️ Dividir em Parcelas

### Divisão Simples

```php
$total = Money::from(100.00, Currency::BRL);
$installments = $total->split(4);

foreach ($installments as $i => $value) {
    echo "Parcela " . ($i + 1) . ": " . $value->formatted() . "\n";
}

// Saída:
// Parcela 1: R$ 25,00
// Parcela 2: R$ 25,00
// Parcela 3: R$ 25,00
// Parcela 4: R$ 25,00
```

### Com Valores Quebrados

```php
$total = Money::from(100.00, Currency::BRL);
$installments = $total->split(3);

foreach ($installments as $i => $value) {
    echo "Parcela " . ($i + 1) . ": " . $value->formatted() . "\n";
}

// Saída:
// Parcela 1: R$ 33,34  (diferença na primeira)
// Parcela 2: R$ 33,33
// Parcela 3: R$ 33,33
```

---

## 🔄 Comparações

### Igual

```php
$money1 = Money::from(100.00, Currency::BRL);
$money2 = Money::from(100.00, Currency::BRL);

if ($money1->equals($money2)) {
    echo "São iguais!";
}
```

### Maior Que

```php
$money1 = Money::from(150.00, Currency::BRL);
$money2 = Money::from(100.00, Currency::BRL);

if ($money1->greaterThan($money2)) {
    echo "R$ 150 > R$ 100";
}
```

### Menor Que

```php
$money1 = Money::from(50.00, Currency::BRL);
$money2 = Money::from(100.00, Currency::BRL);

if ($money1->lessThan($money2)) {
    echo "R$ 50 < R$ 100";
}
```

### Maior ou Igual

```php
if ($money1->greaterThanOrEqual($money2)) {
    echo "Maior ou igual";
}
```

### Menor ou Igual

```php
if ($money1->lessThanOrEqual($money2)) {
    echo "Menor ou igual";
}
```

### Zero

```php
$money = Money::zero(Currency::BRL);

if ($money->isZero()) {
    echo "É zero!";
}
```

### Positivo/Negativo

```php
$money = Money::from(100.00, Currency::BRL);

if ($money->isPositive()) {
    echo "É positivo!";
}

if ($money->isNegative()) {
    echo "É negativo!";
}
```

---

## 🎨 Formatação

### Formatado

```php
$money = Money::from(1234.56, Currency::BRL);
echo $money->formatted(); // R$ 1.234,56
```

### Valor Bruto

```php
$money = Money::from(100.50, Currency::BRL);
echo $money->value(); // 100.5
```

### Em Centavos

```php
$money = Money::from(100.50, Currency::BRL);
echo $money->cents(); // 10050
```

### Para Array

```php
$money = Money::from(100.00, Currency::BRL);
$array = $money->toArray();

// [
//     'amount' => 100.0,
//     'cents' => 10000,
//     'currency' => 'BRL',
//     'formatted' => 'R$ 100,00'
// ]
```

---

## 💡 Exemplos Práticos

### Carrinho de Compras

```php
class Cart
{
    private array $items = [];
    
    public function add(Product $product, int $quantity = 1): void
    {
        $this->items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $product->price->multiply($quantity)
        ];
    }
    
    public function total(): Money
    {
        $total = Money::zero(Currency::BRL);
        
        foreach ($this->items as $item) {
            $total = $total->add($item['subtotal']);
        }
        
        return $total;
    }
    
    public function totalWithTax(float $taxRate): Money
    {
        $subtotal = $this->total();
        $tax = $subtotal->percentage($taxRate);
        
        return $subtotal->add($tax);
    }
}

// Uso
$cart = new Cart();
$cart->add(new Product('Tênis', Money::from(299.90, Currency::BRL)), 2);
$cart->add(new Product('Meia', Money::from(19.90, Currency::BRL)), 3);

echo "Subtotal: " . $cart->total()->formatted() . "\n";
// R$ 659,50

echo "Total com impostos (10%): " . $cart->totalWithTax(10)->formatted() . "\n";
// R$ 725,45
```

### Sistema de Descontos

```php
class DiscountCalculator
{
    public function apply(Money $price, string $code): Money
    {
        return match($code) {
            'SAVE10' => $price->subtract($price->percentage(10)),
            'SAVE20' => $price->subtract($price->percentage(20)),
            'SAVE50' => $price->subtract($price->percentage(50)),
            'FLAT20' => $price->subtract(Money::from(20.00, Currency::BRL)),
            default => $price
        };
    }
}

// Uso
$calculator = new DiscountCalculator();

$price = Money::from(100.00, Currency::BRL);
$discounted = $calculator->apply($price, 'SAVE20');

echo "Preço original: " . $price->formatted() . "\n";
// R$ 100,00

echo "Com desconto: " . $discounted->formatted() . "\n";
// R$ 80,00
```

### Calculadora de Parcelas

```php
class InstallmentCalculator
{
    public function calculate(
        Money $total,
        int $installments,
        float $interestRate = 0
    ): array {
        if ($interestRate > 0) {
            // Com juros compostos
            $totalWithInterest = $total->multiply(
                pow(1 + $interestRate / 100, $installments)
            );
            $values = $totalWithInterest->split($installments);
        } else {
            // Sem juros
            $values = $total->split($installments);
        }
        
        return array_map(
            fn($value, $i) => [
                'number' => $i + 1,
                'value' => $value,
                'formatted' => $value->formatted()
            ],
            $values,
            array_keys($values)
        );
    }
}

// Uso
$calculator = new InstallmentCalculator();

$total = Money::from(600.00, Currency::BRL);
$plan = $calculator->calculate($total, 6, interestRate: 0);

foreach ($plan as $installment) {
    echo "{$installment['number']}x de {$installment['formatted']}\n";
}

// Saída:
// 1x de R$ 100,00
// 2x de R$ 100,00
// ...
```

### Divisão de Conta

```php
class BillSplitter
{
    public function split(Money $total, int $people): Money
    {
        return $total->divide($people);
    }
    
    public function splitWithTip(Money $total, int $people, float $tipPercent): array
    {
        $tip = $total->percentage($tipPercent);
        $totalWithTip = $total->add($tip);
        $perPerson = $totalWithTip->divide($people);
        
        return [
            'subtotal' => $total,
            'tip' => $tip,
            'total' => $totalWithTip,
            'per_person' => $perPerson,
            'people' => $people
        ];
    }
}

// Uso
$splitter = new BillSplitter();

$bill = Money::from(150.00, Currency::BRL);
$result = $splitter->splitWithTip($bill, 5, tipPercent: 10);

echo "Conta: " . $result['subtotal']->formatted() . "\n";
// R$ 150,00

echo "Gorjeta (10%): " . $result['tip']->formatted() . "\n";
// R$ 15,00

echo "Total: " . $result['total']->formatted() . "\n";
// R$ 165,00

echo "Por pessoa: " . $result['per_person']->formatted() . "\n";
// R$ 33,00
```

---

## 🌍 Múltiplas Moedas

```php
$brl = Money::from(100.00, Currency::BRL);
$usd = Money::from(20.00, Currency::USD);
$eur = Money::from(15.00, Currency::EUR);

echo $brl->formatted(); // R$ 100,00
echo $usd->formatted(); // $20.00
echo $eur->formatted(); // €15.00

// Moedas devem ser iguais para operações
try {
    $total = $brl->add($usd); // ❌ Erro!
} catch (InvalidAmountException $e) {
    echo "Moedas diferentes!";
}
```

---

## 🔒 Imutabilidade

```php
$original = Money::from(100.00, Currency::BRL);
$modified = $original->add(Money::from(50.00, Currency::BRL));

echo $original->formatted(); // R$ 100,00 (não muda!)
echo $modified->formatted(); // R$ 150,00 (novo objeto)
```

---

## ⚠️ Validações

```php
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

try {
    // ❌ Valor negativo
    $money = Money::from(-100.00, Currency::BRL);
} catch (InvalidAmountException $e) {
    echo "Valor não pode ser negativo!";
}

try {
    // ❌ Divisão por zero
    $money = Money::from(100.00, Currency::BRL);
    $result = $money->divide(0);
} catch (InvalidAmountException $e) {
    echo "Não pode dividir por zero!";
}
```

---

## 🎯 Resumo de Métodos

```php
// Criação
Money::from(100.00, Currency::BRL)
Money::fromCents(10000, Currency::BRL)
Money::zero(Currency::BRL)

// Operações
$money->add($other)
$money->subtract($other)
$money->multiply(3)
$money->divide(2)
$money->percentage(10)
$money->split(4)

// Comparações
$money->equals($other)
$money->greaterThan($other)
$money->lessThan($other)
$money->greaterThanOrEqual($other)
$money->lessThanOrEqual($other)
$money->isZero()
$money->isPositive()
$money->isNegative()

// Formatação
$money->value()      // 100.5
$money->cents()      // 10050
$money->formatted()  // R$ 100,50
$money->toArray()    // Array completo
```

---

## 📚 Próximos Passos

- [**Enums**](enums.md) - Currency, PaymentStatus
- [**ValueObjects**](value-objects.md) - CPF, CNPJ, Email
- [**Exemplos E-commerce**](../examples/ecommerce.md)
