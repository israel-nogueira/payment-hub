# 🛒 E-commerce Completo

Exemplo prático de implementação completa de pagamentos em e-commerce.

---

## 🎯 O Que Vamos Construir

Um checkout completo com:

- ✅ Carrinho de compras
- ✅ Múltiplos métodos de pagamento (PIX, Cartão, Boleto)
- ✅ Cálculo de frete
- ✅ Cupons de desconto
- ✅ Parcelamento
- ✅ Webhooks
- ✅ Confirmação automática

---

## 📁 Estrutura

```
app/
├── Models/
│   ├── Cart.php
│   ├── Order.php
│   └── Product.php
├── Services/
│   ├── CheckoutService.php
│   └── PaymentService.php
└── Controllers/
    ├── CartController.php
    └── CheckoutController.php
```

---

## 🛍️ Model: Product

```php
<?php

namespace App\Models;

class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public Money $price,
        public int $stock,
        public ?string $image = null
    ) {}
    
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }
    
    public function decrementStock(int $quantity): void
    {
        $this->stock -= $quantity;
    }
}
```

---

## 🛒 Model: Cart

```php
<?php

namespace App\Models;

use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;

class Cart
{
    private array $items = [];
    private ?string $couponCode = null;
    
    public function add(Product $product, int $quantity = 1): void
    {
        if (!$product->hasStock($quantity)) {
            throw new \Exception('Produto sem estoque');
        }
        
        $key = "product_{$product->id}";
        
        if (isset($this->items[$key])) {
            $this->items[$key]['quantity'] += $quantity;
        } else {
            $this->items[$key] = [
                'product' => $product,
                'quantity' => $quantity,
            ];
        }
    }
    
    public function remove(int $productId): void
    {
        unset($this->items["product_{$productId}"]);
    }
    
    public function updateQuantity(int $productId, int $quantity): void
    {
        $key = "product_{$productId}";
        
        if (isset($this->items[$key])) {
            if ($quantity <= 0) {
                $this->remove($productId);
            } else {
                $this->items[$key]['quantity'] = $quantity;
            }
        }
    }
    
    public function applyCoupon(string $code): void
    {
        $this->couponCode = $code;
    }
    
    public function subtotal(): Money
    {
        $total = Money::zero(Currency::BRL);
        
        foreach ($this->items as $item) {
            $itemTotal = $item['product']->price
                ->multiply($item['quantity']);
            $total = $total->add($itemTotal);
        }
        
        return $total;
    }
    
    public function discount(): Money
    {
        if (!$this->couponCode) {
            return Money::zero(Currency::BRL);
        }
        
        $subtotal = $this->subtotal();
        
        return match($this->couponCode) {
            'SAVE10' => $subtotal->percentage(10),
            'SAVE20' => $subtotal->percentage(20),
            'FLAT20' => Money::from(20.00, Currency::BRL),
            default => Money::zero(Currency::BRL)
        };
    }
    
    public function shipping(string $zipCode): Money
    {
        // Simples: R$ 15 fixo
        // Na vida real: integrar com Correios/transportadora
        return Money::from(15.00, Currency::BRL);
    }
    
    public function total(string $zipCode): Money
    {
        return $this->subtotal()
            ->subtract($this->discount())
            ->add($this->shipping($zipCode));
    }
    
    public function items(): array
    {
        return $this->items;
    }
    
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
    
    public function clear(): void
    {
        $this->items = [];
        $this->couponCode = null;
    }
}
```

---

## 📦 Model: Order

```php
<?php

namespace App\Models;

use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;

class Order
{
    public function __construct(
        public ?int $id = null,
        public ?int $userId = null,
        public ?string $transactionId = null,
        public ?Money $amount = null,
        public ?PaymentMethod $paymentMethod = null,
        public ?PaymentStatus $status = null,
        public ?array $items = null,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $paidAt = null
    ) {
        $this->status ??= PaymentStatus::PENDING;
        $this->createdAt ??= new \DateTime();
    }
    
    public static function fromCart(
        Cart $cart,
        int $userId,
        string $zipCode
    ): self {
        return new self(
            userId: $userId,
            amount: $cart->total($zipCode),
            items: array_map(
                fn($item) => [
                    'product_id' => $item['product']->id,
                    'name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['product']->price->value(),
                ],
                $cart->items()
            )
        );
    }
    
    public function markAsPaid(): void
    {
        $this->status = PaymentStatus::PAID;
        $this->paidAt = new \DateTime();
    }
    
    public function markAsFailed(): void
    {
        $this->status = PaymentStatus::FAILED;
    }
    
    public function save(): void
    {
        // Salvar no banco de dados
        // DB::table('orders')->insert([...])
    }
}
```

