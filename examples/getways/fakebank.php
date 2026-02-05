<?
use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBank\FakeBankGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\{
    PixPaymentRequest,
    CreditCardPaymentRequest,
    BoletoPaymentRequest,
    CustomerRequest,
    SubscriptionRequest,
    WalletRequest,
    SubAccountRequest,
    EscrowRequest,
    TransferRequest,
    RefundRequest,
    PaymentLinkRequest
};
use IsraelNogueira\PaymentHub\Enums\Currency;

echo "<pre>🏦 FAKEBANK - EXEMPLO COMPLETO\n";
echo str_repeat("=", 80) . "\n\n";

// Inicializar FakeBank
$hub = new PaymentHub(new FakeBankGateway());

// ==================== 1. CRIAR CLIENTE ====================
echo "📋 1. CRIANDO CLIENTE\n";
echo str_repeat("-", 80) . "\n";

$customerRequest = new CustomerRequest(
    name: 'João da Silva',
    email: 'joao@email.com',
    documentNumber: '05151138910',
    phone: '11999887766'
);

$customer = $hub->createCustomer($customerRequest);
echo "✅ Cliente criado: {$customer->customerId}\n";
echo "   Nome: {$customer->rawResponse['name']}\n";
echo "   Email: {$customer->rawResponse['email']}\n\n";

// ==================== 2. PAGAMENTO PIX ====================
echo "💰 2. PAGAMENTO PIX\n";
echo str_repeat("-", 80) . "\n";

$pixRequest = PixPaymentRequest::create(
    amount: 150.50,
    currency: Currency::BRL,
    description: 'Pagamento teste PIX',
    customerName: 'João da Silva',
    customerDocument: '05151138910',
    customerEmail: 'joao@email.com',
    expiresInMinutes: 30
);

$pixPayment = $hub->createPixPayment($pixRequest);
echo "✅ PIX criado: {$pixPayment->transactionId}\n";
echo "   Status: {$pixPayment->status->label()}\n";
echo "   Valor: " . $pixPayment->getFormattedAmount() . "\n";

$qrCode = $hub->getPixQrCode($pixPayment->transactionId);
$copyPaste = $hub->getPixCopyPaste($pixPayment->transactionId);
echo "   QR Code: " . substr($qrCode, 0, 50) . "...\n";
echo "   Copia e Cola: " . substr($copyPaste, 0, 40) . "...\n\n";

// ==================== 3. TOKENIZAR CARTÃO ====================
echo "💳 3. TOKENIZAÇÃO DE CARTÃO\n";
echo str_repeat("-", 80) . "\n";

$cardData = [
    'number' => '4111111111111111',
    'holder_name' => 'JOAO DA SILVA',
    'expiry_month' => '12',
    'expiry_year' => '2028',
    'cvv' => '123'
];

$token = $hub->tokenizeCard($cardData);
echo "✅ Token criado: {$token}\n";
echo "   Últimos 4 dígitos: 1111\n";
echo "   Bandeira: visa\n\n";

// ==================== 4. PAGAMENTO CARTÃO DE CRÉDITO ====================
echo "💳 4. PAGAMENTO CARTÃO DE CRÉDITO\n";
echo str_repeat("-", 80) . "\n";

$creditCardRequest = CreditCardPaymentRequest::create(
    amount: 500.00,
    currency: Currency::BRL,
    cardNumber: '5555555555554444',
    cardHolderName: 'JOAO DA SILVA',
    cardExpiryMonth: '12',
    cardExpiryYear: '2028',
    cardCvv: '123',
    installments: 3,
    capture: true,
    description: 'Compra parcelada em 3x',
    customerEmail: 'joao@email.com'
);

$creditPayment = $hub->createCreditCardPayment($creditCardRequest);
echo "✅ Pagamento aprovado: {$creditPayment->transactionId}\n";
echo "   Status: {$creditPayment->status->label()}\n";
echo "   Valor: " . $creditPayment->getFormattedAmount() . "\n";
echo "   Parcelas: {$creditPayment->rawResponse['installments']}x\n";
echo "   Bandeira: {$creditPayment->rawResponse['card_brand']}\n";
echo "   Últimos 4: {$creditPayment->rawResponse['card_last4']}\n\n";

