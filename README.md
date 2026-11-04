# Artel

Aplicação de divisão de despesas em grupo — projeto acadêmico de Engenharia de Software (Inatel).

## Demo

- URL: [https://artel-c14.fly.dev](https://artel-c14.fly.dev)
- Health: `GET /up`
- API (ex.): `GET /api/groups`

> **Atenção:** a API pública ainda **não tem autenticação**. Não use dados reais. Detalhes e redeploy: [docs/instalacao.md](docs/instalacao.md#deploy-no-flyio).

## Requisitos

- Docker
- Docker Compose
- PHP 8.4+
- Laravel 13.x
- Composer
- Node.js & npm

O PHP, Composer, Apache e SQLite são executados dentro do container.

## Executando com Docker

### Build e inicialização

```bash
docker compose build
docker compose up -d
```

A aplicação estará disponível em [http://localhost:8000](http://localhost:8000).

### Verificar os containers

```bash
docker compose ps
```

### Verificar as migrations

```bash
docker compose exec app php artisan migrate:status
```

### Testar a conexão com o banco

```bash
docker compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Banco conectado';"
```

### Executar os testes

```bash
docker compose exec app php artisan test
```

### Visualizar os logs

```bash
docker compose logs -f app
```

## Testes

Com Docker:

```bash
docker compose exec app php artisan test
```

