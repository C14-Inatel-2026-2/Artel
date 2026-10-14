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
