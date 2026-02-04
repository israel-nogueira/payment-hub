# ⚡ Instalação Rápida

Configure o PaymentHub em menos de 5 minutos!

---

## 📋 Requisitos

Antes de começar, certifique-se de ter:

- ✅ **PHP 8.3** ou superior
- ✅ **Composer** instalado
- ✅ Extensões: `json`, `mbstring`, `openssl`

---

## 🚀 Instalação

### Via Composer (Recomendado)

```bash
composer require israel-nogueira/payment-hub
```

Pronto! É só isso mesmo! 🎉

---

## 🧪 Teste a Instalação

Crie um arquivo `test.php`:

```php
<?php

require 'vendor/autoload.php';

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;

// Instancia o PaymentHub com gateway fake
$hub = new PaymentHub(new FakeBankGateway());

// Testa se está funcionando
echo "✅ PaymentHub instalado com sucesso!\n";
echo "Gateway: " . get_class($hub->getGateway()) . "\n";
```

Execute:

```bash
php test.php
```

Se ver a mensagem de sucesso, tudo certo! 🎊

---

## 🏗️ Estrutura do Projeto

Recomendamos organizar assim:

```
seu-projeto/
├── vendor/              # Dependências (gerado pelo Composer)
├── src/
│   └── Payments/       # Suas classes de pagamento
├── config/
│   └── payment.php     # Configurações
├── .env                # Credenciais (NUNCA commitar!)
├── composer.json
└── composer.lock
```

---

## 🔧 Configuração Básica

### Arquivo de Configuração

Crie `config/payment.php`:

```php
<?php

return [
    // Gateway padrão
    'default' => env('PAYMENT_GATEWAY', 'fake'),
    
    // Gateways disponíveis
    'gateways' => [
        'fake' => [
            'class' => \IsraelNogueira\PaymentHub\Gateways\FakeBankGateway::class,
        ],
        
        // Adicione gateways reais aqui
        // 'stripe' => [
        //     'class' => \MeuProjeto\Gateways\StripeGateway::class,
        //     'api_key' => env('STRIPE_API_KEY'),
        //     'sandbox' => env('STRIPE_SANDBOX', true),
        // ],
    ],
];
```

### Arquivo .env

Crie `.env` na raiz:

```env
# Ambiente
APP_ENV=development

# Gateway padrão
PAYMENT_GATEWAY=fake

# Credenciais (exemplos)
# STRIPE_API_KEY=sk_test_xxxxx
# STRIPE_SANDBOX=true
# PAGARME_API_KEY=ak_test_xxxxx
```

**⚠️ IMPORTANTE:** Adicione `.env` no `.gitignore`!

```
# .gitignore
.env
vendor/
```

---

## 📦 Autoload

O Composer já configura o autoload automaticamente. Mas se quiser customizar:

```json
{
    "autoload": {
        "psr-4": {
            "MeuProjeto\\": "src/"
        }
    }
}
```

Depois rode:

```bash
composer dump-autoload
```

---

## 🔄 Atualização

Para atualizar para a versão mais recente:

```bash
composer update israel-nogueira/payment-hub
```

---

## 🐛 Problemas Comuns

### "Class not found"

**Solução:**
```bash
composer dump-autoload
```

### "PHP version not compatible"

**Solução:** Atualize o PHP para 8.3+

```bash
php -v  # Verifica versão atual
```

### "Extension missing"

**Solução:** Instale as extensões necessárias

```bash
# Ubuntu/Debian
sudo apt install php8.3-mbstring php8.3-json php8.3-curl

# MacOS (Homebrew)
brew install php@8.3
```

---

## ✅ Checklist Pós-Instalação

- [ ] PaymentHub instalado via Composer
- [ ] Arquivo de teste executado com sucesso
- [ ] Estrutura de diretórios criada
- [ ] Arquivo de configuração criado
- [ ] .env criado e no .gitignore
- [ ] Autoload funcionando

---

## 🎯 Próximos Passos

Agora que está tudo instalado:

1. [**Faça seu primeiro pagamento**](first-payment.md)
2. [**Entenda os conceitos básicos**](core-concepts.md)
3. [**Configure seu gateway real**](configuration.md)

---

## 💡 Dicas

### Desenvolvimento vs Produção

```php
// Desenvolvimento
$hub = new PaymentHub(new FakeBankGateway());

// Produção
$hub = new PaymentHub(new StripeGateway($apiKey));
```

### Use Variáveis de Ambiente

```php
$gateway = match(env('PAYMENT_GATEWAY')) {
    'fake' => new FakeBankGateway(),
    'stripe' => new StripeGateway(env('STRIPE_API_KEY')),
    'pagarme' => new PagarMeGateway(env('PAGARME_API_KEY')),
};

$hub = new PaymentHub($gateway);
```

---

**Está com dúvidas?** Consulte o [FAQ](../help/faq.md) ou abra uma [issue no GitHub](https://github.com/israel-nogueira/payment-hub/issues)!
