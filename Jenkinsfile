// Pipeline de CI/CD do Artel (Laravel 13 / PHP 8.4).
// Fluxo: Build → Quality → Testes e Cobertura → Package → Deploy e Smoke Test.
// Plugins usados: Pipeline, Git, JUnit, Coverage e Credentials Binding.
// Pré-requisitos do agente: PHP 8.4 (pdo_sqlite, mbstring, dom, xml), Composer,
// curl, e um driver de cobertura (PCOV ou Xdebug).
// Deploy (somente main): flyctl + credencial secreta "fly-api-token" (FLY_API_TOKEN).
pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '20'))
        disableConcurrentBuilds()
        timeout(time: 45, unit: 'MINUTES')
    }

    environment {
        REPORTS_DIR = 'build/reports'
        PACKAGE_DIR = 'build/package'
        FLY_APP = 'artel-c14'
        DEPLOY_URL = 'https://artel-c14.fly.dev'
    }

    stages {
        stage('Build') {
            steps {
                sh '''
                    php -v
                    composer --version

                    composer validate --no-check-publish
                    composer install --prefer-dist --no-progress --no-interaction

                    cp .env.example .env
                    php artisan key:generate --ansi
                '''
            }
        }

        // Verifica formatação (Laravel Pint) e vulnerabilidades de dependências (Composer Audit).
        // Qualquer exit code != 0 falha o stage e impede o avanço da pipeline.
        stage('Quality') {
            steps {
                sh '''
                    ./vendor/bin/pint --test
                    composer audit --no-interaction
                '''
            }
        }

        // Falha nos testes (exit code != 0 do PHPUnit) falha o stage e o Jenkins
        // pula os stages seguintes. Os relatórios são publicados mesmo em caso de falha.
        stage('Testes e Cobertura') {
            steps {
                sh '''
                    rm -rf "$REPORTS_DIR" && mkdir -p "$REPORTS_DIR"
                    php artisan config:clear --ansi

                    # O PHPUnit 12 precisa de PCOV ou Xdebug para gerar cobertura.
                    if php -r 'exit(extension_loaded("pcov") ? 0 : 1);'; then
                        echo "Driver de cobertura: PCOV"
                        PHP_COVERAGE="-d pcov.enabled=1"
                    elif php -r 'exit(extension_loaded("xdebug") ? 0 : 1);'; then
                        echo "Driver de cobertura: Xdebug"
                        PHP_COVERAGE=""
                        export XDEBUG_MODE=coverage
                    else
                        echo "ERRO: nenhum driver de cobertura (PCOV ou Xdebug) instalado no PHP do agente Jenkins."
                        exit 1
                    fi

                    php $PHP_COVERAGE vendor/bin/phpunit \
                        --log-junit "$REPORTS_DIR/junit.xml" \
                        --coverage-cobertura "$REPORTS_DIR/cobertura.xml" \
                        --coverage-html "$REPORTS_DIR/coverage-html" \
                        --coverage-text
                '''
            }
            post {
                always {
                    // allowEmptyResults evita esconder o erro original quando o PHPUnit nem chega a rodar.
                    junit testResults: 'build/reports/junit.xml', allowEmptyResults: true
                    recordCoverage(
                        tools: [[parser: 'COBERTURA', pattern: 'build/reports/cobertura.xml']],
                        sourceCodeRetention: 'LAST_BUILD'
                    )
                    archiveArtifacts artifacts: 'build/reports/**', allowEmptyArchive: true
                }
            }
        }

        // Valida o build de produção e gera um pacote versionado (todas as branches).
        stage('Package') {
            steps {
                sh '''
                    composer install --no-dev --optimize-autoloader --prefer-dist --no-progress --no-interaction

                    APP_ENV=production APP_DEBUG=false php artisan config:cache --ansi
                    APP_ENV=production APP_DEBUG=false php artisan route:cache --ansi
                    APP_ENV=production APP_DEBUG=false php artisan view:cache --ansi
                    php artisan optimize:clear --ansi

                    rm -rf "$PACKAGE_DIR" && mkdir -p "$PACKAGE_DIR"
                    PACKAGE="$PACKAGE_DIR/artel-${BUILD_NUMBER}-${GIT_COMMIT:0:8}.tar.gz"

                    tar -czf "$PACKAGE" \
                        --exclude='*.log' \
                        --exclude='database/*.sqlite' \
                        --exclude='storage/framework/cache/data/*' \
                        --exclude='storage/framework/sessions/*' \
                        --exclude='storage/framework/views/*' \
                        app bootstrap config database public resources routes storage vendor \
                        artisan composer.json composer.lock .env.example

                    sha256sum "$PACKAGE" > "$PACKAGE.sha256"
                    ls -lh "$PACKAGE_DIR"
                '''
            }
            post {
                success {
                    archiveArtifacts artifacts: 'build/package/*', fingerprint: true
                }
            }
        }

        // Publica no Fly.io e valida GET /up. Somente na branch main, após stages anteriores.
        stage('Deploy e Smoke Test') {
            when {
                anyOf {
                    branch 'main'
                    expression {
                        def branch = env.BRANCH_NAME ?: env.GIT_BRANCH ?: ''
                        return branch == 'main' || branch == 'origin/main' || branch.endsWith('/main')
                    }
                }
            }
            environment {
                FLY_API_TOKEN = credentials('fly-api-token')
            }
            steps {
                sh '''
                    set -eu

                    if ! command -v flyctl >/dev/null 2>&1 && ! command -v fly >/dev/null 2>&1; then
                        echo "Instalando flyctl no agente..."
                        curl -L https://fly.io/install.sh | sh
                        export FLYCTL_INSTALL="${HOME}/.fly"
                        export PATH="${FLYCTL_INSTALL}/bin:${PATH}"
                    fi

                    FLY_BIN="$(command -v flyctl || command -v fly)"
                    "$FLY_BIN" version

                    echo "Publicando ${FLY_APP} a partir da main..."
                    "$FLY_BIN" deploy --remote-only --app "$FLY_APP"

                    echo "Smoke test: GET ${DEPLOY_URL}/up"
                    ok=0
                    for i in $(seq 1 18); do
                        code="$(curl -sS -o /tmp/artel-up.body -w '%{http_code}' "${DEPLOY_URL}/up" || true)"
                        echo "tentativa ${i}: HTTP ${code}"
                        if [ "$code" = "200" ]; then
                            ok=1
                            break
                        fi
                        sleep 5
                    done

                    if [ "$ok" != "1" ]; then
                        echo "Smoke test falhou: ${DEPLOY_URL}/up não retornou HTTP 200"
                        exit 1
                    fi

                    echo "Deploy e smoke test OK (${DEPLOY_URL})"
                '''
            }
        }
    }
}
