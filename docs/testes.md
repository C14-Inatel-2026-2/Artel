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
| `Unit/ExpenseServiceTest.php` | Exceção ao criar despesa em grupo sem participantes |
| `Unit/GroupBalanceControllerTest.php` | `index` com service mockado: retorno 200 e propagação de exceção |
| `Unit/GroupControllerTest.php` | `destroy`: resposta de sucesso e propagação de exceção |
| `Unit/GroupPolicyTest.php` | `GroupPolicy::update` para dono e não dono |
| `Unit/StoreExpenseRequestTest.php` | Regras obrigatórias e valor mínimo de `StoreExpenseRequest` |
| `Unit/StoreGroupRequestTest.php` | Regras de `StoreGroupRequest` e falha com nome curto |
| `Unit/RegisterRequestTest.php` | Regras e mensagens de `RegisterRequest`: e-mail duplicado e confirmação de senha |
| `Unit/LoginRequestTest.php` | Regras e mensagens de `LoginRequest` e o método `credentials()` |
| `Unit/ExampleTest.php` | Teste de exemplo do skeleton Laravel |
| `Feature/Auth/AuthTest.php` | Endpoints `/api/auth/*`: cadastro, login (sucesso, 401 e limite de tentativas), `me` e revogação do token no logout |
| `Feature/ProtectedRoutesTest.php` | 401 em todas as rotas protegidas sem token ou com token inválido, e uso do usuário autenticado em `GET` e `POST /api/groups` |
| `Feature/ExampleTest.php` | Teste de exemplo do skeleton: `GET /` retorna 200 |

## Cobertura

As pipelines (veja [CI/CD e deploy](ci-cd.md)) geram cobertura com **PCOV**. Localmente, com PCOV ou Xdebug instalado:

```bash
php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text
```

## Limitações conhecidas

- **Poucos testes de feature.** Só a autenticação e a proteção das rotas são exercitadas via HTTP. Os demais endpoints são testados apenas com testes unitários e mocks.
- **Regras centrais sem teste:** a divisão em centavos (`createExpense`) e o cálculo de saldo (`calculateBalance`) não têm testes com banco de dados.
- **`ExampleTest`** do skeleton Laravel ainda está presente nas duas suítes.
- **Autorização:** `GroupPolicyTest` testa a policy isoladamente, mas a policy não é aplicada nas rotas (veja [Visão geral](index.md#limitacoes-atuais)).
