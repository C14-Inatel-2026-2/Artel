# Decisões arquiteturais

Esta página registra as decisões **refletidas no código atual** e as questões ainda **pendentes**. Uma decisão pendente não deve ser tratada como definida até ser registrada aqui como confirmada.

## Confirmadas

| # | Decisão | Evidência no repositório |
|---|---|---|
| D1 | **PHP 8.4 + Laravel 13** como stack da aplicação | `composer.json` (`"php": "^8.4"`), `Dockerfile` (`php:8.4-apache`). O `composer.lock` exige PHP ≥ 8.4.1 (ex.: `symfony/http-foundation` 8.x); por isso o mínimo deixou de ser 8.3. |
| D2 | **SQLite** como banco de dados padrão (em arquivo no desenvolvimento e em memória nos testes) | `.env.example`, `config/database.php`, `phpunit.xml` |
| D3 | **API REST com respostas JSON** sob `/api`, com exceções renderizadas como JSON para `api/*` | `routes/api.php`, `bootstrap/app.php` |
| D4 | **Validação em Form Requests**, com mensagens em português | `app/Http/Requests/` |
| D5 | **Regra de negócio de despesas em uma camada de serviço** (`ExpenseService`), injetada nos controllers | `app/Services/ExpenseService.php`, `ExpenseController`, `GroupBalanceController` |
| D6 | **Divisão de despesas em três tipos (`equal`, `exact` e `percentage`) com aritmética em centavos**: em `equal` o resto é distribuído 1 centavo por participante, em `percentage` ele vai para quem tem o maior resto, e as parcelas são persistidas em `expense_splits` | `ExpenseService::createExpense`, `StoreExpenseRequest`, coluna `expenses.split_type` |
| D7 | **Exclusão em cascata** via chaves estrangeiras | migrations em `database/migrations/2024_*` |
| D8 | **O criador do grupo é participante automático e não pode ser removido** | `GroupController::store`, `GroupMemberController::destroy` |
| D9 | **Testes com PHPUnit + Mockery** | `composer.json`, `phpunit.xml`, `tests/` |
| D10 | **Documentação técnica em MkDocs + Material**, com dependências Python isoladas em `requirements-docs.txt` | `mkdocs.yml`, `requirements-docs.txt` |
| D11 | **Demo hospedada no Fly.io** (Docker Apache + SQLite em volume), app `artel-c14` | `fly.toml`, `docker/entrypoint.sh`, `Dockerfile`. URL: `https://artel-c14.fly.dev`. |
| D12 | **Interface web em Blade + Tailwind CSS (Vite)**, servida pelo próprio Laravel junto com a API | `package.json`, `vite.config.js`. Planejada na US06 ([#42](https://github.com/C14-Inatel-2026-2/Artel/issues/42)); hoje existem o layout base e as telas de login e cadastro. |
| D13 | **Autenticação da API com tokens do Laravel Sanctum** (`Authorization: Bearer`), sem expiração, revogados no logout. Rotas de grupos, participantes, despesas, saldos e quitações exigem `auth:sanctum` e respondem `401` em JSON sem token | `config/sanctum.php`, `routes/api.php`, `Auth\AuthController`, `bootstrap/app.php`. Resolve as pendências P1 e P3. |
| D14 | **Cadastro e login disponíveis como endpoints JSON** (`/api/auth/*`), mantendo as rotas web (`/user/*`) com sessão. Ambos usam `RegisterRequest` e `LoginRequest` | `routes/api.php`, `routes/user.php`, `app/Http/Requests/` |
| D15 | **Autorização por grupo via `GroupPolicy`**: apenas dono e participantes acessam o grupo, suas despesas, saldos e quitações; renomear e excluir o grupo é exclusivo do dono. Respostas `403` com `{"message": "Acesso negado."}`. Nos endpoints com Form Request, a autorização fica no `authorize()` do request para rodar antes da validação | `app/Policies/GroupPolicy.php`, controllers de grupos, participantes, despesas, saldos e quitações, `UpdateGroupRequest`, `AddGroupMemberRequest`, `StoreExpenseRequest`, `StoreSettlementRequest`, `bootstrap/app.php`. Resolve a pendência P2. |
| D16 | **Edição de despesa por substituição completa** (`PUT /api/expenses/{expense}`), com recálculo das parcelas em transação; só participantes do grupo da despesa podem editar | `ExpenseService::updateExpense`, `UpdateExpenseRequest`, `ExpenseController::update` |
| D17 | **Remoção de participante bloqueada com saldo pendente ou como pagador de despesa ativa**: o `net_balance` deve ser exatamente zero e o membro não pode ser `paid_by` de nenhuma despesa do grupo; casos bloqueados respondem `422` com mensagem clara | `GroupMemberController::destroy`, `ExpenseService::netBalanceForMember` |

## Pendentes

| # | Questão | Situação atual |
|---|---|---|
| P6 | **Parcelas e composição do grupo** | A edição recalcula as parcelas com os participantes atuais, e as despesas não editadas não mudam quando alguém entra ou sai do grupo. A remoção de participantes com saldo ou como pagadores ativos já é bloqueada (D17). Falta definir se a edição de uma despesa `equal` sem `participants` deveria manter os participantes originais. |
| P7 | **Integração contínua, cobertura de testes e deploy automatizado** | Pipeline no `Jenkinsfile`: Build → Quality → Testes → Package → Deploy e Smoke Test (Fly, só `main`) (veja [CI/CD e deploy](ci-cd.md)). Falta registrar as evidências do Jenkins ([#29](https://github.com/C14-Inatel-2026-2/Artel/issues/29)). |
