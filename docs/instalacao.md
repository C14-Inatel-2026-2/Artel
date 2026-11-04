# Instalação e execução

## Pré-requisitos

| Ferramenta | Observação |
|---|---|
| PHP 8.4+ | Exigido por `composer.json` (`"php": "^8.4"`) e pela imagem Docker (`php:8.4-apache`). Extensão `pdo_sqlite` habilitada. |
| Composer | Gerenciador de dependências PHP. |
| Node.js + npm | Opcional para a API. Necessário apenas para compilar os assets do skeleton Laravel (Vite). |
| Python 3 | Opcional. Necessário apenas para a documentação (veja [Documentação (MkDocs)](documentacao.md)). |

!!! note "Mínimo PHP 8.4"
    O requisito mínimo é **PHP 8.4**. O `composer.lock` atual não instala em 8.3 (dependências Symfony exigem ≥ 8.4.1). A decisão está registrada em [Decisões arquiteturais](decisoes.md#confirmadas).

## Configuração do ambiente

```bash
# 1. Instalar dependências PHP
composer install

# 2. Criar o arquivo de ambiente
cp .env.example .env

# 3. Gerar a chave da aplicação
php artisan key:generate
```

O `.env.example` já vem configurado com `DB_CONNECTION=sqlite`. Sessão, cache e fila também usam o banco (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`); as tabelas necessárias são criadas pelas migrations.

## Banco de dados (SQLite)

Sem `DB_DATABASE` definido, o Laravel usa o arquivo `database/database.sqlite` (`config/database.php`). Esse arquivo **não é versionado** (`database/.gitignore` ignora `*.sqlite*`), então é preciso criá-lo em um clone novo:

```bash
touch database/database.sqlite
php artisan migrate
```

Tabelas criadas pelas migrations da aplicação: `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `groups`, `group_members`, `expenses` e `expense_splits`. O modelo de dados está descrito em [Arquitetura](arquitetura.md#banco-de-dados).

!!! info "Sem dados de exemplo"
    Existe `database/seeders/DatabaseSeeder.php` e `database/factories/UserFactory.php`, mas ainda não há seeder de demonstração com grupos e despesas.

## Executar a aplicação

```bash
php artisan serve
```

A aplicação fica disponível em `http://localhost:8000` e a API sob o prefixo `/api` (por exemplo, `http://localhost:8000/api/groups`). Há também uma rota de health check em `/up`.

Para listar as rotas registradas:

```bash
php artisan route:list --except-vendor
```

## Script de setup do Composer

O `composer.json` define um script que executa a configuração completa, incluindo a instalação e o build dos assets front-end:

```bash
composer setup
```

Ele executa `composer install`, copia `.env.example` para `.env` (se não existir), `php artisan key:generate`, `php artisan migrate --force`, `npm install --ignore-scripts` e `npm run build`. Por depender do npm, exige Node.js instalado.

## Facilitador de linha de comando (Makefile)

Para agilizar tarefas recorrentes de desenvolvimento (como executar o servidor, rodar migrações e testes), o projeto disponibiliza um `Makefile` na raiz. Consulte o guia completo em [Como rodar o projeto / Comandos Úteis](comandos.md).

## Deploy no Fly.io

A API de demonstração está publicada em **https://artel-c14.fly.dev** (região `gru`). Health check: `GET /up`. Exemplo de API: `GET /api/groups`.

!!! warning "API sem autenticação"
    Em produção pública a API **ainda não exige login**. Controllers usam `User::first()` quando não há usuário autenticado (veja [Decisões — P1](decisoes.md#pendentes)). Não use dados reais nesse ambiente.

### Pré-requisitos

- Conta no [Fly.io](https://fly.io) e CLI `flyctl` (`curl -L https://fly.io/install.sh | sh`)
- Login: `fly auth login`
- App e volume já criados para este projeto: app `artel-c14`, volume `artel_data` montado em `/data`

### Redeploy

Na raiz do repositório (com `fly.toml`):

```bash
fly deploy --app artel-c14
```

Secrets necessários (já configurados no app; regenerar só se necessário):

```bash
fly secrets set APP_KEY="$(php artisan key:generate --show)" APP_URL="https://artel-c14.fly.dev" --app artel-c14
```

O container sobe com [`docker/entrypoint.sh`](../docker/entrypoint.sh): ajusta a porta (`PORT=8080`), garante o SQLite em `/data/database.sqlite`, roda `php artisan migrate --force` e inicia o Apache.

Configuração versionada: [`fly.toml`](../fly.toml), [`Dockerfile`](../Dockerfile). Compose local usa o mesmo entrypoint e o volume em `/data` (porta host `8000` → container `8080`).

### GitHub Actions (status no commit)

Cada push/PR em `main` ou `dev` dispara [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) (Build, Pint, Audit, testes) e aparece o check no GitHub. Push na `main` também tenta Deploy no Fly se o secret **`FLY_API_TOKEN`** estiver cadastrado.

### Pipeline Jenkins (Deploy e Smoke Test)

Na `main`, após Build → Quality → Testes → Package, o stage **Deploy e Smoke Test** publica no Fly (`fly deploy`) e valida `GET /up` (issue [#28](https://github.com/C14-Inatel-2026-2/Artel/issues/28)).

No Jenkins, cadastre a credencial secreta **`fly-api-token`** (Secret text) com um token Fly:

```bash
fly tokens create deploy -x 999999h -a artel-c14
```

Cole o valor gerado na credencial. Sem ela, o stage de Deploy na `main` falha. Branches que não sejam `main` só geram o pacote (não publicam).

Detalhes do servidor local: [ci/jenkins/README.md](../ci/jenkins/README.md).

### Pausar para não gastar (entre demos)

Dá para desligar o host sem afetar Build/Quality/Testes/Package. **Atenção:** um novo build da `main` no Jenkins sobe o app de novo via Deploy.

Para zerar custo de máquina (CPU/RAM):

```bash
fly scale count 0 --app artel-c14 --yes
```

A URL `https://artel-c14.fly.dev` fica indisponível até religar. O **volume** `artel_data` (1 GB) continua existindo e pode gerar um custo mínimo de armazenamento (~US$ 0,15/mês). Para eliminar esse custo também, apague o volume ou o app (`fly apps destroy artel-c14`) — isso remove o SQLite da demo.

Para religar antes da apresentação (manual ou via pipeline na `main`):

```bash
fly deploy --app artel-c14
```

Confirme com `fly status --app artel-c14` e `curl -I https://artel-c14.fly.dev/up`.
