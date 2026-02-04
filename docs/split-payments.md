# 💰 Split de Pagamento

Divida pagamentos entre múltiplos recebedores (marketplace).

---

## 🎯 O Que É Split?

Divide um pagamento entre vários recebedores:

```
Cliente paga R$ 100
├── R$ 80 → Vendedor
├── R$ 15 → Marketplace (taxa)
└── R$ 5  → Entregador
```

---

## 🚀 Split Básico

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SplitPaymentRequest;

$request = new SplitPaymentRequest(
    amount: 100.00,
    currency: 'BRL',
    paymentMethod: 'credit_card',
    splits: [
        [
            'recipient_id' => 'seller_123',
            'amount' => 80.00,
            'percentage' => null,
        ],
        [
            'recipient_id' => 'marketplace',
            'amount' => 15.00,
            'percentage' => null,
        ],
        [
            'recipient_id' => 'delivery',
            'amount' => 5.00,
            'percentage' => null,
        ],
    ]
);

$response = $hub->createSplitPayment($request);
```

---

## 📊 Split por Porcentagem

```php
$request = new SplitPaymentRequest(
    amount: 100.00,
    currency: 'BRL',
    paymentMethod: 'credit_card',
    splits: [
        [
            'recipient_id' => 'seller_123',
            'percentage' => 80,  // 80%
        ],
        [
            'recipient_id' => 'marketplace',
            'percentage' => 15,  // 15%
        ],
        [
            'recipient_id' => 'delivery',
            'percentage' => 5,   // 5%
        ],
    ]
);
```

---

## 💡 Exemplo - Marketplace

```php
class MarketplaceCheckout
{
    public function process(Cart $cart, array $paymentData)
    {
        $total = $cart->total();
        $platformFee = $total->percentage(10); // 10% plataforma
        $sellerAmount = $total->subtract($platformFee);
        
        $request = new SplitPaymentRequest(
            amount: $total->value(),
            currency: 'BRL',
            paymentMethod: 'credit_card',
            cardNumber: $paymentData['card_number'],
            // ... outros dados do cartão
            splits: [
                [
                    'recipient_id' => $cart->seller_id,
                    'amount' => $sellerAmount->value(),
                ],
                [
                    'recipient_id' => 'platform',
                    'amount' => $platformFee->value(),
                ],
            ],
            metadata: [
                'order_id' => $cart->id,
            ]
        );
        
        return $this->hub->createSplitPayment($request);
    }
}
```

---

## 🎯 Casos de Uso

### E-commerce Marketplace

```php
// Vendedor recebe 90%, marketplace 10%
$splits = [
    ['recipient_id' => $seller->id, 'percentage' => 90],
    ['recipient_id' => 'platform', 'percentage' => 10],
];
```

### Delivery

```php
// Restaurante 85%, entregador 10%, plataforma 5%
$splits = [
    ['recipient_id' => $restaurant->id, 'percentage' => 85],
    ['recipient_id' => $driver->id, 'percentage' => 10],
    ['recipient_id' => 'platform', 'percentage' => 5],
];
```

### Afiliados

```php
// Vendedor 70%, afiliado 20%, plataforma 10%
$splits = [
    ['recipient_id' => $seller->id, 'percentage' => 70],
    ['recipient_id' => $affiliate->id, 'percentage' => 20],
    ['recipient_id' => 'platform', 'percentage' => 10],
];
```

---

## 🎯 Próximos Passos

- [**Sub-contas**](sub-accounts.md)
- [**Exemplo Marketplace**](../examples/marketplace.md)
