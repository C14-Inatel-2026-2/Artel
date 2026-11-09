# API

Base: `http://localhost:8000/api` (prefixo `/api` aplicado pelo Laravel às rotas de `routes/api.php`).

!!! info "Autenticação e autorização"
    Todas as rotas de grupos, participantes, despesas e saldos exigem um token válido (veja [Autenticação](#autenticacao)). O acesso a cada grupo é restrito ao dono e aos participantes, conforme a `GroupPolicy` (veja [Permissões](#permissoes)).

## Convenções

- Envie `Content-Type: application/json` e `Accept: application/json`.
- Nas rotas protegidas, envie o token obtido no cadastro ou login no header `Authorization: Bearer <token>`.
- As respostas são os models Eloquent serializados em JSON. As relações carregadas aparecem como chaves aninhadas (`owner`, `members`, `payer`, `splits` etc.). As relações *many-to-many* incluem o campo `pivot` padrão do Eloquent.
- Os campos `password` e `remember_token` de `User` são ocultos na serialização.

### Respostas de erro comuns

| Situação | Status | Corpo |
|---|---|---|
| Token ausente, inválido ou revogado em rota protegida | `401` | `{"message": "Não autenticado."}` (com ou sem `Accept: application/json`). |
| Usuário autenticado sem permissão sobre o grupo (veja [Permissões](#permissoes)) | `403` | `{"message": "Acesso negado."}` |
| Falha de validação (Form Request) | `422` | Formato padrão do Laravel: `message` e `errors` (objeto campo → lista de mensagens). |
| ID inexistente em `{group}`, `{expense}` ou `{user}` | `404` | JSON de erro padrão do Laravel (*route model binding*). |
| Erro de regra de negócio tratado no controller | `422` / `404` / `401` | `{"message": "..."}` (detalhado em cada endpoint). |

## Resumo dos endpoints

| Método | Rota | Controller | Descrição | Quem pode |
|---|---|---|---|---|
| POST | `/api/auth/register` | `Auth\AuthController@register` | Cadastra um usuário e retorna um token | Público |
| POST | `/api/auth/login` | `Auth\AuthController@login` | Autentica e retorna um token | Público |
| POST | `/api/auth/logout` | `Auth\AuthController@logout` | Revoga o token atual | Autenticado |
| GET | `/api/auth/me` | `Auth\AuthController@me` | Retorna o usuário autenticado | Autenticado |
| GET | `/api/groups` | `GroupController@index` | Lista os grupos do usuário atual | Autenticado |
| POST | `/api/groups` | `GroupController@store` | Cria um grupo | Autenticado |
| GET | `/api/groups/{group}` | `GroupController@show` | Detalha um grupo | Participante |
| PUT | `/api/groups/{group}` | `GroupController@update` | Renomeia um grupo | Dono |
| DELETE | `/api/groups/{group}` | `GroupController@destroy` | Exclui um grupo | Dono |
| GET | `/api/groups/{group}/members` | `GroupMemberController@index` | Lista os participantes | Participante |
| POST | `/api/groups/{group}/members` | `GroupMemberController@store` | Adiciona um participante | Participante |
| DELETE | `/api/groups/{group}/members/{user}` | `GroupMemberController@destroy` | Remove um participante | Dono, ou o próprio participante |
| GET | `/api/groups/{group}/expenses` | `ExpenseController@index` | Lista as despesas do grupo | Participante |
| POST | `/api/groups/{group}/expenses` | `ExpenseController@store` | Registra uma despesa | Participante |
| GET | `/api/groups/{group}/balances` | `GroupBalanceController@index` | Saldos do grupo | Participante |
| GET | `/api/expenses/{expense}` | `ExpenseController@show` | Detalha uma despesa | Participante do grupo da despesa |
| DELETE | `/api/expenses/{expense}` | `ExpenseController@destroy` | Exclui uma despesa | Participante do grupo da despesa |

A lista corresponde à saída de `php artisan route:list --except-vendor`. Exceto `register` e `login`, todas as rotas exigem autenticação (`auth:sanctum`). "Participante" inclui o dono do grupo.

## Permissões

As regras ficam em `App\Policies\GroupPolicy`:

| Regra | Quem é autorizado | Onde é aplicada |
|---|---|---|
| `view` | Dono ou participante | `GroupController@show`, `GroupMemberController@index` e `@destroy`, `ExpenseController@index`, `@show` e `@destroy`, `GroupBalanceController@index`, `StoreExpenseRequest` |
| `update` | Dono | `UpdateGroupRequest` |
| `delete` | Dono | `GroupController@destroy` |
| `addMember` | Dono ou participante | `AddGroupMemberRequest` |
| `removeMember` | Dono (remove qualquer participante) ou o próprio participante (sai do grupo) | `GroupMemberController@destroy` |

- Nos endpoints com Form Request, a autorização roda no `authorize()` do request, **antes** da validação. Por isso, quem não tem permissão recebe `403`, e não `422`, sem descobrir quais e-mails estão cadastrados nem quem participa do grupo.
- Ao remover um participante, a ordem das verificações é: participação no grupo (`403`), tentativa de remover o dono (`422`), regra `removeMember` (`403`) e, por fim, se o usuário é participante (`404`).
- Um ID inexistente continua resultando em `404`, porque o *route model binding* acontece antes da autorização.

---

## Autenticação

A autenticação usa tokens do **Laravel Sanctum**. Os tokens não expiram: ficam válidos até o logout.

Resposta de sucesso do cadastro e do login:

```json
{
  "message": "...",
  "user": { "id": 0, "name": "...", "email": "...", "email_verified_at": null, "created_at": "...", "updated_at": "..." },
  "token": "1|...",
  "token_type": "Bearer"
}
```

### `POST /api/auth/register`

| Campo | Regras (`RegisterRequest`) |
|---|---|
| `name` | obrigatório, string, máx. 255 |
| `email` | obrigatório, e-mail, máx. 255, único em `users` |
| `password` | obrigatório, string, mín. 8, `confirmed` (exige `password_confirmation`) |

- **201**: usuário criado, com `message` "Usuário cadastrado com sucesso." e o token.
- **422**: erro de validação (por exemplo, `email`: "Este e-mail já está cadastrado.").

### `POST /api/auth/login`

Limitado a **5 tentativas por minuto**.

| Campo | Regras (`LoginRequest`) |
|---|---|
| `email` | obrigatório, e-mail |
| `password` | obrigatório, string |
| `device_name` | opcional, string, máx. 255. Nome do token (padrão: `api`) |

- **200**: `message` "Login realizado com sucesso." e o token.
- **401**: `{"message": "E-mail ou senha inválidos."}`, para e-mail inexistente ou senha incorreta.
- **422**: erro de validação.
- **429**: limite de tentativas excedido.

### `POST /api/auth/logout`

Revoga apenas o token usado na requisição. Os demais tokens do usuário continuam válidos.

- **200**: `{"message": "Logout realizado com sucesso."}`
- **401**: `{"message": "Não autenticado."}`

### `GET /api/auth/me`

- **200**: o usuário autenticado (`id`, `name`, `email`, `email_verified_at`, `created_at`, `updated_at`).
- **401**: `{"message": "Não autenticado."}`

---

## Grupos

### `GET /api/groups`

Retorna os grupos em que o usuário autenticado é dono **ou** participante, ordenados do mais recente para o mais antigo.

- **200**: array de grupos, cada um com `owner` (`id`, `name`, `email`), `members` (`id`, `name`, `email`), `members_count` e `expenses_count`.

### `POST /api/groups`

Cria um grupo tendo o usuário autenticado como dono e o adiciona automaticamente como participante (em uma transação).

| Campo | Regras (`StoreGroupRequest`) |
|---|---|
| `name` | obrigatório, string, 3–255 caracteres |

- **201**: grupo criado, com `owner` e `members`.
- **422**: erro de validação.

### `GET /api/groups/{group}`

- **200**: grupo com `owner`, `members` e `expenses` (cada despesa com `payer`).
- **403**: o usuário não participa do grupo.

### `PUT /api/groups/{group}`

| Campo | Regras (`UpdateGroupRequest`) |
|---|---|
| `name` | obrigatório, string, 3–255 caracteres |

- **200**: grupo atualizado, com `owner` e `members`.
- **403**: o usuário não é o dono do grupo.
- **422**: erro de validação.

### `DELETE /api/groups/{group}`

Exclui o grupo. Participantes, despesas e parcelas são removidos em cascata pelo banco.

- **200**: `{"message": "Grupo excluído com sucesso."}`
- **403**: o usuário não é o dono do grupo.

---

## Participantes

### `GET /api/groups/{group}/members`

- **200**: array de usuários (`id`, `name`, `email`, mais `pivot`).
- **403**: o usuário não participa do grupo.

### `POST /api/groups/{group}/members`

Adiciona um usuário existente ao grupo. Informe `email` **ou** `user_id`. Se ambos forem enviados, `user_id` tem prioridade.

| Campo | Regras (`AddGroupMemberRequest`) |
|---|---|
| `email` | obrigatório se `user_id` ausente; e-mail válido; deve existir em `users.email` |
| `user_id` | obrigatório se `email` ausente; inteiro; deve existir em `users.id` |

- **201**:

    ```json
    {
      "message": "Participante adicionado com sucesso.",
      "user": { "id": 0, "name": "...", "email": "..." }
    }
    ```

- **403**: o usuário não participa do grupo.
- **422**: `{"message": "Este usuário já faz parte do grupo."}` ou erro de validação.

### `DELETE /api/groups/{group}/members/{user}`

- **200**: `{"message": "Participante removido do grupo com sucesso."}`
- **403**: o usuário não participa do grupo, ou é participante e tenta remover outra pessoa.
- **422**: `{"message": "O criador do grupo não pode ser removido."}`
- **404**: `{"message": "Este usuário não é participante deste grupo."}`

!!! note
    Remover um participante não altera as despesas nem as parcelas já registradas.

---

## Despesas

### `GET /api/groups/{group}/expenses`

- **200**: array de despesas do grupo, da mais recente para a mais antiga, cada uma com `payer` e `splits` (cada parcela com `user`).
- **403**: o usuário não participa do grupo.

### `POST /api/groups/{group}/expenses`

Registra uma despesa e a divide igualmente entre os participantes atuais do grupo (veja [regras de divisão](arquitetura.md#divisao-de-despesa-createexpense)).

| Campo | Regras (`StoreExpenseRequest`) |
|---|---|
| `description` | obrigatório, string, 3–255 caracteres |
| `amount` | obrigatório, numérico, mínimo `0.01` |
| `paid_by` | obrigatório, inteiro, deve existir em `users.id` **e** ser participante do grupo |

- **201**: despesa criada, com `payer` e `splits`.
- **403**: o usuário não participa do grupo.
- **422**: erro de validação (por exemplo, `paid_by`: "O pagador deve ser um participante do grupo.") ou `{"message": "O grupo não possui participantes para divisão da despesa."}`.

### `GET /api/expenses/{expense}`

- **200**: despesa com `group` (`id`, `name`), `payer` e `splits` (cada parcela com `user`).
- **403**: o usuário não participa do grupo da despesa.

### `DELETE /api/expenses/{expense}`

Remove as parcelas e a despesa (em uma transação).

- **200**: `{"message": "Despesa excluída com sucesso."}`
- **403**: o usuário não participa do grupo da despesa.

---

## Saldos

### `GET /api/groups/{group}/balances`

- **200**:

    ```json
    {
      "group_id": 0,
      "group_name": "...",
      "total_expenses": 0.0,
      "expenses_count": 0,
      "balances": [
        {
          "user": { "id": 0, "name": "...", "email": "..." },
          "total_paid": 0.0,
          "total_owed": 0.0,
          "net_balance": 0.0,
          "status": "a_receber | a_pagar | quitado"
        }
      ]
    }
    ```

    Os valores acima indicam apenas a estrutura e os tipos retornados por `ExpenseService::calculateBalance`. Não são uma resposta real.

- **403**: o usuário não participa do grupo.

---

## Rotas web de usuário

Definidas em `routes/user.php` e incluídas por `routes/web.php`. **Não** ficam sob `/api`: usam o grupo de middleware `web` (sessão e verificação de token CSRF) e respondem com **redirecionamento**, não com JSON.

As validações são as mesmas dos endpoints de [autenticação da API](#autenticacao), com os mesmos Form Requests.

### `POST /user/register`

Valida com `RegisterRequest`, cria o usuário (senha com hash) e redireciona para `/`. Não autentica o usuário criado.

### `POST /user/login`

Valida com `LoginRequest`. Em caso de sucesso, autentica por sessão e redireciona para `/`. Em caso de falha, volta à página anterior com o erro `email`: "Email ou senha errado".
