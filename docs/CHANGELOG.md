# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2025-02-05

### 🎉 Lançamento Inicial

#### Adicionado
- ✅ Suporte para 10 gateways de pagamento
  - FakeBankGateway (desenvolvimento/testes)
  - Asaas
  - Pagar.me
  - EBANX
  - MercadoPago
  - PagSeguro
  - Adyen
  - Stripe
  - PayPal
  - EtherGlobalAssets

#### 💳 Métodos de Pagamento
- ✅ PIX com QR Code e Copia-e-Cola
- ✅ Cartão de Crédito (à vista e parcelado)
- ✅ Cartão de Débito
- ✅ Boleto Bancário
- ✅ Links de Pagamento

#### 💰 ValueObjects
- ✅ Money (previne valores negativos e erros de arredondamento)
- ✅ CPF com validação automática
- ✅ CNPJ com validação automática
- ✅ Email com validação
- ✅ CardNumber com validação Luhn

#### 🎯 Enums Type-Safe
- ✅ PaymentStatus (paid, pending, failed, etc)
- ✅ PaymentMethod (pix, credit_card, boleto, etc)
- ✅ Currency (BRL, USD, EUR, etc)
- ✅ SubscriptionInterval (daily, weekly, monthly, yearly)

#### 🔁 Funcionalidades Avançadas
- ✅ Assinaturas e Recorrência
- ✅ Split de Pagamento (marketplaces)
- ✅ Sub-contas Multi-tenant
- ✅ Wallets (carteiras digitais)
- ✅ Escrow (custódia de valores)
- ✅ Transferências e Saques
- ✅ Antecipação de Recebíveis
- ✅ Gestão de Clientes
- ✅ Refunds e Chargebacks
- ✅ Antifraude
- ✅ Webhooks
- ✅ Tokenização de Cartões
- ✅ Pre-autorização e Captura

#### 📚 Documentação
- ✅ Guia completo em português
- ✅ Exemplos práticos para cada gateway
- ✅ Documentação de cada funcionalidade
- ✅ Guia de migração entre gateways
- ✅ Coleção Postman

#### 🧪 Testes
- ✅ FakeBankGateway com todas as funcionalidades
- ✅ Testes unitários
- ✅ Testes de integração

#### 🎨 Características
- ✅ PHP 8.3+ com tipos estritos
- ✅ PSR-4 Autoloading
- ✅ Zero dependências externas (exceto psr/log)
- ✅ 100% Type-Safe
- ✅ Sistema de eventos
- ✅ Logging com PSR-3

---

## [Unreleased]

### 🚧 Em Desenvolvimento
- 🔜 Mais gateways brasileiros
- 🔜 Suporte a carteira digital (PicPay, Ame, etc)
- 🔜 Integração com nota fiscal
- 🔜 Dashboard de métricas

---

## Como Contribuir

Veja nosso [Guia de Contribuição](docs/contributing.md) para mais detalhes sobre como contribuir com o projeto.

## Versionamento

Este projeto usa [Semantic Versioning](https://semver.org/lang/pt-BR/):
- **MAJOR**: Mudanças incompatíveis na API
- **MINOR**: Nova funcionalidade compatível com versões anteriores
- **PATCH**: Correções de bugs compatíveis

---

[1.0.0]: https://github.com/israel-nogueira/payment-hub/releases/tag/v1.0.0
[Unreleased]: https://github.com/israel-nogueira/payment-hub/compare/v1.0.0...HEAD
