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
        stage("Build") {
            steps {
                script {
                    if (env.COMPOSER_INSTALL == 'true') {
                        sh 'php --version'
                        sh 'composer install'
                    } else {
                        echo "Composer install skipped"
                    }
                }
            }
        }
        stage("CodeSniffer Tests") {
            steps {
                script {
                    if (env.RUN_CS_TEST == 'true') {
                        sh 'chmod 777 validate-phpcs.sh'
                        sh './validate-phpcs.sh'
                    } else {
                        echo "PHP Code Sniffer skipped"
                    }
                }
            }
        }
        stage("PhpMd Tests") {
            steps {
                script {
                    if (env.RUN_MD_TEST == 'true') {
                        sh 'chmod 777 validate-phpmd.sh'
                        sh './validate-phpmd.sh'
                    } else {
                        echo "PHP Mess Detector skipped"
                    }
                }
            }
        }
    }
}
