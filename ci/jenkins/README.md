# Jenkins Server - Infraestrutura de CI

Este diretório contém a definição do Docker Compose para subir o servidor Jenkins oficial, utilizado para execução e teste dos pipelines de CI/CD.

## 🚀 Como Executar

### 1. Iniciar o Jenkins
A partir da raiz do repositório ou deste diretório:

```bash
cd ci/jenkins
docker compose up -d
```

Ou diretamente da raiz:
```bash
docker compose -f ci/jenkins/compose.yml up -d
```

### 2. Acessar a Interface Web
Abra o navegador em:
- [http://localhost:8080](http://localhost:8080)

### 3. Obter a Senha Inicial de Administrador
Na primeira inicialização, o Jenkins solicitará o token de desbloqueio. Obtenha-o com:

```bash
docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
```

### 4. Parar o Servidor
Para encerrar a execução do container:

```bash
cd ci/jenkins
docker compose down
```

---

## ⚙️ Detalhes da Configuração (`compose.yml`)

- **Imagem Oficial:** `jenkins/jenkins:lts` (Long-Term Support)
- **Portas:**
  - `8080`: Acesso à interface web do Jenkins.
  - `50000`: Comunicação com agentes (inbound/JNLP).
- **Volumes:**
  - `jenkins_home`: Volume persistente do Docker para preservar plugins, configurações, jobs e histórico de builds.
  - `/var/run/docker.sock`: Permite que os pipelines utilizem o daemon Docker do host para executar passos em containers.
- **Usuário:** `root` (garante permissões de leitura/escrita no socket do Docker e no volume).

---

## GitHub Actions (check verde no GitHub)

Além do Jenkins local, o repositório tem [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml):

- Em **todo push/PR** para `main` ou `dev`: Build → Quality → Testes (status no commit)
- Em **push na `main`**: Deploy Fly + smoke `/up` (precisa do secret `FLY_API_TOKEN` em *Settings → Secrets and variables → Actions*)

```bash
fly tokens create deploy -x 999999h -a artel-c14
# cole o valor em FLY_API_TOKEN no GitHub
```

## Pipeline Jenkins (`Jenkinsfile` na raiz)

Fluxo integrado:

1. **Build** — `composer install`, `.env`, `key:generate`
2. **Quality** — Laravel Pint (`--test`) + Composer Audit
3. **Testes e Cobertura** — PHPUnit + JUnit/Cobertura
4. **Package** — caches de produção + artefato `.tar.gz` (todas as branches)
5. **Deploy e Smoke Test** — **somente `main`**: `fly deploy` no app `artel-c14` + `GET https://artel-c14.fly.dev/up` (HTTP 200)

### Credencial Fly (obrigatória para Deploy na main)

1. Gere um token de deploy:

```bash
fly tokens create deploy -x 999999h -a artel-c14
```

2. No Jenkins: **Manage Jenkins → Credentials → Add**  
   - Kind: **Secret text**  
   - ID: **`fly-api-token`** (exato)  
   - Secret: cole o token

3. O agente precisa de `curl` (e baixa o `flyctl` no stage se ainda não estiver instalado).

Sem a credencial, builds da `main` falham no último stage; builds de outras branches seguem até o Package.