// ==================== 5. BOLETO ====================
echo "🧾 5. CRIANDO BOLETO\n";
echo str_repeat("-", 80) . "\n";

$boletoRequest = BoletoPaymentRequest::create(
    amount: 299.90,
    currency: Currency::BRL,
    dueDate: date('Y-m-d', strtotime('+7 days')),
    description: 'Pagamento via boleto',
    customerName: 'João da Silva',
    customerDocument: '05151138910',
    customerEmail: 'joao@email.com',
    finePercentage: 2.0,
    interestPercentage: 1.0
);

$boleto = $hub->createBoleto($boletoRequest);
echo "✅ Boleto criado: {$boleto->transactionId}\n";
echo "   Status: {$boleto->status->label()}\n";
echo "   Vencimento: {$boleto->rawResponse['due_date']}\n";
echo "   Código de barras: {$boleto->rawResponse['barcode']}\n";

$boletoUrl = $hub->getBoletoUrl($boleto->transactionId);
echo "   URL: {$boletoUrl}\n\n";

// ==================== 6. CRIAR WALLET ====================
echo "👛 6. CRIANDO CARTEIRA (WALLET)\n";
echo str_repeat("-", 80) . "\n";

$walletRequest = new WalletRequest(
    customerId: $customer->customerId,
    currency: Currency::BRL->value
);

$wallet = $hub->createWallet($walletRequest);
echo "✅ Wallet criada: {$wallet->walletId}\n";
echo "   Saldo inicial: R$ " . number_format($wallet->balance, 2, ',', '.') . "\n\n";

// ==================== 7. ADICIONAR SALDO ====================
echo "💵 7. ADICIONANDO SALDO À WALLET\n";
echo str_repeat("-", 80) . "\n";

$walletAdd = $hub->addBalance($wallet->walletId, 1000.00);
echo "✅ Saldo adicionado\n";
echo "   Novo saldo: R$ " . number_format($walletAdd->balance, 2, ',', '.') . "\n\n";

// ==================== 8. CRIAR SEGUNDA WALLET ====================
echo "👛 8. CRIANDO SEGUNDA WALLET\n";
echo str_repeat("-", 80) . "\n";

$wallet2Request = new WalletRequest(
    customerId: 'FAKE_CUST_2',
    currency: Currency::BRL->value
);

$wallet2 = $hub->createWallet($wallet2Request);
echo "✅ Segunda wallet criada: {$wallet2->walletId}\n\n";

// ==================== 9. TRANSFERIR ENTRE WALLETS ====================
echo "↔️  9. TRANSFERÊNCIA ENTRE WALLETS\n";
echo str_repeat("-", 80) . "\n";

$transfer = $hub->transferBetweenWallets($wallet->walletId, $wallet2->walletId, 250.00);
echo "✅ Transferência realizada: {$transfer->transferId}\n";
echo "   De: {$transfer->rawResponse['from_wallet_id']}\n";
echo "   Para: {$transfer->rawResponse['to_wallet_id']}\n";
echo "   Valor: " . ($transfer->money ? $transfer->money->formatted() : 'N/A') . "\n";
echo "   Status: {$transfer->status->label()}\n\n";

// Verificar saldos
$balance1 = $hub->getWalletBalance($wallet->walletId);
$balance2 = $hub->getWalletBalance($wallet2->walletId);
echo "   Saldo Wallet 1: R$ " . number_format($balance1->balance, 2, ',', '.') . "\n";
echo "   Saldo Wallet 2: R$ " . number_format($balance2->balance, 2, ',', '.') . "\n\n";

// ==================== 10. CRIAR SUB-CONTA ====================
echo "🏢 10. CRIANDO SUB-CONTA (MARKETPLACE)\n";
echo str_repeat("-", 80) . "\n";