---

## 💼 Service: PaymentService

```php
<?php

namespace App\Services;

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\{Currency, PaymentMethod};

class PaymentService
{
    public function __construct(
        private PaymentHub $hub
    ) {}
    
    public function createPayment(
        Order $order,
        PaymentMethod $method,
        array $paymentData
    ): array {
        return match($method) {
            PaymentMethod::PIX => $this->createPix($order, $paymentData),
            PaymentMethod::CREDIT_CARD => $this->createCreditCard($order, $paymentData),
            PaymentMethod::BOLETO => $this->createBoleto($order, $paymentData),
            default => throw new \Exception('Método não suportado')
        };
    }
    
    private function createPix(Order $order, array $data): array
    {
        $request = PixPaymentRequest::create(
            amount: $order->amount->value(),
            currency: Currency::BRL,
            description: "Pedido #{$order->id}",
            customerName: $data['customer_name'],
            customerDocument: $data['customer_document'],
            customerEmail: $data['customer_email'],
            expiresInMinutes: 30,
            metadata: [
                'order_id' => $order->id,
                'user_id' => $order->userId,
            ]
        );
        
        $response = $this->hub->createPixPayment($request);
        
        if ($response->isSuccess()) {
            $order->transactionId = $response->transactionId;
            $order->paymentMethod = PaymentMethod::PIX;
            $order->save();
            
            return [
                'success' => true,
                'transaction_id' => $response->transactionId,
                'qr_code' => $this->hub->getPixQrCode($response->transactionId),
                'qr_code_text' => $this->hub->getPixCopyPaste($response->transactionId),
            ];
        }
        
        throw new \Exception($response->message ?? 'Erro ao criar PIX');
    }
    
    private function createCreditCard(Order $order, array $data): array
    {
        $request = CreditCardPaymentRequest::create(
            amount: $order->amount->value(),
            currency: Currency::BRL,
            cardNumber: $data['card_number'],
            cardHolderName: $data['card_name'],
            cardExpiryMonth: $data['card_month'],
            cardExpiryYear: $data['card_year'],
            cardCvv: $data['card_cvv'],
            installments: $data['installments'] ?? 1,
            capture: true,
            description: "Pedido #{$order->id}",
            customerEmail: $data['customer_email'],
            customerDocument: $data['customer_document'],
            metadata: [
                'order_id' => $order->id,
                'user_id' => $order->userId,
            ]
        );
        
        $response = $this->hub->createCreditCardPayment($request);
        
        if ($response->isSuccess()) {
            $order->transactionId = $response->transactionId;
            $order->paymentMethod = PaymentMethod::CREDIT_CARD;
            
            if ($response->status->isPaid()) {
                $order->markAsPaid();
            }
            
            $order->save();
            
            return [
                'success' => true,
                'transaction_id' => $response->transactionId,
                'status' => $response->status->value,
            ];
        }
        
        throw new \Exception($response->message ?? 'Pagamento recusado');
    }
    
    private function createBoleto(Order $order, array $data): array
    {
        $request = new BoletoPaymentRequest(
            amount: $order->amount->value(),
            currency: Currency::BRL->value,
            dueDate: (new \DateTime('+3 days'))->format('Y-m-d'),
            description: "Pedido #{$order->id}",
            customerName: $data['customer_name'],
            customerDocument: $data['customer_document'],
            customerEmail: $data['customer_email']
        );
        
        $response = $this->hub->createBoleto($request);
        
        if ($response->isSuccess()) {
            $order->transactionId = $response->transactionId;
            $order->paymentMethod = PaymentMethod::BOLETO;
            $order->save();
            
            return [
                'success' => true,
                'transaction_id' => $response->transactionId,
                'boleto_url' => $this->hub->getBoletoUrl($response->transactionId),
                'barcode' => $response->rawResponse['barcode'] ?? null,
            ];
        }
        
        throw new \Exception($response->message ?? 'Erro ao gerar boleto');
    }
}
```

---

## 🎯 Service: CheckoutService

