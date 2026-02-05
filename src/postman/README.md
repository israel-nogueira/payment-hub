# 📦 PaymentHub - Postman Collection Package

## 📋 Conteúdo do Pacote

Este pacote contém tudo que você precisa para testar o PaymentHub via Postman:

```
📦 Arquivos Inclusos:
├── 📄 PaymentHub-Collection-Complete.json    (Collection com testes automáticos)
├── 🌍 PaymentHub-Environment-Dev.json         (Environment de Desenvolvimento)
├── 🌍 PaymentHub-Environment-Prod.json        (Environment de Produção)
├── 📖 POSTMAN-GUIDE.md                        (Guia completo de uso)
└── 📖 README.md                                (Este arquivo)
```

---

## 🚀 Quick Start (3 minutos)

### 1️⃣ Importe no Postman
```
1. Abra Postman
2. Import → PaymentHub-Collection-Complete.json
3. Environments → Import → PaymentHub-Environment-Dev.json
```

### 2️⃣ Configure
```
1. Selecione environment "PaymentHub - Development"
2. Edite variáveis:
   - BASE_URL: http://localhost:8000/api/v1
   - API_KEY: sua-chave-aqui
```

### 3️⃣ Teste!
```
1. Abra: Customers → Create Customer
2. Clique em "Send"
3. ✅ Veja testes passando automaticamente!
```

---

## ✨ Features Incluídas

### 🧪 Testes Automáticos
- ✅ Validação de status codes
- ✅ Validação de estrutura JSON
- ✅ Validação de regras de negócio
- ✅ Medição de performance
- ✅ Salva IDs automaticamente

### 🤖 Dados Dinâmicos
- ✅ Emails únicos gerados automaticamente
- ✅ CPF/CNPJ aleatórios
- ✅ Valores randomizados
- ✅ Datas futuras calculadas
- ✅ Timestamps atualizados

### 🔗 Workflows Integrados
- ✅ Variáveis conectadas entre requests
- ✅ Fluxo PIX completo
- ✅ Fluxo Cartão com tokenização
- ✅ Fluxo Assinaturas
- ✅ Teste de Webhooks

---

## 📊 Requests Disponíveis

### Métodos de Pagamento
- **PIX**: Create Payment, Get QR Code, Get Copy/Paste
- **Cartão de Crédito**: Payment, Tokenization, Capture, Cancel
- **Boleto**: Create, Get URL, Cancel

### Gestão
- **Customers**: Create, Get, Update, List
- **Transactions**: Get Status, List
- **Subscriptions**: Create, Cancel, Suspend, Reactivate
- **Refunds**: Full, Partial

### Avançado
- **Wallets**: Create, Add Balance, Transfer
- **Escrow**: Hold, Release, Cancel
- **Split Payment**: Marketplace splits
- **Sub-Accounts**: Create, Manage
- **Payment Links**: Create, Expire
- **Webhooks**: Register, List, Delete
- **Balance**: Get Balance, Settlement Schedule

**Total**: 50+ requests prontos para uso! 🎉

---

## 🎯 Cenários de Teste Prontos

### Cenário 1: Pagamento PIX E2E
```
1. Create Customer          → Salva CUSTOMER_ID
2. Create PIX Payment       → Salva TRANSACTION_ID
3. Get PIX QR Code          → Usa TRANSACTION_ID
4. Get Transaction Status   → Verifica aprovação
```

### Cenário 2: Cartão de Crédito Parcelado
```
1. Tokenize Card           → Salva CARD_TOKEN
2. Create Customer         → Salva CUSTOMER_ID
3. Create Card Payment     → Usa token + customer
4. Get Transaction Status  → Confirma aprovação
```

### Cenário 3: Assinatura Recorrente
```
1. Create Customer         → Salva CUSTOMER_ID
2. Tokenize Card          → Salva CARD_TOKEN
3. Create Subscription    → Usa ambos, salva SUBSCRIPTION_ID
4. Get Transaction Status → Verifica cobrança inicial
```

### Cenário 4: Marketplace com Split
```
1. Create Sub-Account     → Seller 1
2. Create Sub-Account     → Seller 2
3. Create Split Payment   → Divide entre sellers
4. Get Balance            → Verifica saldos
```

---

## 🔔 Testando Webhooks

