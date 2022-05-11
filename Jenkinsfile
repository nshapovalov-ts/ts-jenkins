pipeline {
    agent { label 'staging_dev4' }
    stages {
        stage("Build") {
            steps {
                sh 'php --version'
            }
        }
        stage("CodeSniffer Tests") {
            steps {
                sh './validate-phpcs.sh'
            }
        }
        stage("PhpMd Tests") {
            steps {
                sh './validate-phpmd.sh'
            }
        }
    }
}