```php
<?php

namespace App\Services;

use App\Models\{Cart, Order};

class CheckoutService
{
    public function __construct(
        private PaymentService $paymentService
    ) {}
    
    public function process(
        Cart $cart,
        int $userId,
        array $customerData,
        array $paymentData
    ): array {
        // 1. Validar carrinho
        if ($cart->isEmpty()) {
            throw new \Exception('Carrinho vazio');
        }
        
        // 2. Criar pedido
        $order = Order::fromCart($cart, $userId, $customerData['zip_code']);
        $order->save();
        
        // 3. Processar pagamento
        try {
            $result = $this->paymentService->createPayment(
                $order,
                $paymentData['method'],
                array_merge($customerData, $paymentData)
            );
            
            // 4. Decrementar estoque
            foreach ($cart->items() as $item) {
                $item['product']->decrementStock($item['quantity']);
            }
            
            // 5. Limpar carrinho
            $cart->clear();
            
            return array_merge($result, [
                'order_id' => $order->id,
            ]);
            
        } catch (\Exception $e) {
            $order->markAsFailed();
            $order->save();
            
            throw $e;
        }
    }
}
```

---

## 🎮 Controller: CheckoutController

```php
<?php

namespace App\Controllers;

use App\Services\CheckoutService;
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;

class CheckoutController
{
    public function __construct(
        private CheckoutService $checkoutService
    ) {}
    
    public function show(Request $request)
    {
        $cart = $request->session()->get('cart');
        
        return view('checkout', [
            'cart' => $cart,
            'subtotal' => $cart->subtotal()->formatted(),
            'discount' => $cart->discount()->formatted(),
            'shipping' => $cart->shipping('12345')->formatted(),
            'total' => $cart->total('12345')->formatted(),
        ]);
    }
    
    public function process(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required',
            'customer_email' => 'required|email',
            'customer_document' => 'required',
            'customer_phone' => 'required',
            'zip_code' => 'required',
            'payment_method' => 'required',
            // Campos do cartão (se necessário)
            'card_number' => 'required_if:payment_method,credit_card',
            'card_name' => 'required_if:payment_method,credit_card',
            'card_month' => 'required_if:payment_method,credit_card',
            'card_year' => 'required_if:payment_method,credit_card',
            'card_cvv' => 'required_if:payment_method,credit_card',
            'installments' => 'nullable|integer',
        ]);
        
        $cart = $request->session()->get('cart');
        $method = PaymentMethod::from($validated['payment_method']);
        
        try {
            $result = $this->checkoutService->process(
                cart: $cart,
                userId: $request->user()->id,
                customerData: $validated,
                paymentData: array_merge(
                    ['method' => $method],
                    $validated
                )
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
```

---

## 🔔 Webhook Handler

```php
<?php

// webhook.php

use App\Models\Order;

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// Validar assinatura (importante!)
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
if (!validateSignature($payload, $signature)) {
    http_response_code(401);
    exit;
}

if ($event['type'] === 'payment.paid') {
    $transactionId = $event['data']['transaction_id'];
    
    $order = Order::findByTransactionId($transactionId);
    
    if ($order) {
        $order->markAsPaid();
        $order->save();
        
        // Enviar email de confirmação
        sendConfirmationEmail($order);
        
        // Gerar nota fiscal
        generateInvoice($order);
    }
}

http_response_code(200);

function validateSignature(string $payload, string $signature): bool
{
    $secret = env('PAYMENT_WEBHOOK_SECRET');
    $expected = hash_hmac('sha256', $payload, $secret);
    
    return hash_equals($expected, $signature);
}
```

---

## 🎨 View: Checkout

