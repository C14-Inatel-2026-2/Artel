# Artel

Aplicação de divisão de despesas em grupo — projeto acadêmico de Engenharia de Software (Inatel).

## Demo

- URL: [https://artel-c14.fly.dev](https://artel-c14.fly.dev)
- Health: `GET /up`
- API (ex.): `GET /api/groups`

> **Atenção:** a API pública ainda **não tem autenticação**. Não use dados reais.
>
> Entre demos o app pode estar **pausado** (`fly scale count 0`) para não gerar custo de máquina. Um novo build da `main` no Jenkins volta a publicar no Fly. Veja [CI/CD e deploy](docs/ci-cd.md).

## Executando com Docker

Requisitos: Docker e Docker Compose. PHP, Composer, Apache e SQLite rodam dentro do container.

```bash
docker compose up -d --build
```

A aplicação estará disponível em [http://localhost:8000](http://localhost:8000).

```bash
docker compose ps                                     # status dos containers
docker compose exec app php artisan migrate:status    # migrations
docker compose logs -f app                            # logs
docker compose down                                   # parar
```

## Executando localmente

Requisitos: PHP 8.4+ (`pdo_sqlite`), Composer e, para a interface web, Node.js + npm.

```bash
composer setup   # dependências, .env, chave, migrations e build dos assets
make serve       # http://127.0.0.1:8000
```

## Testes e qualidade

```bash
make test    # PHPUnit
make lint    # Laravel Pint (somente leitura)
make audit   # vulnerabilidades nas dependências
```

A imagem Docker é de produção (sem dependências de desenvolvimento), então os testes rodam localmente ou na pipeline.

## CI/CD

A pipeline roda no Jenkins (`Jenkinsfile`): **Build → Quality → Testes e Cobertura → Package** e, na `main`, **Deploy e Smoke Test** no Fly.io. O servidor Jenkins local está em [`ci/jenkins/`](ci/jenkins/README.md).

## Refactorings

| Smell | Refactoring | Por quê | PR |
|---|---|---|---|
| Código duplicado: `$request->user() ?? User::first()` repetido em `GroupController::index` e `GroupController::store` | Extract Method (`resolveActingUser`) | Centraliza a regra de fallback de usuário em um único lugar, evitando que as duas rotas fiquem fora de sincronia caso a regra mude | _a preencher_ |
| Dead code: model `GroupMember` nunca era instanciado — a relação `group_members` já é manipulada via `Group::members()` (pivô `belongsToMany`) | Remove Dead Code | Remove uma classe sem uso real, que só confundia quem lia a arquitetura do projeto (documentada em `docs/arquitetura.md` como não utilizada) | _a preencher_ |

## Documentação

A documentação técnica fica em [`docs/`](docs/index.md) (MkDocs):

- [Instalação e execução](docs/instalacao.md)
- [Comandos úteis (Makefile)](docs/comandos.md)
- [Arquitetura](docs/arquitetura.md)
- [API](docs/api.md)
- [Testes](docs/testes.md)
- [CI/CD e deploy](docs/ci-cd.md)
- [Decisões arquiteturais](docs/decisoes.md)
