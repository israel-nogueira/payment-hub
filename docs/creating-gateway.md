# 🔧 Criando seu Gateway

```php
namespace MeuProjeto\Gateways;

use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;

class MeuGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $apiKey,
        private bool $sandbox = false
    ) {}
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        // Chamar API do gateway
        $response = $this->apiCall('/pix', $request->toArray());
        
        return PaymentResponse::create(
            success: $response['success'],
            transactionId: $response['id'],
            status: $response['status'],
            amount: $request->amount,
            currency: $request->currency
        );
    }
    
    // Implementar outros métodos...
}
```
