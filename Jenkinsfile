// Pipeline de CI/CD do Artel (Laravel 13 / PHP 8.3).
// Plugins usados: Pipeline, Git, JUnit e Coverage.
// Pré-requisitos do agente: PHP 8.3 (pdo_sqlite, mbstring, dom, xml), Composer
// e um driver de cobertura (PCOV ou Xdebug) para o stage de Testes e Cobertura.
pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '20'))
        disableConcurrentBuilds()
        timeout(time: 30, unit: 'MINUTES')
    }

    environment {
        REPORTS_DIR = 'build/reports'
        PACKAGE_DIR = 'build/package'
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

        // Não existe ambiente de deploy (servidor, registry ou credenciais) configurado no projeto.
        // Este estágio NÃO publica a aplicação: ele valida que o build de produção funciona
        // e gera um pacote versionado pronto para deploy, arquivado no Jenkins.
        stage('Deploy (validação e empacotamento)') {
            steps {
                sh '''
                    composer install --no-dev --optimize-autoloader --prefer-dist --no-progress --no-interaction

                    # Valida o boot da aplicação com caches de produção e depois os descarta.
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
                    echo "Pacote gerado. Nenhuma publicação foi realizada: não há ambiente de deploy configurado."
                '''
            }
            post {
                success {
                    archiveArtifacts artifacts: 'build/package/*', fingerprint: true
                }
            }
        }
    }
}
