# 🛡️ ValueObjects - Validação Automática

Use ValueObjects para validar dados automaticamente.

## CPF
```php
use IsraelNogueira\PaymentHub\ValueObjects\CPF;
$cpf = CPF::fromString('123.456.789-00');
echo $cpf->formatted(); // 123.456.789-00
echo $cpf->masked();    // ***.456.789-00
```

## CNPJ
```php
$cnpj = CNPJ::fromString('12.345.678/0001-00');
```

## Email
```php
$email = Email::fromString('teste@email.com');
echo $email->domain(); // email.com
```

## CardNumber
```php
$card = CardNumber::fromString('4111 1111 1111 1111');
echo $card->brand();   // visa
echo $card->masked();  // ************1111
```

[Mais detalhes nos guias específicos]
