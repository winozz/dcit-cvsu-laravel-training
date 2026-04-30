pipeline {
    agent any

    environment {
        GITHUB_TOKEN = credentials('github-token')
    }

    stages {
        stage('Run Jenkins Local Dev Deploy') {
            steps {
                script {
                    if (isUnix()) {
                        sh 'bash jenkins-local-deploy.bat'
                    } else {
                        bat 'call jenkins-local-deploy.bat'
                    }
                }
            }
        }
    }

    post {
        always {
            echo 'Build finished.'
        }
    }
}
