# API

Base: `http://localhost:8000/api` (prefixo `/api` aplicado pelo Laravel às rotas de `routes/api.php`).

!!! danger "Sem autenticação"
    Nenhuma rota da API exige autenticação, e nenhuma regra de autorização é aplicada. Em `GET /groups` e `POST /groups`, o "usuário atual" é o usuário autenticado, se houver. Caso contrário, é usado o **primeiro usuário cadastrado** (`User::first()`).

## Convenções

- Envie `Content-Type: application/json` e `Accept: application/json`.
- As respostas são os models Eloquent serializados em JSON. As relações carregadas aparecem como chaves aninhadas (`owner`, `members`, `payer`, `splits` etc.). As relações *many-to-many* incluem o campo `pivot` padrão do Eloquent.
- Os campos `password` e `remember_token` de `User` são ocultos na serialização.

### Respostas de erro comuns

| Situação | Status | Corpo |
|---|---|---|
| Falha de validação (Form Request) | `422` | Formato padrão do Laravel: `message` e `errors` (objeto campo → lista de mensagens). |
| ID inexistente em `{group}`, `{expense}` ou `{user}` | `404` | JSON de erro padrão do Laravel (*route model binding*). |
| Erro de regra de negócio tratado no controller | `422` / `404` / `401` | `{"message": "..."}` (detalhado em cada endpoint). |

## Resumo dos endpoints

| Método | Rota | Controller | Descrição |
|---|---|---|---|
| GET | `/api/groups` | `GroupController@index` | Lista os grupos do usuário atual |
| POST | `/api/groups` | `GroupController@store` | Cria um grupo |
| GET | `/api/groups/{group}` | `GroupController@show` | Detalha um grupo |
| PUT | `/api/groups/{group}` | `GroupController@update` | Renomeia um grupo |
| DELETE | `/api/groups/{group}` | `GroupController@destroy` | Exclui um grupo |
| GET | `/api/groups/{group}/members` | `GroupMemberController@index` | Lista os participantes |
| POST | `/api/groups/{group}/members` | `GroupMemberController@store` | Adiciona um participante |
| DELETE | `/api/groups/{group}/members/{user}` | `GroupMemberController@destroy` | Remove um participante |
| GET | `/api/groups/{group}/expenses` | `ExpenseController@index` | Lista as despesas do grupo |
| POST | `/api/groups/{group}/expenses` | `ExpenseController@store` | Registra uma despesa |
| GET | `/api/groups/{group}/balances` | `GroupBalanceController@index` | Saldos do grupo |
| GET | `/api/groups/{group}/settlements` | `SettlementController@index` | Histórico de quitações do grupo |
| POST | `/api/groups/{group}/settlements` | `SettlementController@store` | Registra uma quitação entre dois participantes |
| GET | `/api/expenses/{expense}` | `ExpenseController@show` | Detalha uma despesa |
| DELETE | `/api/expenses/{expense}` | `ExpenseController@destroy` | Exclui uma despesa |

A lista corresponde à saída de `php artisan route:list --except-vendor`.

---

## Grupos

### `GET /api/groups`

Retorna os grupos em que o usuário atual é dono **ou** participante, ordenados do mais recente para o mais antigo.

- **200**: array de grupos, cada um com `owner` (`id`, `name`, `email`), `members` (`id`, `name`, `email`), `members_count` e `expenses_count`.
- **200** com `[]`: quando não existe nenhum usuário no banco.

### `POST /api/groups`

Cria um grupo tendo o usuário atual como dono e o adiciona automaticamente como participante (em uma transação).

| Campo | Regras (`StoreGroupRequest`) |
|---|---|
| `name` | obrigatório, string, 3–255 caracteres |

- **201**: grupo criado, com `owner` e `members`.
- **401**: `{"message": "Nenhum usuário autenticado para criar o grupo."}`, quando não há nenhum usuário no banco.
- **422**: erro de validação.

### `GET /api/groups/{group}`

- **200**: grupo com `owner`, `members` e `expenses` (cada despesa com `payer`).

### `PUT /api/groups/{group}`

