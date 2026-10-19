
PHP       ?= php
COMPOSER  ?= composer
MKDOCS    ?= mkdocs
HOST      ?= 127.0.0.1
PORT      ?= 8000
DOCS_PORT ?= 8001

.DEFAULT_GOAL := help

.PHONY: help install serve test migrate migrate-status lint lint-fix audit docs-serve docs-build

# Ajuda gerada pelo próprio make ($(info)), sem depender de echo/grep/awk.
help:
	$(info Uso: make <alvo>)
	$(info )
	$(info Aplicação:)
	$(info $()  install         Instala as dependências PHP (composer install))
	$(info $()  serve           Inicia o servidor Laravel em http://$(HOST):$(PORT))
	$(info $()  test            Executa a suíte de testes (composer test))
	$(info $()  migrate         Executa as migrations pendentes)
	$(info $()  migrate-status  Mostra o status das migrations (somente leitura))
	$(info $()  lint            Verifica o estilo com Laravel Pint, sem alterar arquivos)
	$(info $()  lint-fix        Corrige automaticamente o estilo do código com Laravel Pint)
	$(info $()  audit           Audita vulnerabilidades nas dependências PHP (composer audit))
	$(info )
	$(info Documentação (requer o .venv do MkDocs ativado):)
	$(info $()  docs-serve      Inicia o servidor da documentação em http://$(HOST):$(DOCS_PORT))
	$(info $()  docs-build      Gera a documentação com validação estrita)
	@exit 0

install:
	$(COMPOSER) install

serve:
	$(PHP) artisan serve --host=$(HOST) --port=$(PORT)

test:
	$(COMPOSER) test

migrate:
	$(PHP) artisan migrate

migrate-status:
	$(PHP) artisan migrate:status

lint:
	$(PHP) vendor/bin/pint --test

lint-fix:
	$(PHP) vendor/bin/pint

audit:
	$(COMPOSER) audit

docs-serve:
	$(MKDOCS) serve -a $(HOST):$(DOCS_PORT)

docs-build:
	$(MKDOCS) build --strict
