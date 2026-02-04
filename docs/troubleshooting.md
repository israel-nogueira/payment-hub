# 🔧 Troubleshooting

## Class not found
```bash
composer dump-autoload
```

## Invalid document
Valide o CPF/CNPJ antes:
```php
$cpf = CPF::fromString('123.456.789-00');
```

## Gateway exception
```php
try {
    $response = $hub->createPixPayment($request);
} catch (GatewayException $e) {
    Log::error($e->getMessage());
}
```

## Currency mismatch
Use a mesma moeda:
```php
$value1 = Money::from(100, Currency::BRL);
$value2 = Money::from(50, Currency::BRL);
```
