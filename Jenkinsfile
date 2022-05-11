pipeline {
    agent any
    stages {
        stage("Build") {
            steps {
                sh 'php --version'
            }
        }
        stage("Unit test") {
            steps {
                sh 'php artisan test'
            }
        }
        stage("Code coverage") {
            steps {
                sh "vendor/bin/phpunit --coverage-html 'reports/coverage'"
            }
        }
        stage("Static code analysis larastan") {
            steps {
                sh "vendor/bin/phpstan analyse --memory-limit=2G"
            }
        }
        stage("Static code analysis phpcs") {
            steps {
                sh "vendor/bin/phpcs"
            }
        }
        stage("Docker build") {
            steps {
                sh "docker build -t danielgara/laravel8cd ."
            }
        }
        stage("Docker push") {
            environment {
                DOCKER_USERNAME = credentials("docker-user")
                DOCKER_PASSWORD = credentials("docker-password")
            }
            steps {
                sh "docker login --username ${DOCKER_USERNAME} --password ${DOCKER_PASSWORD}"
                sh "docker push danielgara/laravel8cd"
            }
        }
        stage("Deploy to staging") {
            steps {
                sh "docker run -d --rm -p 80:80 --name laravel8cd danielgara/laravel8cd"
            }
        }
        stage("Acceptance test curl") {
            steps {
                sleep 20
                sh "chmod +x acceptance_test.sh && ./acceptance_test.sh"
            }
        }
        stage("Acceptance test codeception") {
            steps {
                sh "vendor/bin/codecept run"
            }
            post {
                always {
                    sh "docker stop laravel8cd"
                }
            }
        }
    }
}
