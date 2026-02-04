# ⚙️ Configuração

Configure o PaymentHub para desenvolvimento e produção.

---

## 🏗️ Estrutura Recomendada

```
seu-projeto/
├── config/
│   └── payment.php          # Configurações
├── .env                     # Credenciais (nunca commitar!)
├── .env.example             # Template do .env
├── src/
│   └── Services/
│       └── PaymentService.php
└── bootstrap/
    └── payment.php          # Inicialização
```

---

## 📝 Arquivo de Configuração

### config/payment.php

```php
<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Gateway Padrão
    |--------------------------------------------------------------------------
    |
    | Define qual gateway será usado por padrão.
    | Opções: 'fake', 'stripe', 'pagarme', etc.
    |
    */
    'default' => env('PAYMENT_GATEWAY', 'fake'),
    
    /*
    |--------------------------------------------------------------------------
    | Gateways Disponíveis
    |--------------------------------------------------------------------------
    |
    | Configure todos os gateways que você vai usar.
    | Cada gateway tem sua própria configuração.
    |
    */
    'gateways' => [
        
        'fake' => [
            'class' => \IsraelNogueira\PaymentHub\Gateways\FakeBankGateway::class,
            'enabled' => env('PAYMENT_FAKE_ENABLED', true),
        ],
        
        'stripe' => [
            'class' => \MeuProjeto\Gateways\StripeGateway::class,
            'api_key' => env('STRIPE_API_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'sandbox' => env('STRIPE_SANDBOX', true),
            'enabled' => env('PAYMENT_STRIPE_ENABLED', false),
        ],
        
        'pagarme' => [
            'class' => \MeuProjeto\Gateways\PagarMeGateway::class,
            'api_key' => env('PAGARME_API_KEY'),
            'sandbox' => env('PAGARME_SANDBOX', true),
            'enabled' => env('PAYMENT_PAGARME_ENABLED', false),
        ],
        
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configurações Globais
    |--------------------------------------------------------------------------
    */
    'global' => [
        'currency' => env('PAYMENT_CURRENCY', 'BRL'),
        'timeout' => env('PAYMENT_TIMEOUT', 30),
        'retry_attempts' => env('PAYMENT_RETRY_ATTEMPTS', 3),
        'log_requests' => env('PAYMENT_LOG_REQUESTS', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'enabled' => env('PAYMENT_WEBHOOKS_ENABLED', true),
        'url' => env('PAYMENT_WEBHOOK_URL'),
        'secret' => env('PAYMENT_WEBHOOK_SECRET'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Antifraude
    |--------------------------------------------------------------------------
    */
    'antifraud' => [
        'enabled' => env('PAYMENT_ANTIFRAUD_ENABLED', false),
        'min_score' => env('PAYMENT_ANTIFRAUD_MIN_SCORE', 70),
    ],
    
];
```

---

## 🔐 Variáveis de Ambiente

### .env (Desenvolvimento)

```env
# Aplicação
APP_ENV=development
APP_DEBUG=true

# Gateway Padrão
PAYMENT_GATEWAY=fake

# Fake Bank (para testes)
PAYMENT_FAKE_ENABLED=true

# Stripe (desabilitado em dev)
PAYMENT_STRIPE_ENABLED=false
STRIPE_API_KEY=sk_test_xxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxx
STRIPE_SANDBOX=true

# PagarMe (desabilitado em dev)
PAYMENT_PAGARME_ENABLED=false
PAGARME_API_KEY=ak_test_xxxxxxxxxxxxxxxx
PAGARME_SANDBOX=true

# Globais
PAYMENT_CURRENCY=BRL
PAYMENT_TIMEOUT=30
PAYMENT_LOG_REQUESTS=true

# Webhooks
PAYMENT_WEBHOOKS_ENABLED=true
PAYMENT_WEBHOOK_URL=https://seusite.com/webhooks/payment
PAYMENT_WEBHOOK_SECRET=seu-secret-aqui

# Antifraude
PAYMENT_ANTIFRAUD_ENABLED=false
PAYMENT_ANTIFRAUD_MIN_SCORE=70
```

### .env (Produção)

