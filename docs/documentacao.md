# Documentação (MkDocs)

Esta documentação usa **MkDocs** com o tema **Material**. As dependências Python ficam isoladas em um ambiente virtual e não se misturam com as dependências PHP (Composer) ou Node (npm) da aplicação.

| Arquivo | Função |
|---|---|
| `mkdocs.yml` | Configuração do site e da navegação (raiz do projeto) |
| `docs/` | Páginas em Markdown |
| `requirements-docs.txt` | Dependências Python com versões fixadas |
| `.venv/` | Ambiente virtual local (não versionado) |
| `site/` | Saída do build (não versionada) |

## Preparar o ambiente

Pré-requisito: Python 3 com o módulo `venv`.

```bash
# Linux / macOS / WSL
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements-docs.txt
```

```powershell
# Windows (PowerShell)
py -m venv .venv
.venv\Scripts\Activate.ps1
pip install -r requirements-docs.txt
```

## Servidor local

```bash
mkdocs serve
```

O site fica disponível em `http://127.0.0.1:8000` e recarrega ao salvar os arquivos.

!!! tip "Conflito de porta"
    O `php artisan serve` também usa a porta 8000 por padrão. Para executar os dois ao mesmo tempo, use outra porta para a documentação: `mkdocs serve -a 127.0.0.1:8001`.

## Build

```bash
mkdocs build --strict
```

Gera o site estático em `site/`. Com `--strict`, avisos como links quebrados, âncoras inexistentes ou páginas fora da navegação fazem o build falhar.

## Adicionar uma página

1. Crie o arquivo `.md` em `docs/`.
2. Inclua a página na seção `nav` do `mkdocs.yml`.
3. Execute `mkdocs build --strict` para validar.