$subAccountRequest = new SubAccountRequest(
    name: 'Loja ABC',
    documentNumber: '12345678000190',
    email: 'loja@abc.com'
);

$subAccount = $hub->createSubAccount($subAccountRequest);
echo "✅ Sub-conta criada: {$subAccount->subAccountId}\n";
echo "   Nome: {$subAccount->rawResponse['name']}\n";
echo "   Status: {$subAccount->status}\n\n";

// ==================== 11. ESCROW (CUSTÓDIA) ====================
echo "🔒 11. ESCROW - CUSTÓDIA DE VALORES\n";
echo str_repeat("-", 80) . "\n";

$escrowRequest = EscrowRequest::create(
    amount: 500.00,
    currency: Currency::BRL
);

$escrow = $hub->holdInEscrow($escrowRequest);
echo "✅ Valor em custódia: {$escrow->escrowId}\n";
echo "   Valor: R$ " . number_format($escrow->amount, 2, ',', '.') . "\n";
echo "   Status: {$escrow->status}\n\n";

// Liberar parcialmente
$escrowRelease = $hub->partialReleaseEscrow($escrow->escrowId, 200.00);
echo "✅ Liberação parcial de R$ " . number_format($escrowRelease->amount, 2, ',', '.') . "\n";
echo "   Novo status: {$escrowRelease->status}\n\n";

// ==================== 12. ASSINATURA/RECORRÊNCIA ====================
echo "🔄 12. CRIANDO ASSINATURA\n";
echo str_repeat("-", 80) . "\n";

$subscriptionRequest = SubscriptionRequest::create(
    amount: 49.90,
    currency: Currency::BRL,
    interval: 'monthly',
    customerId: $customer->customerId,
    cardToken: $token,
    description: 'Plano Premium Mensal',
    trialDays: 7,
    cycles: 12
);

$subscription = $hub->createSubscription($subscriptionRequest);
echo "✅ Assinatura criada: {$subscription->subscriptionId}\n";
echo "   Valor: R$ " . number_format($subscription->rawResponse['amount'], 2, ',', '.') . "\n";
echo "   Intervalo: {$subscription->rawResponse['interval']}\n";
echo "   Trial: {$subscription->rawResponse['trial_days']} dias\n";
echo "   Status: {$subscription->status}\n\n";

// ==================== 13. LINK DE PAGAMENTO ====================
echo "🔗 13. CRIANDO LINK DE PAGAMENTO\n";
echo str_repeat("-", 80) . "\n";

$linkRequest = new PaymentLinkRequest(
    amount: 99.90,
    description: 'Produto XYZ'
);

$paymentLink = $hub->createPaymentLink($linkRequest);
echo "✅ Link criado: {$paymentLink->linkId}\n";
echo "   URL: {$paymentLink->url}\n";
echo "   Status: {$paymentLink->status}\n\n";

// ==================== 14. ESTORNO ====================
echo "↩️  14. ESTORNO DE PAGAMENTO\n";
echo str_repeat("-", 80) . "\n";

// Estorno TOTAL (sem passar amount)
$refundRequest = new RefundRequest(
    transactionId: $creditPayment->transactionId,
    reason: 'Cliente solicitou cancelamento'
);

// OU Estorno PARCIAL (passando amount)
// $refundRequest = new RefundRequest(
//     transactionId: $creditPayment->transactionId,
//     amount: 250.00,
//     reason: 'Estorno parcial de R$ 250,00'
// );

$refund = $hub->refund($refundRequest);
echo "✅ Estorno processado: {$refund->refundId}\n";
echo "   Transação original: {$refund->transactionId}\n";
echo "   Valor estornado: " . $refund->getFormattedAmount() . "\n";
echo "   Status: {$refund->status->label()}\n\n";

// ==================== 15. CONSULTAR STATUS ====================
echo "🔍 15. CONSULTANDO STATUS DE TRANSAÇÃO\n";
echo str_repeat("-", 80) . "\n";

