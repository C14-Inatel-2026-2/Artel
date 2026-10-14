# Decisões arquiteturais

Esta página registra as decisões **refletidas no código atual** e as questões ainda **pendentes**. Uma decisão pendente não deve ser tratada como definida até ser registrada aqui como confirmada.

## Confirmadas

| # | Decisão | Evidência no repositório |
|---|---|---|
| D1 | **PHP 8.3 + Laravel 13** como stack da aplicação | `composer.json` |
| D2 | **SQLite** como banco de dados padrão (em arquivo no desenvolvimento e em memória nos testes) | `.env.example`, `config/database.php`, `phpunit.xml` |
| D3 | **API REST com respostas JSON** sob `/api`, com exceções renderizadas como JSON para `api/*` | `routes/api.php`, `bootstrap/app.php` |
| D4 | **Validação em Form Requests**, com mensagens em português | `app/Http/Requests/` |
| D5 | **Regra de negócio de despesas em uma camada de serviço** (`ExpenseService`), injetada nos controllers | `app/Services/ExpenseService.php`, `ExpenseController`, `GroupBalanceController` |
| D6 | **Divisão igualitária com aritmética em centavos**: o resto é distribuído 1 centavo por participante, e as parcelas são persistidas em `expense_splits` | `ExpenseService::createExpense` |
| D7 | **Exclusão em cascata** via chaves estrangeiras | migrations em `database/migrations/2024_*` |
| D8 | **O criador do grupo é participante automático e não pode ser removido** | `GroupController::store`, `GroupMemberController::destroy` |
| D9 | **Testes com PHPUnit + Mockery** | `composer.json`, `phpunit.xml`, `tests/` |
| D10 | **Documentação técnica em MkDocs + Material**, com dependências Python isoladas em `requirements-docs.txt` | `mkdocs.yml`, `requirements-docs.txt` |

## Pendentes

| # | Questão | Situação atual |
|---|---|---|
| P1 | **Mecanismo de autenticação da API** | Não implementado. Os controllers usam `User::first()` como alternativa quando não há usuário autenticado. |
| P2 | **Aplicação da autorização** (`GroupPolicy`) | A policy existe e tem testes unitários, mas não é chamada em nenhum ponto. Os Form Requests autorizam tudo. |
| P3 | **Fluxo de cadastro/login**: rotas web com sessão ou endpoints de API | As rotas atuais estão no grupo `web` e redirecionam. |
| P4 | **Escopo de interface**: apenas API ou API com frontend | Existe apenas a view `welcome` do skeleton Laravel. |
| P5 | **Tipos de divisão além da igualitária** | Não implementado. |
| P6 | **Edição de despesas e recálculo de parcelas** | Não implementado. Também está indefinido o que fazer com as parcelas quando a composição do grupo muda. |
| P7 | **Integração contínua, cobertura de testes e deploy** | Não configurados no repositório. |
