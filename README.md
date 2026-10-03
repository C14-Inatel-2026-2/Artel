# Artel

Aplicação de divisão de despesas em grupo — projeto acadêmico de Engenharia de Software (Inatel).

## Requisitos

- PHP 8.2+
- Composer

## Setup

```bash
# 1. Instalar dependências
composer install

# 2. Copiar arquivo de ambiente
cp .env.example .env

# 3. Gerar chave da aplicação
php artisan key:generate

# 4. Rodar migrations
php artisan migrate

# 5. Subir o servidor de desenvolvimento
php artisan serve
```

A aplicação estará disponível em [http://localhost:8000](http://localhost:8000).

## Testes

```bash
php artisan test
```
