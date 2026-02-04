# 🔔 Webhooks

## Registrar
```php
$hub->registerWebhook('https://site.com/webhook', [
    'payment.paid',
    'payment.failed'
]);
```

## Processar
```php
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if ($event['type'] === 'payment.paid') {
    $order = Order::findByTransactionId($event['data']['transaction_id']);
    $order->markAsPaid();
}

http_response_code(200);
```

## Validar Assinatura
```php
$signature = $_SERVER['HTTP_X_SIGNATURE'];
$expected = hash_hmac('sha256', $payload, env('WEBHOOK_SECRET'));
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit;
}
```
