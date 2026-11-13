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
| `Unit/GroupBalanceControllerTest.php` | `index` com service e `Gate` mockados: retorno 200, propagação de exceção e saldo não calculado quando a autorização falha |
| `Unit/GroupControllerTest.php` | `destroy` com `Gate` mockado: resposta de sucesso, propagação de exceção e grupo não excluído quando a autorização falha |
| `Unit/GroupPolicyTest.php` | `GroupPolicy::update` para dono e não dono |
| `Unit/StoreExpenseRequestTest.php` | Regras obrigatórias, valor mínimo e regras de divisão (`split_type`, `participants`, `splits`) de `StoreExpenseRequest` |
| `Unit/StoreGroupRequestTest.php` | Regras de `StoreGroupRequest` e falha com nome curto |
| `Unit/SettlementServiceTest.php` | Criação e listagem de quitações via `SettlementService` (banco em memória) |
| `Unit/DebtSimplificationServiceTest.php` | Simplificação de dívidas: cadeia colapsada, lista vazia e conservação de saldos |
| `Unit/RegisterRequestTest.php` | Regras e mensagens de `RegisterRequest`: e-mail duplicado e confirmação de senha |
| `Unit/LoginRequestTest.php` | Regras e mensagens de `LoginRequest` e o método `credentials()` |
| `Feature/AuthTest.php` | Endpoints `/api/auth/*`: cadastro, login (sucesso, 401 e limite de tentativas), `me` e revogação do token no logout |
| `Feature/GroupAuthorizationTest.php` | Permissões de anônimo, dono, participante e estranho em grupos, participantes, despesas, saldos e quitações: 401 sem token, 403 consistente, 403 antes do 422 e remoção do dono bloqueada com 422 |
| `Feature/ProtectedRoutesTest.php` | 401 em todas as rotas protegidas sem token ou com token inválido, e uso do usuário autenticado em `GET` e `POST /api/groups` |
| `Feature/ExpenseSplitTest.php` | `POST /api/groups/{group}/expenses` com banco em memória: divisão `equal` (todos e subconjunto, com resto de centavos), `exact` e `percentage` (soma correta e incorreta, sem gravar nada em caso de erro), usuários fora do grupo, repetidos ou com valores inválidos, e saldos refletindo a divisão personalizada |
| `Feature/ExpenseUpdateTest.php` | `PUT /api/expenses/{expense}` com banco em memória: atualização completa da despesa e das parcelas, troca entre `equal`, `exact` e `percentage`, saldos sem resíduos das parcelas antigas, 422 sem alterar nada (valor inválido, pagador ou usuários fora do grupo, soma que não fecha, corpo vazio), 403 para quem não participa do grupo (com prioridade sobre a validação) e 404 |
| `Feature/GroupMemberRemovalTest.php` | `DELETE /api/groups/{group}/members/{user}`: bloqueia remoção com saldo a pagar/a receber, bloqueia pagador de despesa ativa mesmo quitado, permite remoção com saldo zero e sem ser pagador, mantém 422 do criador e 404 de não-membro |

## Cobertura

As pipelines (veja [CI/CD e deploy](ci-cd.md)) geram cobertura com **PCOV**. Localmente, com PCOV ou Xdebug instalado:

```bash
php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text
```

## Limitações conhecidas

- **Cobertura de feature concentrada em alguns fluxos.** Autenticação, autorização, divisão personalizada (`ExpenseSplitTest`), quitações e simplificação são exercitadas via HTTP; os demais endpoints são testados apenas com testes unitários e mocks.
- **Regras centrais com cobertura parcial:** a divisão em centavos (`createExpense`) tem testes com banco de dados em `ExpenseSplitTest`, mas o cálculo de saldo (`calculateBalance`) só é exercitado de forma indireta.
