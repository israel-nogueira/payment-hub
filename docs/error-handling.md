# ⚠️ Tratamento de Erros

## Exceções Disponíveis

```php
use IsraelNogueira\PaymentHub\Exceptions\{
    InvalidCardNumberException,
    InvalidDocumentException,
    InvalidEmailException,
    InvalidAmountException,
    GatewayException
};
```

## Exemplo Completo

```php
try {
    $cpf = CPF::fromString($input);
    $request = PixPaymentRequest::create(
        amount: $amount,
        customerDocument: $cpf->value()
    );
    $response = $hub->createPixPayment($request);
} catch (InvalidDocumentException $e) {
    return ['error' => 'Documento inválido'];
} catch (GatewayException $e) {
    Log::error($e->getMessage());
    return ['error' => 'Erro no pagamento'];
}
```
