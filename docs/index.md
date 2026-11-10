# Visão geral

O **Artel** é uma aplicação de divisão de despesas em grupo, desenvolvida como projeto acadêmico de Engenharia de Software (Inatel). A aplicação expõe uma **API REST** que permite criar grupos, gerenciar participantes, registrar despesas e consultar o saldo de cada participante.

!!! warning "Projeto em desenvolvimento"
    Esta documentação descreve o estado **atual** do código. Itens marcados como *limitação* ou *pendente* ainda não estão resolvidos. Veja [Limitações atuais](#limitacoes-atuais).

## Funcionalidades implementadas

| Área | O que existe hoje |
|---|---|
| Grupos | Listar, criar, exibir, renomear e excluir grupos. O criador é adicionado automaticamente como participante. |
| Participantes | Listar, adicionar (por `email` ou `user_id`) e remover participantes. O criador do grupo não pode ser removido. |
| Despesas | Listar despesas do grupo, registrar, exibir e excluir despesas. |
| Divisão | Cada despesa pode ser dividida **igualmente** (entre todos os participantes ou só entre os escolhidos), por **valores exatos** ou por **percentuais**, sempre com tratamento de centavos: a soma das parcelas é igual ao valor da despesa. |
| Saldos | Cálculo de total pago, total devido, saldo líquido e status (`a_receber`, `a_pagar`, `quitado`) por participante. |
| Simplificação | Sugestão de transferências (`suggested_transfers`) que minimiza o número de PIX entre participantes com base nos saldos líquidos. |
| Quitações | Registro de acertos (total ou parcial) entre dois participantes e histórico por grupo; o saldo considera os acertos. |
| Autenticação | Cadastro, login, logout e consulta do usuário autenticado via API (`/api/auth/*`), com tokens do Laravel Sanctum. As demais rotas da API exigem token. |
| Autorização | Acesso a cada grupo, suas despesas, saldos e quitações restrito ao dono e aos participantes (`GroupPolicy`). Renomear e excluir o grupo é exclusivo do dono. |
| Usuários (web) | Telas e rotas web de cadastro (`/register`, `POST /user/register`) e login por sessão (`/login`, `POST /user/login`). |

Detalhes de cada endpoint estão em [API](api.md).

## Limitações atuais

- **Sem edição de despesas:** não existe rota de atualização de despesa.

## Tecnologias

| Tecnologia | Versão declarada | Fonte |
|---|---|---|
| PHP | `^8.4` | `composer.json`, `Dockerfile` |
| Laravel | `^13.0` | `composer.json` |
| Laravel Sanctum | `^4.3` | `composer.json` |
| SQLite | conexão padrão | `.env.example`, `config/database.php` |
| PHPUnit | `^12.5.12` | `composer.json` (dev) |
| Mockery | `^1.6` | `composer.json` (dev) |
| Laravel Pint | `^1.27` | `composer.json` (dev) |
| Vite / Tailwind CSS | `^8.0.0` / `^4.0.0` | `package.json` (interface web em Blade: layout base e telas de login e cadastro) |
| MkDocs + Material | ver `requirements-docs.txt` | Documentação |

## Mapa da documentação

- [Instalação e execução](instalacao.md)
- [Como rodar o projeto / Comandos Úteis](comandos.md)
- [Arquitetura](arquitetura.md)
- [API](api.md)
- [Testes](testes.md)
- [CI/CD e deploy](ci-cd.md)
- [Decisões arquiteturais](decisoes.md)
- [Documentação (MkDocs)](documentacao.md)
