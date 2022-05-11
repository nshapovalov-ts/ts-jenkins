pipeline {
    agent { label 'staging_dev4' }
    parameters {
        booleanParam(name: 'BUILD_FRONTEND',
            defaultValue:true,
            description:'Should we run frontend build?'
        )
    }
    stages {
        stage("Build") {
            steps {
                sh 'php --version'
                sh 'composer install'
            }
        }
        stage("CodeSniffer Tests") {
            steps {
                sh 'chmod 777 validate-phpcs.sh'
                sh './validate-phpcs.sh'
            }
        }
        stage("PhpMd Tests") {
            steps {
                sh 'chmod 777 validate-phpmd.sh'
                sh './validate-phpmd.sh'
            }
        }
    }
}
