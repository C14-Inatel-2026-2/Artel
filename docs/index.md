# Visão geral

O **Artel** é uma aplicação de divisão de despesas em grupo, desenvolvida como projeto acadêmico de Engenharia de Software (Inatel). A aplicação expõe uma **API REST** que permite criar grupos, gerenciar participantes, registrar despesas e consultar o saldo de cada participante.

!!! warning "Projeto em desenvolvimento"
    Esta documentação descreve o estado **atual** do código. Itens marcados como *limitação* ou *pendente* ainda não estão resolvidos. Em especial, a API **não possui autenticação** no momento (veja [Limitações atuais](#limitacoes-atuais)).

## Funcionalidades implementadas

| Área | O que existe hoje |
|---|---|
| Grupos | Listar, criar, exibir, renomear e excluir grupos. O criador é adicionado automaticamente como participante. |
| Participantes | Listar, adicionar (por `email` ou `user_id`) e remover participantes. O criador do grupo não pode ser removido. |
| Despesas | Listar despesas do grupo, registrar, exibir e excluir despesas. |
| Divisão | Cada despesa é dividida **igualmente** entre todos os participantes do grupo no momento do registro, com tratamento de centavos. |
| Saldos | Cálculo de total pago, total devido, saldo líquido e status (`a_receber`, `a_pagar`, `quitado`) por participante. |
| Usuários | Rotas web de cadastro (`POST /user/register`) e login por sessão (`POST /user/login`). |

Detalhes de cada endpoint estão em [API](api.md).

## Limitações atuais

- **Sem autenticação na API:** as rotas de `routes/api.php` não usam middleware de autenticação. Quando não há usuário autenticado, `GroupController` usa o **primeiro usuário do banco** (`User::first()`) como usuário atual.
- **Sem autorização aplicada:** existe a classe `App\Policies\GroupPolicy`, mas ela não é chamada por nenhum controller ou Form Request. Todos os `authorize()` dos Form Requests retornam `true`.
- **Divisão apenas igualitária:** não há divisão por valores ou percentuais personalizados.
- **Sem edição de despesas:** não existe rota de atualização de despesa.
- **Cadastro/login fora da API:** as rotas de usuário estão no grupo `web` (sessão e proteção CSRF) e respondem com redirecionamento, não com JSON.

## Tecnologias

| Tecnologia | Versão declarada | Fonte |
|---|---|---|
| PHP | `^8.3` | `composer.json` |
| Laravel | `^13.17` | `composer.json` |
| SQLite | conexão padrão | `.env.example`, `config/database.php` |
| PHPUnit | `^12.5.12` | `composer.json` (dev) |
| Mockery | `^1.6` | `composer.json` (dev) |
| Laravel Pint | `^1.27` | `composer.json` (dev) |
| Vite / Tailwind CSS | `^8.0.0` / `^4.0.0` | `package.json` (assets do skeleton Laravel) |
| MkDocs + Material | ver `requirements-docs.txt` | Documentação |

## Mapa da documentação

- [Instalação e execução](instalacao.md)
- [Arquitetura](arquitetura.md)
- [API](api.md)
- [Testes](testes.md)
- [Decisões arquiteturais](decisoes.md)
- [Documentação (MkDocs)](documentacao.md)