| Campo | Regras (`UpdateGroupRequest`) |
|---|---|
| `name` | obrigatório, string, 3–255 caracteres |

- **200**: grupo atualizado, com `owner` e `members`.
- **422**: erro de validação.

### `DELETE /api/groups/{group}`

Exclui o grupo. Participantes, despesas e parcelas são removidos em cascata pelo banco.

- **200**: `{"message": "Grupo excluído com sucesso."}`

---

## Participantes

### `GET /api/groups/{group}/members`

- **200**: array de usuários (`id`, `name`, `email`, mais `pivot`).

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

- **422**: `{"message": "Este usuário já faz parte do grupo."}` ou erro de validação.

### `DELETE /api/groups/{group}/members/{user}`

- **200**: `{"message": "Participante removido do grupo com sucesso."}`
- **422**: `{"message": "O criador do grupo não pode ser removido."}`
- **404**: `{"message": "Este usuário não é participante deste grupo."}`

!!! note
    Remover um participante não altera as despesas nem as parcelas já registradas.

---

## Despesas

### `GET /api/groups/{group}/expenses`

- **200**: array de despesas do grupo, da mais recente para a mais antiga, cada uma com `payer` e `splits` (cada parcela com `user`).

### `POST /api/groups/{group}/expenses`

Registra uma despesa e a divide entre os participantes do grupo, de forma igualitária, por valores exatos ou por percentuais (veja [regras de divisão](arquitetura.md#divisao-de-despesa-createexpense)).

| Campo | Regras (`StoreExpenseRequest`) |
|---|---|
| `description` | obrigatório, string, 3–255 caracteres |
| `amount` | obrigatório, numérico, mínimo `0.01` |
| `paid_by` | obrigatório, inteiro, deve existir em `users.id` **e** ser participante do grupo |
| `split_type` | opcional: `equal` (padrão), `exact` ou `percentage` |
| `participants` | opcional, usado só em `equal`: lista de ids de usuários, todos participantes do grupo e sem repetição. Sem ela, a divisão é entre todos os participantes |
| `splits` | obrigatório em `exact` e `percentage`: lista com pelo menos uma parcela |
| `splits[].user_id` | obrigatório, inteiro, sem repetição, deve existir em `users.id` **e** ser participante do grupo |
| `splits[].amount` | obrigatório em `exact`, numérico, mínimo `0.01` |
| `splits[].percentage` | obrigatório em `percentage`, numérico, de `0.01` a `100` |

`participants` é ignorado em `exact` e `percentage`, e `splits` é ignorado em `equal`, mas os `user_id` de ambos são sempre conferidos contra o grupo.

Exemplos de corpo:

```json
{"description": "Jantar", "amount": 100, "paid_by": 1, "split_type": "equal", "participants": [2, 3]}
```

```json
{"description": "Jantar", "amount": 100, "paid_by": 1, "split_type": "exact",
 "splits": [{"user_id": 1, "amount": 70}, {"user_id": 2, "amount": 30}]}
```

```json
{"description": "Jantar", "amount": 100, "paid_by": 1, "split_type": "percentage",
 "splits": [{"user_id": 1, "percentage": 60}, {"user_id": 2, "percentage": 40}]}
```

- **201**: despesa criada, com `split_type`, `payer` e `splits`.
- **422**: erro de validação, com os campos em `errors` (por exemplo, `paid_by`: "O pagador deve ser um participante do grupo." ou `splits.1.user_id`: "O usuário da parcela deve pertencer ao grupo."), ou uma regra de negócio em `message`:
    - `{"message": "O grupo não possui participantes para divisão da despesa."}`
    - `{"message": "A soma das parcelas (R$ 90,00) é diferente do valor da despesa (R$ 100,00)."}` (`exact`)
    - `{"message": "A soma dos percentuais (99,00%) deve ser exatamente 100%."}` (`percentage`)

### `GET /api/expenses/{expense}`

- **200**: despesa com `group` (`id`, `name`), `payer` e `splits` (cada parcela com `user`).

### `PUT /api/expenses/{expense}`

Edita uma despesa já lançada. É uma **substituição completa**: o corpo tem os mesmos campos e regras do `POST /api/groups/{group}/expenses`, então `description`, `amount` e `paid_by` são obrigatórios e `split_type`, `participants` e `splits` seguem as regras de divisão. Se `split_type` não vier, a divisão volta para `equal`.

O grupo é o da própria despesa, e a divisão `equal` sem `participants` usa os participantes **atuais** do grupo. A despesa e todas as suas parcelas são atualizadas em uma única transação: as parcelas antigas são removidas e as novas gravadas, então os saldos refletem a edição imediatamente.

Só participantes do grupo da despesa podem editá-la.

- **200**: despesa atualizada, com `split_type`, `payer` e `splits`.
- **403**: o usuário não participa do grupo da despesa (a checagem vem antes da validação).
- **404**: despesa inexistente.
- **422**: os mesmos erros do `POST` (erros por campo ou `message` com a soma que não fecha). Nada é alterado.

### `DELETE /api/expenses/{expense}`

Remove as parcelas e a despesa (em uma transação).

- **200**: `{"message": "Despesa excluída com sucesso."}`

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
      ],
      "suggested_transfers": [
        {
          "from": { "id": 0, "name": "...", "email": "..." },
          "to": { "id": 0, "name": "...", "email": "..." },
          "amount": 0.0
        }
      ]
    }
    ```

    Os valores acima indicam apenas a estrutura e os tipos retornados por `ExpenseService::calculateBalance`. Não são uma resposta real.

    O `net_balance` já incorpora as quitações do grupo: cada acerto aumenta o saldo de quem pagou (`paid_by`) e diminui o de quem recebeu (`paid_to`).

    `suggested_transfers` é a sugestão mínima de PIX/transferências para zerar os saldos (veja [simplificação](arquitetura.md#simplificacao-de-dividas-debtsimplificationservicesuggesttransfers)). Membros `quitado` não aparecem; se todos estiverem quitados, a lista é `[]`.

---

## Quitações

### `GET /api/groups/{group}/settlements`

Lista o histórico de acertos do grupo, do mais recente para o mais antigo (`settled_at`, depois `id`).

- **200**: array de quitações, cada uma com `payer` e `payee` (`id`, `name`, `email`).

### `POST /api/groups/{group}/settlements`

Registra uma quitação (total ou parcial) entre dois participantes do mesmo grupo. O campo `settled_at` é preenchido automaticamente com a data/hora do registro.

| Campo | Regras (`StoreSettlementRequest`) |
|---|---|
| `payer_id` | obrigatório, inteiro, deve existir em `users.id`, ser participante do grupo e diferente de `payee_id` |
| `payee_id` | obrigatório, inteiro, deve existir em `users.id` e ser participante do grupo |
| `amount` | obrigatório, numérico, mínimo `0.01` |

No banco, `payer_id` é persistido como `paid_by` e `payee_id` como `paid_to`.

- **201**: quitação criada, com `payer` e `payee`.
- **422**: erro de validação (mesmo usuário nos dois lados, alguém fora do grupo, valor inválido etc.).

---

## Rotas web de usuário

Definidas em `routes/user.php` e incluídas por `routes/web.php`. **Não** ficam sob `/api`: usam o grupo de middleware `web` (sessão e verificação de token CSRF) e respondem com **redirecionamento**, não com JSON.

### `POST /user/register`

| Campo | Regras |
|---|---|
| `name` | obrigatório, string, máx. 255 |
| `email` | obrigatório, e-mail, máx. 255, único em `users` |
| `password` | obrigatório, string, mín. 8, `confirmed` (exige `password_confirmation`) |

Cria o usuário (senha com hash) e redireciona para `/`. Não autentica o usuário criado.

### `POST /user/login`

| Campo | Regras |
|---|---|
| `email` | obrigatório, e-mail |
| `password` | obrigatório, string |

Em caso de sucesso, autentica por sessão e redireciona para `/`. Em caso de falha, volta à página anterior com o erro `email`: "Email ou senha errado".
