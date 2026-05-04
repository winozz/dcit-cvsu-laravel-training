pipeline {
    agent any

    parameters {
        choice(
            name: 'TUNNEL_TYPE',
            choices: ['ssh', 'cloudflare', 'none'],
            description: 'Tunnel method for temporary public access. ssh = localhost.run (built-in OpenSSH, no download). cloudflare = trycloudflare.com (downloads cloudflared.exe). none = skip tunnel.'
        )
    }

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