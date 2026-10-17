# Instalação e execução

## Pré-requisitos

| Ferramenta | Observação |
|---|---|
| PHP 8.3+ | Exigido por `composer.json` (`"php": "^8.3"`). Extensão `pdo_sqlite` habilitada. |
| Composer | Gerenciador de dependências PHP. |
| Node.js + npm | Opcional para a API. Necessário apenas para compilar os assets do skeleton Laravel (Vite). |
| Python 3 | Opcional. Necessário apenas para a documentação (veja [Documentação (MkDocs)](documentacao.md)). |

!!! note "Divergência no README"
    O `README.md` cita "PHP 8.2+", mas o `composer.json` exige `^8.3`. Considere **PHP 8.3** como requisito mínimo.

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
