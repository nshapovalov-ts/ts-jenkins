pipeline {
    agent any
    node("MagentoStaging") {
    stages {
        stage("Build") {
            steps {
                sh 'php --version'
            }
        }
    }
    }
}
