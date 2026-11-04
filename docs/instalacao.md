# Instalação e execução

## Pré-requisitos

| Ferramenta | Observação |
|---|---|
| PHP 8.4+ | Exigido por `composer.json` (`"php": "^8.4"`) e pela imagem Docker (`php:8.4-apache`). Extensão `pdo_sqlite` habilitada. |
| Composer | Gerenciador de dependências PHP. |
| Node.js + npm | Necessário para compilar os assets da interface web (Vite + Tailwind CSS). Não é necessário para usar apenas a API. |
| Python 3 | Opcional. Necessário apenas para a documentação (veja [Documentação (MkDocs)](documentacao.md)). |
| Docker + Docker Compose | Opcional. Alternativa à instalação local (veja [Docker](#docker)). |

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

## Docker

O `compose.yml` usa a mesma imagem do deploy (`Dockerfile` + `docker/entrypoint.sh`), com o SQLite em um volume montado em `/data`:

```bash
docker compose up -d --build
```

A aplicação fica em `http://localhost:8000` (porta do host `8000` → container `8080`). O entrypoint roda as migrations ao subir.

```bash
docker compose exec app php artisan migrate:status   # status das migrations
docker compose logs -f app                           # logs
docker compose down                                  # parar
```

!!! note "Testes fora do container"
    A imagem é de produção: instala as dependências com `--no-dev` e o `.dockerignore` exclui `tests/` e `phpunit.xml`. Rode os testes localmente (`make test`) ou pela pipeline.

## CI/CD e deploy

A pipeline Jenkins, o deploy no Fly.io e como pausar a demo estão em [CI/CD e deploy](ci-cd.md).