### Opção 1: webhook.site (Mais Fácil)
```bash
1. Vá em https://webhook.site
2. Copie sua URL única
3. Cole em YOUR_WEBHOOK_URL no environment
4. Execute "Register Webhook"
5. Faça um pagamento
6. Veja o webhook chegar em tempo real!
```

### Opção 2: ngrok (Para código local)
```bash
# Terminal 1: Rode sua API
php -S localhost:8000

# Terminal 2: Exponha com ngrok
ngrok http 8000

# Copie URL HTTPS do ngrok
# Atualize YOUR_WEBHOOK_URL
# Registre webhook e teste!
```

---

## 📈 Executando Testes em Massa

### Via Interface
```
1. Botão direito na Collection
2. "Run collection"
3. Escolha quantas iterações
4. "Run PaymentHub"
5. Veja relatório completo!
```

### Via CLI (Newman)
```bash
# Instale Newman
npm install -g newman

# Execute collection
newman run PaymentHub-Collection-Complete.json \
  -e PaymentHub-Environment-Dev.json \
  --reporters cli,html

# Gera relatório HTML automático!
```

### Integração CI/CD
```yaml
# Exemplo GitHub Actions
- name: Run API Tests
  run: |
    npm install -g newman
    newman run PaymentHub-Collection-Complete.json \
      -e PaymentHub-Environment-Dev.json \
      --reporters cli,json
```

---

## 🐛 Troubleshooting

### ❌ "Could not get response"
**Solução:**
- Verifique se API está rodando
- Confirme BASE_URL correto
- Teste com curl primeiro

### ❌ "401 Unauthorized"
**Solução:**
- Verifique API_KEY no environment
- Confirme formato do header Authorization
- Teste com API key válida

### ❌ "Variable not set"
**Solução:**
- Execute requests em ordem (workflows)
- Customer antes de Subscription
- Tokenize antes de usar token

### ❌ Testes falhando
**Solução:**
- Verifique estrutura da resposta da API
- Compare com exemplos esperados
- Ajuste testes se necessário

---

## 💡 Dicas Pro

### 1. Organize por Ambiente
Crie environments para cada gateway:
```
- PaymentHub - Stripe Dev
- PaymentHub - Stripe Prod
- PaymentHub - PagarMe Dev
- PaymentHub - MercadoPago Dev
```

### 2. Use Folders para Agrupar
Organize requests por funcionalidade:
```
📁 Setup (Customer, Tokenize)
📁 Payments (PIX, Card, Boleto)
📁 Recurring (Subscriptions)
📁 Admin (Balance, Webhooks)
```

### 3. Salve Variações
```
Botão direito → "Save As"
Crie: "PIX - High Value", "PIX - Low Value"
```

### 4. Compartilhe com Time
```
Gere workspace do Postman
Convide membros do time
Todos usam mesma collection atualizada
```

### 5. Export/Import para Backup
```
Export regularmente
Versione no Git junto com código
Mantenha histórico de mudanças
```

---

## 📚 Documentação Adicional

- 📖 [POSTMAN-GUIDE.md](./POSTMAN-GUIDE.md) - Guia completo de uso
- 🌐 [Postman Learning Center](https://learning.postman.com/)
- 🔔 [webhook.site](https://webhook.site) - Teste webhooks
- 🚀 [ngrok](https://ngrok.com) - Exponha localhost
- 📦 [Newman](https://www.npmjs.com/package/newman) - CLI do Postman

---

## 🤝 Contribuindo

Encontrou bugs ou quer melhorias?

1. Fork o repositório
2. Adicione/modifique requests ou testes
3. Exporte collection atualizada
4. Faça Pull Request!

---

## 📝 Changelog

### v1.0.0 (2026-02-03)
- ✨ Collection inicial com 50+ requests
- ✅ Testes automáticos integrados
- 🤖 Geração dinâmica de dados
- 🔗 Workflows E2E conectados
- 🌍 Environments Dev e Prod
- 📖 Documentação completa

---

## 📧 Suporte

**Autor:** Israel Nogueira  
**Email:** contato@israelnogueira.com  
**GitHub:** https://github.com/israel-nogueira/payment-hub

---

## 📄 Licença

MIT License - Use livremente!

---

## ⭐ Gostou?

Se este pacote te ajudou:
- ⭐ Dê uma estrela no GitHub
- 🐛 Reporte bugs encontrados
- 💡 Sugira melhorias
- 📢 Compartilhe com outros devs!

---

**Feito com ❤️ para facilitar sua vida de dev!** 🚀