```html
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .payment-methods {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .payment-method {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        .payment-method.active {
            border-color: #32BCAD;
            background: #f0fffe;
        }
        .summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .summary-total {
            font-size: 24px;
            font-weight: bold;
            color: #32BCAD;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-form">
            <h2>Finalizar Compra</h2>
            
            <!-- Dados do Cliente -->
            <div class="section">
                <h3>Dados Pessoais</h3>
                <input type="text" id="customerName" placeholder="Nome completo">
                <input type="email" id="customerEmail" placeholder="E-mail">
                <input type="text" id="customerDocument" placeholder="CPF/CNPJ">
                <input type="text" id="customerPhone" placeholder="Telefone">
                <input type="text" id="zipCode" placeholder="CEP">
            </div>
            
            <!-- Método de Pagamento -->
            <div class="section">
                <h3>Forma de Pagamento</h3>
                <div class="payment-methods">
                    <div class="payment-method active" data-method="pix">
                        💰 PIX
                    </div>
                    <div class="payment-method" data-method="credit_card">
                        💳 Cartão de Crédito
                    </div>
                    <div class="payment-method" data-method="boleto">
                        📄 Boleto
                    </div>
                </div>
                
                <!-- Formulários de pagamento -->
                <div id="pix-form" class="payment-form active">
                    <p>Você será redirecionado para a tela de pagamento PIX</p>
                </div>
                
                <div id="card-form" class="payment-form">
                    <input type="text" id="cardNumber" placeholder="Número do cartão">
                    <input type="text" id="cardName" placeholder="Nome no cartão">
                    <div class="row">
                        <select id="cardMonth">
                            <option>Mês</option>
                            <!-- ... -->
                        </select>
                        <select id="cardYear">
                            <option>Ano</option>
                            <!-- ... -->
                        </select>
                        <input type="text" id="cardCvv" placeholder="CVV">
                    </div>
                    <select id="installments">
                        <option value="1">1x sem juros</option>
                        <option value="2">2x sem juros</option>
                        <option value="3">3x sem juros</option>
                    </select>
                </div>
                
                <div id="boleto-form" class="payment-form">
                    <p>Boleto será gerado após confirmação</p>
                </div>
            </div>
            
            <button onclick="checkout()" class="btn-checkout">
                Finalizar Compra
            </button>
        </div>
        
        <!-- Resumo -->
        <div class="summary">
            <h3>Resumo do Pedido</h3>
            
            <div class="summary-line">
                <span>Subtotal:</span>
                <span><?= $subtotal ?></span>
            </div>
            
            <div class="summary-line">
                <span>Desconto:</span>
                <span class="discount">-<?= $discount ?></span>
            </div>
            
            <div class="summary-line">
                <span>Frete:</span>
                <span><?= $shipping ?></span>
            </div>
            
            <div class="summary-total">
                <div class="summary-line">
                    <span>Total:</span>
                    <span><?= $total ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Trocar método de pagamento
        document.querySelectorAll('.payment-method').forEach(el => {
            el.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(e => 
                    e.classList.remove('active')
                );
                this.classList.add('active');
                
                const method = this.dataset.method;
                document.querySelectorAll('.payment-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.getElementById(method + '-form').style.display = 'block';
            });
        });
        
        // Checkout
        async function checkout() {
            const method = document.querySelector('.payment-method.active').dataset.method;
            
            const data = {
                customer_name: document.getElementById('customerName').value,
                customer_email: document.getElementById('customerEmail').value,
                customer_document: document.getElementById('customerDocument').value,
                customer_phone: document.getElementById('customerPhone').value,
                zip_code: document.getElementById('zipCode').value,
                payment_method: method,
            };
            
            if (method === 'credit_card') {
                data.card_number = document.getElementById('cardNumber').value;
                data.card_name = document.getElementById('cardName').value;
                data.card_month = document.getElementById('cardMonth').value;
                data.card_year = document.getElementById('cardYear').value;
                data.card_cvv = document.getElementById('cardCvv').value;
                data.installments = document.getElementById('installments').value;
            }
            
            try {
                const response = await fetch('/checkout/process', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (method === 'pix') {
                        window.location.href = `/order/${result.order_id}/pix`;
                    } else if (method === 'credit_card') {
                        window.location.href = `/order/${result.order_id}/success`;
                    } else if (method === 'boleto') {
                        window.location.href = `/order/${result.order_id}/boleto`;
                    }
                } else {
                    alert(result.error);
                }
                
            } catch (error) {
                alert('Erro ao processar pagamento');
            }
        }
    </script>
</body>
</html>
```

---

## 📊 Resumo

Este exemplo mostra:

- ✅ Carrinho com cálculos
- ✅ Múltiplos métodos de pagamento
- ✅ Serviços desacoplados
- ✅ Webhooks
- ✅ Controle de estoque
- ✅ Interface completa

---

## 🎯 Próximos Passos

- [**Webhooks Avançados**](../advanced/webhooks.md)
- [**Marketplace**](marketplace.md)
- [**Assinaturas**](subscriptions-example.md)