```env
# Aplicação
APP_ENV=production
APP_DEBUG=false

# Gateway Padrão
PAYMENT_GATEWAY=stripe

# Fake Bank (desabilitado!)
PAYMENT_FAKE_ENABLED=false

# Stripe (habilitado)
PAYMENT_STRIPE_ENABLED=true
STRIPE_API_KEY=sk_live_xxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxx
STRIPE_SANDBOX=false

# PagarMe (backup)
PAYMENT_PAGARME_ENABLED=true
PAGARME_API_KEY=ak_live_xxxxxxxxxxxxxxxx
PAGARME_SANDBOX=false

# Globais
PAYMENT_CURRENCY=BRL
PAYMENT_TIMEOUT=60
PAYMENT_LOG_REQUESTS=true

# Webhooks
PAYMENT_WEBHOOKS_ENABLED=true
PAYMENT_WEBHOOK_URL=https://seusite.com/webhooks/payment
PAYMENT_WEBHOOK_SECRET=SENHA-SUPER-SEGURA-AQUI

# Antifraude
PAYMENT_ANTIFRAUD_ENABLED=true
PAYMENT_ANTIFRAUD_MIN_SCORE=80
```

### .env.example

```env
# Aplicação
APP_ENV=development
APP_DEBUG=true

# Gateway Padrão
PAYMENT_GATEWAY=fake

# Stripe
PAYMENT_STRIPE_ENABLED=false
STRIPE_API_KEY=
STRIPE_WEBHOOK_SECRET=
STRIPE_SANDBOX=true

# PagarMe
PAYMENT_PAGARME_ENABLED=false
PAGARME_API_KEY=
PAGARME_SANDBOX=true

# Globais
PAYMENT_CURRENCY=BRL
PAYMENT_TIMEOUT=30

# Webhooks
PAYMENT_WEBHOOKS_ENABLED=true
PAYMENT_WEBHOOK_URL=
PAYMENT_WEBHOOK_SECRET=
```

---

## 🚀 Inicialização

### bootstrap/payment.php

```php
<?php

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Factories\PaymentHubFactory;

/**
 * Carrega configurações
 */
$config = require __DIR__ . '/../config/payment.php';

/**
 * Obtém gateway padrão
 */
$gatewayName = $config['default'];
$gatewayConfig = $config['gateways'][$gatewayName];

/**
 * Verifica se está habilitado
 */
if (!($gatewayConfig['enabled'] ?? true)) {
    throw new \RuntimeException("Gateway '{$gatewayName}' está desabilitado");
}

/**
 * Instancia gateway
 */
$gatewayClass = $gatewayConfig['class'];
unset($gatewayConfig['class'], $gatewayConfig['enabled']);

$gateway = new $gatewayClass(...array_values($gatewayConfig));

/**
 * Cria PaymentHub
 */
$hub = new PaymentHub($gateway);

return $hub;
```

### Uso

```php
// No seu código
$hub = require __DIR__ . '/bootstrap/payment.php';

$response = $hub->createPixPayment($request);
```

---

## 🏭 Service Provider (Laravel)

### app/Providers/PaymentServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;

class PaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PaymentHub::class, function ($app) {
            $gatewayName = config('payment.default');
            $gatewayConfig = config("payment.gateways.{$gatewayName}");
            
            if (!($gatewayConfig['enabled'] ?? true)) {
                throw new \RuntimeException("Gateway '{$gatewayName}' desabilitado");
            }
            
            $gatewayClass = $gatewayConfig['class'];
            unset($gatewayConfig['class'], $gatewayConfig['enabled']);
            
            $gateway = new $gatewayClass(...array_values($gatewayConfig));
            
            return new PaymentHub($gateway);
        });
    }
}
```

### config/app.php

```php
'providers' => [
    // ...
    App\Providers\PaymentServiceProvider::class,
],
```

### Uso

```php
// Controller
use IsraelNogueira\PaymentHub\PaymentHub;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentHub $hub
    ) {}
    
    public function pay()
    {
        $response = $this->hub->createPixPayment($request);
        // ...
    }
}
```

---

## 🔧 Configurações Avançadas

### Múltiplos Gateways Simultâneos

```php
// config/payment.php
return [
    'gateways' => [
        'stripe_brazil' => [
            'class' => StripeGateway::class,
            'api_key' => env('STRIPE_BR_API_KEY'),
            'country' => 'BR',
        ],
        'stripe_usa' => [
            'class' => StripeGateway::class,
            'api_key' => env('STRIPE_US_API_KEY'),
            'country' => 'US',
        ],
    ],
];

// Uso
$hubBR = new PaymentHub(new StripeGateway(
    config('payment.gateways.stripe_brazil.api_key'),
    config('payment.gateways.stripe_brazil.country')
));

