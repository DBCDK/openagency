pipeline {
    agent { label "devel8" }
    triggers {
        pollSCM("H/3 * * * *")
    }
    options {
        buildDiscarder(logRotator(artifactDaysToKeepStr: "", artifactNumToKeepStr: "", daysToKeepStr: "30", numToKeepStr: "30"))
        timestamps()
    }
    stages {
        stage("build") {
            steps {
                // Fail Early..
                script {
                    if (!env.BRANCH_NAME) {
                        currentBuild.rawBuild.result = Result.ABORTED
                        throw new hudson.AbortException('Job Started from non MultiBranch Build')
                    } else {
                        println(" Building BRANCH_NAME == ${BRANCH_NAME}")
                    }

                }

                sh """                     
                    ./build.sh                                                                                    
                """
                archiveArtifacts artifacts: '**/docker/*.tar.gz', fingerprint: true
                //junit "**/target/surefire-reports/TEST-*.xml,**/target/failsafe-reports/TEST-*.xml"
            }
        }
        stage("docker") {
            steps {
                script {
                    dirName = "docker"
                    dir(dirName) {
                        def imageName = "openagency-php".toLowerCase()
                        def imageLabel = env.BUILD_NUMBER
                        if (!(env.BRANCH_NAME ==~ /master|trunk/)) {
                            println("Using branch_name ${BRANCH_NAME}")
                            imageLabel = BRANCH_NAME.split(/\//)[-1]
                            imageLabel = imageLabel.toLowerCase()
                            imageLabel = imageLabel + "-" + env.BUILD_NUMBER
                        } else {
                            println(" Using Master branch ${BRANCH_NAME}")
                        }
                        println("In ${dirName} build openagency as ${imageName}:$imageLabel")
                        def app = docker.build("$imageName:${imageLabel}".toLowerCase(), '--pull --no-cache .')

                        if (currentBuild.resultIsBetterOrEqualTo('SUCCESS')) {
                            docker.withRegistry('https://docker-i.dbc.dk', 'docker') {
                                app.push()
                                if (env.BRANCH_NAME ==~ /master|trunk/) {
                                    app.push "latest"
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}