$status = $hub->getTransactionStatus($pixPayment->transactionId);
echo "✅ Status consultado: {$status->transactionId}\n";
echo "   Status: {$status->status->label()}\n";
echo "   Valor: " . ($status->money ? $status->money->formatted() : 'N/A') . "\n\n";

// ==================== 16. LISTAR TRANSAÇÕES ====================
echo "📊 16. LISTANDO TRANSAÇÕES\n";
echo str_repeat("-", 80) . "\n";

$transactions = $hub->listTransactions();
echo "✅ Total de transações: " . count($transactions) . "\n\n";

foreach ($transactions as $i => $txn) {
    echo "   " . ($i + 1) . ". {$txn['id']}\n";
    echo "      Tipo: {$txn['type']}\n";
    echo "      Status: {$txn['status']}\n";
    echo "      Valor: R$ " . number_format($txn['amount'], 2, ',', '.') . "\n\n";
}

// ==================== 17. LISTAR CLIENTES ====================
echo "👥 17. LISTANDO CLIENTES\n";
echo str_repeat("-", 80) . "\n";

$customers = $hub->listCustomers();
echo "✅ Total de clientes: " . count($customers) . "\n\n";

foreach ($customers as $i => $cust) {
    echo "   " . ($i + 1) . ". {$cust['id']}\n";
    echo "      Nome: {$cust['name']}\n";
    echo "      Email: {$cust['email']}\n\n";
}

// ==================== 18. SALDO DA CONTA ====================
echo "💰 18. CONSULTANDO SALDO\n";
echo str_repeat("-", 80) . "\n";

$balance = $hub->getBalance();
echo "✅ Saldo total: R$ " . number_format($balance->balance, 2, ',', '.') . "\n";
echo "   Disponível: R$ " . number_format($balance->availableBalance, 2, ',', '.') . "\n";
echo "   Pendente: R$ " . number_format($balance->pendingBalance, 2, ',', '.') . "\n\n";

// ==================== 19. SUSPENDER E REATIVAR ASSINATURA ====================
echo "⏸️  19. GERENCIANDO ASSINATURA\n";
echo str_repeat("-", 80) . "\n";

$suspended = $hub->suspendSubscription($subscription->subscriptionId);
echo "✅ Assinatura suspensa: {$suspended->subscriptionId}\n";
echo "   Status: {$suspended->status}\n\n";

$reactivated = $hub->reactivateSubscription($subscription->subscriptionId);
echo "✅ Assinatura reativada: {$reactivated->subscriptionId}\n";
echo "   Status: {$reactivated->status}\n\n";

// ==================== 20. ATUALIZAR CLIENTE ====================
echo "✏️  20. ATUALIZANDO CLIENTE\n";
echo str_repeat("-", 80) . "\n";

$updatedCustomer = $hub->updateCustomer($customer->customerId, [
    'phone' => '11988776655',
    'name' => 'João da Silva Souza'
]);
echo "✅ Cliente atualizado: {$updatedCustomer->customerId}\n";
echo "   Novo nome: {$updatedCustomer->rawResponse['name']}\n";
echo "   Novo telefone: {$updatedCustomer->rawResponse['phone']}\n\n";

// ==================== RESUMO FINAL ====================
echo str_repeat("=", 80) . "\n";
echo "✅ RESUMO FINAL\n";
echo str_repeat("=", 80) . "\n\n";

echo "📋 Clientes criados: " . count($customers) . "\n";
echo "💳 Transações: " . count($transactions) . "\n";
echo "👛 Wallets: 2\n";
echo "🔄 Assinaturas: 1\n";
echo "🏢 Sub-contas: 1\n";
echo "🔒 Escrows: 1\n";
echo "🔗 Links de pagamento: 1\n";
echo "↩️  Estornos: 1\n\n";

echo "🎉 Exemplo completo executado com sucesso!\n";
echo "📁 Dados salvos em: storage/fakebank/*.json\n\n";


echo "</pre>";