$hubUS = new PaymentHub(new StripeGateway(
    config('payment.gateways.stripe_usa.api_key'),
    config('payment.gateways.stripe_usa.country')
));
```

### Gateway Dinâmico

```php
class PaymentService
{
    public function getHub(string $gatewayName): PaymentHub
    {
        $config = config("payment.gateways.{$gatewayName}");
        
        if (!$config) {
            throw new \InvalidArgumentException("Gateway '{$gatewayName}' não configurado");
        }
        
        $gatewayClass = $config['class'];
        unset($config['class'], $config['enabled']);
        
        $gateway = new $gatewayClass(...array_values($config));
        
        return new PaymentHub($gateway);
    }
}

// Uso
$service = new PaymentService();

$hubStripe = $service->getHub('stripe');
$hubPagarMe = $service->getHub('pagarme');
```

### Fallback Automático

```php
class PaymentService
{
    public function processPayment($request)
    {
        $gateways = ['stripe', 'pagarme', 'mercadopago'];
        
        foreach ($gateways as $gatewayName) {
            try {
                $hub = $this->getHub($gatewayName);
                return $hub->createPixPayment($request);
                
            } catch (GatewayException $e) {
                // Tenta próximo gateway
                Log::warning("Gateway {$gatewayName} falhou", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        throw new \RuntimeException('Todos os gateways falharam');
    }
}
```

---

## 🔐 Segurança

### Nunca Commite Credenciais

```bash
# .gitignore
.env
.env.backup
.env.production
*.key
```

### Rotação de Chaves

```php
// config/payment.php
'stripe' => [
    'api_key' => env('STRIPE_API_KEY_' . date('Ym')), // Chave mensal
    'rotation_day' => 1, // Dia da rotação
],
```

### Criptografia

```php
// Criptografe chaves sensíveis
'stripe' => [
    'api_key' => decrypt(env('STRIPE_API_KEY_ENCRYPTED')),
],
```

---

## 📊 Monitoramento

### Log de Requisições

```php
class LoggableGateway implements PaymentGatewayInterface
{
    public function __construct(
        private PaymentGatewayInterface $gateway
    ) {}
    
    public function createPixPayment(PixPaymentRequest $request): PaymentResponse
    {
        Log::info('PIX payment request', [
            'amount' => $request->amount,
            'customer' => $request->customerEmail,
        ]);
        
        try {
            $response = $this->gateway->createPixPayment($request);
            
            Log::info('PIX payment response', [
                'transaction_id' => $response->transactionId,
                'status' => $response->status->value,
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('PIX payment error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

// Uso
$gateway = new LoggableGateway(new StripeGateway($key));
$hub = new PaymentHub($gateway);
```

---

## 🧪 Ambientes

### Development

```php
// .env
APP_ENV=development
PAYMENT_GATEWAY=fake
PAYMENT_LOG_REQUESTS=true
APP_DEBUG=true
```

### Staging

```php
// .env
APP_ENV=staging
PAYMENT_GATEWAY=stripe
STRIPE_SANDBOX=true
PAYMENT_LOG_REQUESTS=true
APP_DEBUG=true
```

### Production

```php
// .env
APP_ENV=production
PAYMENT_GATEWAY=stripe
STRIPE_SANDBOX=false
PAYMENT_LOG_REQUESTS=false
APP_DEBUG=false
```

---

## 🎯 Checklist de Produção

- [ ] `.env` no `.gitignore`
- [ ] Credenciais de produção configuradas
- [ ] Sandbox desabilitado
- [ ] Debug desabilitado
- [ ] Logs configurados
- [ ] Webhooks testados
- [ ] Fallback configurado
- [ ] Monitoramento ativo
- [ ] Backup de configurações
- [ ] Documentação atualizada

---

## 🆘 Troubleshooting

### Gateway não encontrado

```php
// Verifique se a classe existe
if (!class_exists($gatewayClass)) {
    throw new \RuntimeException("Classe {$gatewayClass} não existe");
}
```

### Credenciais inválidas

```php
// Valide antes de instanciar
if (empty($config['api_key'])) {
    throw new \RuntimeException('API Key não configurada');
}
```

### Ambiente errado

```php
// Verifique o ambiente
if (app()->environment('production') && $gateway->isSandbox()) {
    throw new \RuntimeException('Sandbox em produção!');
}
```

---

**Próximo:** [Aprenda sobre PIX](../guides/pix.md)
