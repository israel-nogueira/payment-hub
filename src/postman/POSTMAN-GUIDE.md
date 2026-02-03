# 📮 PaymentHub - Guia de Importação Postman

## 🚀 Instalação Rápida

### Passo 1: Importar Collection
1. Abra o Postman
2. Clique em **"Import"** (canto superior esquerdo)
3. Arraste o arquivo `PaymentHub-Collection-Complete.json`
4. Clique em **"Import"**

### Passo 2: Importar Environments
1. Clique no ícone **Environments** (⚙️ canto superior direito)
2. Clique em **"Import"**
3. Importe os dois arquivos:
   - `PaymentHub-Environment-Dev.json`
   - `PaymentHub-Environment-Prod.json`

### Passo 3: Configurar
1. Selecione **"PaymentHub - Development"** no dropdown
2. Clique no ícone 👁️ ao lado
3. Edite:
   - `BASE_URL`: sua URL local (ex: http://localhost:8000/api/v1)
   - `API_KEY`: sua chave de API
   - `YOUR_WEBHOOK_URL`: use https://webhook.site para testes

---

## ✅ Testes Automáticos Inclusos

### O que os testes fazem:

#### Testes Globais (Todos os requests)
- ✅ Valida status code de sucesso
- ✅ Verifica se resposta é JSON
- ✅ Mede tempo de resposta (< 2s)
- ✅ Valida headers de segurança

#### Testes Específicos por Request
- ✅ Valida estrutura da resposta
- ✅ Salva IDs automaticamente (transaction_id, customer_id, etc)
- ✅ Gera dados dinâmicos (emails únicos, valores aleatórios)
- ✅ Valida status e valores de negócio

---

## 🔄 Workflows Automatizados

### Workflow 1: Criar Pagamento PIX Completo
```
1. Customers → Create Customer
   ✅ Salva CUSTOMER_ID automaticamente

2. PIX → Create PIX Payment
   ✅ Usa dados gerados dinamicamente
   ✅ Salva TRANSACTION_ID automaticamente

3. PIX → Get PIX QR Code
   ✅ Usa TRANSACTION_ID salvo

4. Transactions → Get Transaction Status
   ✅ Verifica status do pagamento
```

### Workflow 2: Pagamento com Cartão
```
1. Customers → Create Customer
   ✅ Salva CUSTOMER_ID

2. Credit Card → Tokenize Card
   ✅ Salva CARD_TOKEN automaticamente

3. Credit Card → Create Credit Card Payment
   ✅ Usa CARD_TOKEN e CUSTOMER_ID salvos
   ✅ Gera ano de expiração automaticamente

4. Transactions → Get Transaction Status
   ✅ Consulta status do pagamento
```

### Workflow 3: Testar Webhooks
```
1. Webhooks → Register Webhook
   ✅ Registra webhook com URL do webhook.site
   ✅ Salva WEBHOOK_ID

2. PIX → Create PIX Payment
   ✅ Simula pagamento

3. Verifique em webhook.site
   ✅ Veja o payload recebido em tempo real
```

---

## 📊 Visualizando Resultados dos Testes

### Durante a execução:
1. Clique em **"Send"** em qualquer request
2. Veja a aba **"Test Results"** na resposta
3. Testes passados = ✅ verde
4. Testes falhados = ❌ vermelho

### Executar Collection completa:
1. Clique com botão direito na Collection
2. **"Run collection"**
3. Configure quantas iterações quer
4. Clique em **"Run PaymentHub"**
5. Veja relatório completo com todos os testes

### Exportar Resultados:
1. Após rodar a collection
2. Clique em **"Export Results"**
3. Escolha formato (JSON ou HTML)
4. Use para relatórios ou CI/CD

---

## 🎯 Variáveis Automáticas

Estas variáveis são **automaticamente preenchidas** pelos testes:

| Variável | Preenchida Por | Usado Em |
|----------|----------------|----------|
| `TRANSACTION_ID` | Create PIX/Card Payment | Get Status, Refund |
| `CUSTOMER_ID` | Create Customer | Subscriptions, Wallets |
| `CARD_TOKEN` | Tokenize Card | Card Payments |
| `SUBSCRIPTION_ID` | Create Subscription | Cancel/Update |
| `WALLET_ID` | Create Wallet | Add/Deduct Balance |
| `RANDOM_EMAIL` | Auto-gerado | Create Customer |
| `RANDOM_AMOUNT` | Auto-gerado | Payments |
| `FUTURE_DATE` | Auto-calculado | Boleto, Links |

Você **NÃO precisa** preencher manualmente! 🎉

---

## 🔔 Testando Webhooks Locais

### Com ngrok (Recomendado):
```bash
# 1. Instale ngrok: https://ngrok.com/download

# 2. Exponha sua aplicação local
ngrok http 8000

# 3. Copie a URL HTTPS gerada
# Exemplo: https://abc123.ngrok.io

# 4. No Postman, atualize YOUR_WEBHOOK_URL:
https://abc123.ngrok.io/webhooks/payment-hub

# 5. Registre o webhook
# Execute: Webhooks → Register Webhook

# 6. Teste!
# Execute qualquer pagamento e veja chegar no seu código
```

### Com webhook.site (Testes rápidos):
```
# 1. Acesse: https://webhook.site

# 2. Copie sua URL única
# Exemplo: https://webhook.site/abc-123-def

# 3. Cole em YOUR_WEBHOOK_URL no environment

# 4. Registre o webhook

# 5. Faça pagamentos e veja em tempo real no navegador!
```

---

## 🐛 Troubleshooting

### Erro: "TRANSACTION_ID not set"
**Solução:** Execute primeiro "Create PIX Payment" ou "Create Credit Card Payment"

### Erro: "CUSTOMER_ID not set"
**Solução:** Execute primeiro "Create Customer"

### Erro: "CARD_TOKEN not set"
**Solução:** Execute primeiro "Tokenize Card"

### Testes falhando
**Verifique:**
1. Environment correto selecionado?
2. BASE_URL está correto?
3. API está rodando?
4. API_KEY está válida?

### Response vazio
**Verifique:**
1. Content-Type: application/json no header?
2. Body está em formato JSON válido?
3. API retorna JSON?

---

## 💡 Dicas Profissionais

### 1. Salvar Requests Personalizados
```
Botão direito no request → "Save As"
Crie variações para diferentes cenários
```

### 2. Duplicar Environment
```
Crie environments por gateway:
- PaymentHub - Stripe
- PaymentHub - PagarMe
- PaymentHub - MercadoPago
```

### 3. Usar Console do Postman
```
View → Show Postman Console (Ctrl+Alt+C)
Veja logs detalhados e debug
```

### 4. Compartilhar com Time
```
Botão direito na Collection → "Share"
Gere link público ou exporte JSON
```

### 5. Integrar com CI/CD
```bash
# Instale Newman (Postman CLI)
npm install -g newman

# Execute collection no terminal
newman run PaymentHub-Collection-Complete.json \
  -e PaymentHub-Environment-Dev.json \
  --reporters cli,json

# Use no GitHub Actions, GitLab CI, etc
```

---

## 📚 Recursos Adicionais

- [Documentação Postman](https://learning.postman.com/)
- [Webhook.site](https://webhook.site)
- [ngrok](https://ngrok.com)
- [Newman (CLI)](https://www.npmjs.com/package/newman)

---

## 🎉 Pronto para Usar!

Agora é só:
1. ✅ Importar os arquivos
2. ✅ Configurar BASE_URL e API_KEY
3. ✅ Clicar em "Send"
4. ✅ Ver os testes passando! 🚀

**Dúvidas?** Abra uma issue no GitHub!

---

**Criado com ❤️ por Israel Nogueira**
