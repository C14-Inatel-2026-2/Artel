# Testes

A suíte usa **PHPUnit 12** (via `php artisan test`) e **Mockery** para mocks.

## Executar

```bash
php artisan test
```

Alternativa pelo script do Composer, que limpa o cache de configuração antes de executar:

```bash
composer test
```

Para executar apenas uma suíte ou um arquivo:

```bash
php artisan test --testsuite=Unit
php artisan test tests/Unit/ExpenseControllerTest.php
```

## Ambiente de teste

O `phpunit.xml` define as suítes `Unit` (`tests/Unit`) e `Feature` (`tests/Feature`) e sobrescreve as variáveis de ambiente, entre elas:

- `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:` (banco em memória; o `database/database.sqlite` não é usado);
- `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`;
- `BCRYPT_ROUNDS=4`.

## Suíte atual

| Arquivo | O que verifica |
|---|---|
| `Unit/AddGroupMemberRequestTest.php` | Regras de `AddGroupMemberRequest` e falha quando nenhum identificador é enviado |
| `Unit/ExpenseControllerTest.php` | `store` com `ExpenseService` mockado: 201 em sucesso e 422 em `InvalidArgumentException` |
| `Unit/ExpenseServiceTest.php` | Regras de `StoreExpenseRequest` e exceção ao criar despesa em grupo sem participantes |
| `Unit/GroupBalanceControllerTest.php` | `index` com service mockado: retorno 200 e propagação de exceção |
| `Unit/GroupControllerTest.php` | `destroy`: resposta de sucesso e propagação de exceção |
| `Unit/GroupPolicyTest.php` | `GroupPolicy::update` para dono e não dono |
| `Unit/StoreGroupRequestTest.php` | Regras de `StoreGroupRequest` e falha com nome curto |
| `Unit/ExampleTest.php` | Teste de exemplo do skeleton Laravel |
| `Feature/ExampleTest.php` | Teste de exemplo do skeleton: `GET /` retorna 200 |

Última execução local registrada para esta documentação: **16 testes, 41 asserções, todos passando**.

## Limitações conhecidas

- **Quase todos os testes são unitários com mocks.** Não há testes de feature que exercitem os endpoints da API via HTTP.
- **Regras centrais sem teste:** a divisão em centavos (`createExpense`) e o cálculo de saldo (`calculateBalance`) não têm testes com banco de dados.
- **`ExampleTest`** do skeleton Laravel ainda está presente nas duas suítes.
- **Organização:** `ExpenseServiceTest.php` contém um teste de `StoreExpenseRequest`.
- **Cobertura:** não há relatório de cobertura configurado. A geração exige um driver (Xdebug ou PCOV), e o projeto não define um.
- **Autorização:** `GroupPolicyTest` testa a policy isoladamente, mas a policy não é aplicada nas rotas (veja [Visão geral](index.md#limitacoes-atuais)).
