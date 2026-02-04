# 🏦 Sub-contas

Gerencie múltiplos vendedores no seu marketplace.

---

## 🚀 Criar Sub-conta

```php
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubAccountRequest;

$request = new SubAccountRequest(
    name: 'Loja do João',
    email: 'joao@loja.com',
    document: '12.345.678/0001-00',
    bankAccount: [
        'bank_code' => '001',
        'branch' => '1234',
        'account' => '56789-0',
        'type' => 'checking'
    ]
);

$response = $hub->createSubAccount($request);
$subAccountId = $response->subAccountId;
```

---

## 🎯 Gerenciar

```php
// Atualizar
$hub->updateSubAccount($subAccountId, [
    'email' => 'novo@email.com'
]);

// Consultar
$account = $hub->getSubAccount($subAccountId);

// Ativar/Desativar
$hub->activateSubAccount($subAccountId);
$hub->deactivateSubAccount($subAccountId);
```

---

## 💡 Marketplace

```php
// Onboarding de vendedor
class SellerOnboarding
{
    public function register($sellerData)
    {
        $request = new SubAccountRequest(
            name: $sellerData['company_name'],
            email: $sellerData['email'],
            document: $sellerData['cnpj'],
            bankAccount: $sellerData['bank_account']
        );
        
        $response = $this->hub->createSubAccount($request);
        
        Seller::create([
            'sub_account_id' => $response->subAccountId,
            'name' => $sellerData['company_name'],
            'status' => 'active'
        ]);
    }
}
```
