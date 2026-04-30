pipeline {
    agent any

    stages {
        stage('Run Jenkins Local Dev Deploy') {
            steps {
                withCredentials([string(credentialsId: 'github-token', variable: 'GITHUB_TOKEN')]) {
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
    }

    post {
        always {
            echo 'Build finished.'
        }
    }
}
