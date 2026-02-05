# 🤝 Contribuindo para o Payment Hub

Obrigado por considerar contribuir com o Payment Hub! 🎉

Este documento fornece diretrizes para contribuir com o projeto.

## 📋 Índice

- [Código de Conduta](#código-de-conduta)
- [Como Posso Contribuir?](#como-posso-contribuir)
- [Processo de Desenvolvimento](#processo-de-desenvolvimento)
- [Diretrizes de Código](#diretrizes-de-código)
- [Commits e Pull Requests](#commits-e-pull-requests)
- [Testes](#testes)

---

## 📜 Código de Conduta

Este projeto adere ao [Código de Conduta](../CODE_OF_CONDUCT.md). Ao participar, espera-se que você cumpra este código.

---

## 🚀 Como Posso Contribuir?

### 🐛 Reportar Bugs

Encontrou um bug? Ajude-nos criando uma issue:

1. **Verifique se já existe** uma issue sobre o problema
2. **Use o template** de issue para bugs
3. **Inclua detalhes**:
   - Versão do PHP
   - Versão do Payment Hub
   - Gateway utilizado
   - Passos para reproduzir
   - Comportamento esperado vs atual
   - Mensagens de erro (se houver)

### 💡 Sugerir Melhorias

Tem uma ideia? Compartilhe:

1. **Abra uma issue** com o label `enhancement`
2. **Descreva claramente** o problema que resolve
3. **Explique a solução** proposta
4. **Considere alternativas** que você pensou

### 🔧 Implementar Features

Quer implementar algo?

1. **Verifique as issues** com label `good first issue` ou `help wanted`
2. **Comente na issue** dizendo que vai trabalhar nela
3. **Siga o processo** descrito abaixo

### 📚 Melhorar Documentação

Documentação nunca é demais:

- Corrigir erros de digitação
- Melhorar explicações
- Adicionar exemplos
- Traduzir para outros idiomas

### 🌐 Adicionar Novo Gateway

Quer adicionar suporte a um novo gateway de pagamento?

1. **Leia** [docs/creating-gateway.md](creating-gateway.md)
2. **Implemente** a interface `PaymentGatewayInterface`
3. **Adicione testes** de integração
4. **Documente** em um arquivo `.md` no diretório do gateway

---

## 🛠️ Processo de Desenvolvimento

### 1. Fork e Clone

```bash
# Fork pelo GitHub, depois:
git clone https://github.com/SEU_USERNAME/payment-hub.git
cd payment-hub
```

### 2. Instale Dependências

```bash
composer install
```

### 3. Crie uma Branch

```bash
git checkout -b feature/minha-feature
# ou
git checkout -b fix/meu-bugfix
```

**Convenção de nomes:**
- `feature/` - Novas funcionalidades
- `fix/` - Correção de bugs
- `docs/` - Alterações na documentação
- `refactor/` - Refatoração de código
- `test/` - Adição/correção de testes

### 4. Faça Suas Alterações

Desenvolva sua feature/correção seguindo as diretrizes abaixo.

### 5. Rode os Testes

```bash
# Todos os testes
composer test

# Com cobertura
composer test:coverage

# Análise estática
composer analyse

# Verificação de estilo
composer cs
```

### 6. Commit e Push

```bash
git add .
git commit -m "Tipo: Descrição curta"
git push origin feature/minha-feature
```

### 7. Abra um Pull Request

No GitHub, abra um PR da sua branch para `main`.

---

## 💻 Diretrizes de Código

### Padrões PHP

- **PHP 8.3+** obrigatório
- **PSR-12** para estilo de código
- **PSR-4** para autoloading
- **Strict types** sempre:
  ```php
  <?php
  
  declare(strict_types=1);
  ```

### Type Hints

Use type hints em **tudo**:

```php
// ✅ BOM
public function createPayment(PaymentRequest $request): PaymentResponse
{
    // ...
}

// ❌ RUIM
public function createPayment($request)
{
    // ...
}
```

### Readonly Properties

Use `readonly` sempre que possível:

```php
// ✅ BOM
public function __construct(
    public readonly string $apiKey,
    public readonly bool $sandbox
) {}

// ❌ EVITE (a menos que precise modificar)
public function __construct(
    public string $apiKey,
    public bool $sandbox
) {}
```

### Enums

Use enums para valores fixos:

```php
// ✅ BOM
public function setStatus(PaymentStatus $status): void

// ❌ RUIM
public function setStatus(string $status): void
```

### ValueObjects

Valide dados com ValueObjects:

```php
// ✅ BOM
public function setCpf(CPF $cpf): void

// ❌ RUIM
public function setCpf(string $cpf): void
{
    if (!$this->isValidCpf($cpf)) {
        throw new InvalidArgumentException();
    }
}
```

### Documentação

Use PHPDoc:

```php
/**
 * Cria um pagamento PIX
 * 
 * @param PixPaymentRequest $request Dados do pagamento
 * @return PaymentResponse Resposta do gateway
 * @throws GatewayException Se houver erro na comunicação
 */
public function createPixPayment(PixPaymentRequest $request): PaymentResponse
{
    // ...
}
```

### Tratamento de Erros

Lance exceções específicas:

```php
// ✅ BOM
throw new InvalidAmountException('Amount must be positive');

// ❌ RUIM
throw new Exception('Error');
```

---

## 📝 Commits e Pull Requests

### Mensagens de Commit

Siga o padrão:

```
Tipo: Descrição curta (máx 50 caracteres)

Descrição detalhada (se necessário)
```

**Tipos:**
- `Add`: Nova funcionalidade
- `Fix`: Correção de bug
- `Docs`: Documentação
- `Refactor`: Refatoração
- `Test`: Testes
- `Chore`: Tarefas de manutenção

**Exemplos:**
```
Add: Suporte ao gateway Cielo
Fix: Validação incorreta de CPF
Docs: Atualiza README com novos gateways
Refactor: Simplifica validação de Money
Test: Adiciona testes para AsaasGateway
```

### Pull Requests

**Título:**
```
[Tipo] Descrição clara do que foi feito
```

**Descrição deve incluir:**
- 📝 **O que foi feito**
- 🎯 **Por que foi feito**
- 🧪 **Como testar**
- 📸 **Screenshots** (se aplicável)
- ✅ **Checklist**:
  - [ ] Testes adicionados/atualizados
  - [ ] Documentação atualizada
  - [ ] PHPStan passa
  - [ ] CS-Fixer passa
  - [ ] Testes passam

---

## 🧪 Testes

### Estrutura

```
Tests/
├── Unit/           # Testes unitários
├── Integration/    # Testes de integração
└── Fixtures/       # Dados de teste
```

### Escrever Testes

```php
<?php

namespace IsraelNogueira\PaymentHub\Tests\Unit;

use PHPUnit\Framework\TestCase;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;

class MoneyTest extends TestCase
{
    public function testCanCreateMoney(): void
    {
        $money = Money::from(100.00, Currency::BRL);
        
        $this->assertEquals(100.00, $money->amount());
        $this->assertEquals(Currency::BRL, $money->currency());
    }
    
    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidAmountException::class);
        
        Money::from(-50.00, Currency::BRL);
    }
}
```

### Rodar Testes

```bash
# Todos os testes
composer test

# Teste específico
vendor/bin/phpunit --filter MoneyTest

# Com cobertura
composer test:coverage
```

### Cobertura

Mantenha **> 80%** de cobertura de código.

---

## 🔍 Análise de Código

### PHPStan (Nível 8)

```bash
composer analyse
```

Deve passar **sem erros**.

### PHP CS Fixer

```bash
# Verificar estilo
composer cs

# Corrigir automaticamente
composer cs:fix
```

---

## ❓ Dúvidas?

- 📧 Email: contato@israelnogueira.com
- 💬 [GitHub Discussions](https://github.com/israel-nogueira/payment-hub/discussions)
- 🐛 [GitHub Issues](https://github.com/israel-nogueira/payment-hub/issues)

---

## 🙏 Agradecimentos

Obrigado por contribuir! Toda ajuda é muito bem-vinda! 🎉

