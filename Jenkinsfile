pipeline {
    agent any

    stages {
        stage('Run Jenkins Local Dev Deploy') {
            steps {
                withCredentials([string(credentialsId: 'github-token', variable: 'GITHUB_TOKEN')]) {
                    script {
                        if (isUnix()) {
                            sh 'bash "${WORKSPACE}/jenkins-local-deploy.sh"'
                        } else {
                            bat 'call "%WORKSPACE%\\jenkins-local-deploy.bat"'
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
