pipeline {
    agent { label 'staging_dev4' }
    parameters {
        booleanParam(name: 'COMPOSER_INSTALL',
            defaultValue:true,
            description:'Run composer install'
        )
        booleanParam(name: 'BUILD_FRONTEND',
            defaultValue:true,
            description:'Should we run frontend build?'
        )
        booleanParam(name: 'RUN_CS_TEST',
            defaultValue:true,
            description:'Run phpcs check?'
        )
        booleanParam(name: 'RUN_MD_TEST',
            defaultValue:true,
            description:'Run phpmd check?'
        )
    }
    stages {
        stage("PHP Code Sniffer Tests") {
            steps {
                script {
                    if (env.RUN_CS_TEST == 'true') {
                        echo "Run PHP Code Sniffer tests"
                        sh 'chmod 777 validate-phpcs.sh'
                        sh './validate-phpcs.sh'
                    } else {
                        echo "PHP Code Sniffer skipped"
                    }
                }
            }
        }
        stage("PHP Mess Detector Tests") {
            steps {
                script {
                    if (env.RUN_MD_TEST == 'true') {
                        echo "Run PHP Mess Detector tests"
                        sh 'chmod 777 validate-phpmd.sh'
                        sh './validate-phpmd.sh'
                    } else {
                        echo "PHP Mess Detector skipped"
                    }
                }
            }
        }
        stage("Build") {
            steps {
                script {
                    if (env.COMPOSER_INSTALL == 'true') {
                        sh 'php --version'
                        sh 'rm -rf vendor/*'
                        sh 'composer install'
                    } else {
                        echo "Composer install skipped"
                    }
                }
            }
        }
    }
}
