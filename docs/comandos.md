# Como rodar o projeto / Comandos Úteis

Para simplificar as operações comuns de desenvolvimento do Artel, o projeto conta com um facilitador de linha de comando baseado em **Makefile** na raiz do repositório.

Os atalhos padronizam tarefas de instalação, execução de servidor, migrações de banco de dados, testes e validações de código em ambiente local (sem Docker).

---

## Pré-requisitos

| Ferramenta | Finalidade | Observações |
|---|---|---|
| **GNU Make** | Executor dos comandos | Nativo em Linux e macOS. No Windows, pode ser usado via WSL, Git Bash ou instalado com `choco install make` / `winget install GnuWin32.Make`. |
| **PHP 8.3+** | Execução da aplicação | Extensão `pdo_sqlite` habilitada. |
| **Composer** | Gerenciador de dependências PHP | Necessário para instalação e execução dos scripts de teste. |
| **Python 3 (`.venv`)** | Documentação MkDocs | Opcional. Apenas para os comandos `docs-serve` e `docs-build` (veja [Documentação](documentacao.md)). |

---

## Resumo dos comandos

| Comando | Comando real executado | Descrição |
|---|---|---|
| `make` ou `make help` | *Função interna `$(info)`* | Lista todos os atalhos disponíveis e variáveis configuráveis. |
| `make install` | `composer install` | Instala as dependências PHP do projeto. |
| `make serve` | `php artisan serve --host=... --port=...` | Inicia o servidor de desenvolvimento da API. |
| `make test` | `composer test` | Limpa o cache de configuração e roda a suíte do PHPUnit. |
| `make migrate` | `php artisan migrate` | Aplica migrações pendentes no banco SQLite. |
| `make migrate-status` | `php artisan migrate:status` | Exibe o status de cada migration sem alterar o banco. |
| `make lint` | `php vendor/bin/pint --test` | Analisa a formatação do código com Laravel Pint (somente leitura). |
| `make docs-serve` | `mkdocs serve -a ...:8001` | Inicia o servidor local da documentação MkDocs. |
| `make docs-build` | `mkdocs build --strict` | Compila a documentação estática com validação estrita. |

---

## Detalhamento dos atalhos

### `help` (padrão)

Exibe a lista resumida de comandos de forma amigável no terminal. É o alvo padrão disparado ao executar apenas `make`.

- **Pré-requisitos:** GNU Make instalado.
- **Exemplo de uso:**
  ```bash
  make
  # ou
  make help
  ```

---

### `install`

Executa a instalação de todas as dependências declaradas no `composer.json`.

- **Pré-requisitos:** Composer instalado no PATH.
- **Exemplo de uso:**
  ```bash
  make install
  ```

---

### `serve`

Inicia o servidor de desenvolvimento embutido do Laravel (`artisan serve`). Por padrão, sobe em `http://127.0.0.1:8000`.

- **Pré-requisitos:** Dependências instaladas (`vendor/`) e arquivo `.env` configurado.
- **Exemplo de uso padrão:**
  ```bash
  make serve
  ```
- **Customização de porta ou host:**
  ```bash
  make serve PORT=8080
  make serve HOST=0.0.0.0 PORT=8080
  ```

---

### `test`

Executa o script `composer test`, que realiza a limpeza de configurações em cache (`php artisan config:clear`) e dispara a suíte de testes automatizados com PHPUnit.

- **Pré-requisitos:** Dependências de desenvolvimento instaladas (`make install`).
- **Exemplo de uso:**
  ```bash
  make test
  ```

---

### `migrate`

Executa as migrations pendentes no banco de dados configurado no `.env` (SQLite por padrão).

- **Pré-requisitos:** Banco de dados SQLite criado (`touch database/database.sqlite`) e `.env` configurado.
- **Exemplo de uso:**
  ```bash
  make migrate
  ```

---

### `migrate-status`

Exibe uma tabela detalhada com o status de cada migração (se já foi executada e o número do lote correspondente). Trata-se de uma operação estritamente de leitura, segura para inspeção do estado do banco.

- **Pré-requisitos:** Banco de dados SQLite acessível.
- **Exemplo de uso:**
  ```bash
  make migrate-status
  ```

---

### `lint`

Dispara o **Laravel Pint** em modo de checagem (`--test`). Essa verificação não altera nenhum arquivo no repositório; apenas aponta violações de estilo de código segundo as regras do Laravel.

- **Pré-requisitos:** Dependências instaladas (`vendor/bin/pint`).
- **Exemplo de uso:**
  ```bash
  make lint
  ```

---

### `docs-serve`

Inicia o servidor de desenvolvimento do portal de documentação (MkDocs Material). Por padrão, utiliza a porta `8001` para não colidir com o servidor Laravel (`8000`).

- **Pré-requisitos:** Ambiente virtual Python ativo com MkDocs instalado (`source .venv/bin/activate`).
- **Exemplo de uso:**
  ```bash
  make docs-serve
  ```
- **Customização de porta:**
  ```bash
  make docs-serve DOCS_PORT=8002
  ```

---

### `docs-build`

Executa a validação e geração estática do portal de documentação em modo estrito (`--strict`). Garante que não haja links quebrados ou avisos pendentes na documentação.

- **Pré-requisitos:** Ambiente virtual Python ativo com MkDocs instalado.
- **Exemplo de uso:**
  ```bash
  make docs-build
  ```

---

## Sobrescrevendo variáveis

O `Makefile` utiliza atribuições condicionais (`?=`), permitindo ajustar caminhos e portas sem editar o arquivo:

```bash
# Usar binários específicos de PHP ou Composer
make test PHP=/usr/bin/php8.3 COMPOSER=/usr/local/bin/composer

# Executar o servidor Laravel em outro endereço e porta
make serve HOST=0.0.0.0 PORT=9000
```

---

## Próximos passos e integração com Docker

Atualmente, o ambiente Docker do Artel está em desenvolvimento e padronização. Uma vez finalizada e estabilizada a infraestrutura de containers, os seguintes atalhos poderão ser adicionados ao facilitador:

- `make docker-up`: Inicialização dos containers em background (`docker compose up -d`).
- `make docker-down`: Encerramento dos containers (`docker compose down`).
- `make docker-test`: Execução dos testes automatizados dentro do container `app`.
- `make docker-migrate`: Execução de migrações dentro do container da aplicação.
- `make docker-bash`: Abertura de shell interativo no container da aplicação.
