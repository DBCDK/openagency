#!groovy

def workerNode = "devel10"

pipeline {
    agent {
        label workerNode
    }
    environment {
        VERSION = "1.0.0"

        // This is the prefix used by the docker container builds. If you need to change this,
        // you must sync it with the values from the source code. (Shell scripts, compose files, etc).
        DOCKER_BUILD_PREFIX = "openagency-php-local"

        // This is the prefix used for PUSH
        DOCKER_PUSH_PREFIX = "docker-i.dbc.dk"

        // We use this tag during building, to avoid clashing with other builds
        DOCKER_BUILD_TAG = "${env.BUILD_TAG}"

        // This is how we wish to mark the pushed tags
        DOCKER_PUSH_TAG = "${env.BUILD_NUMBER}"

        // The registry to push images to
        registry = "https://docker-i.dbc.dk"
        registryCredential = "docker"

        // Used to update vip-openagency yml file in dit-gitops-secrets project
        GITLAB_PRIVATE_TOKEN = credentials("metascrum-gitlab-api-token")
    }
    triggers {
        pollSCM("H/3 * * * *")
        upstream( upstreamProjects: '/vip/vipcore/master,/Docker/Docker-apache-php7/master')
    }
    options {
        buildDiscarder(logRotator(artifactDaysToKeepStr: "", artifactNumToKeepStr: "", daysToKeepStr: "30", numToKeepStr: "30"))
        timestamps()
        // Limit concurrent builds to one pr. branch.
        disableConcurrentBuilds()
    }

    stages {
        stage("clear workspace") {
            steps {
                deleteDir()
                checkout scm
            }
        }
        stage('Checkout OLS_class_lib') {
            /* Let's make sure we have the OLS_class_lib repository cloned to our workspace */
            steps {
                checkout scm
                script {
                    sh "script/bootstrap"
                }
            }
        }

        // Build all docker images by script. If they pass the systemtest, and we are on master, they will be pushed.
        stage("Docker Build") {
            steps {
                script {
                    currentBuild.displayName = "Building Dockers for ${env.BUILD_TAG}"
                }
                // This builds the docker images with a prefix and a tag that is related to this jenkins job.
                ansiColor("xterm") {
                    sh """#!/usr/bin/env bash
                    set -e
                    build-dockers.py --debug --pull --tag ${DOCKER_BUILD_TAG} --output tags-to-push.json
                    """
                }
                script {
                    // The next step... Cheating a bit here.
                    currentBuild.displayName = "Testing ${env.BRANCH_NAME} / ${env.BUILD_TAG}"
                }
            }
        }

        stage("Integration tests") {
            failFast true
            parallel {
                stage("Systemtest") {
                    steps {
                        script {
                            echo "Running integration test on ${DOCKER_BUILD_TAG}"
                            ansiColor("xterm") {
                                sh """#!/usr/bin/env bash
                                set -e
                                pwd
                                ./run-system-test.sh --debug --pull --tag ${DOCKER_BUILD_TAG}
                                """
                            }
                        }
                    }
                }
            }
         }

        stage("Docker Push") {
            // If, we are on branch master, and tests passed, push to artifactory, using "push names"
            when {
                branch "master"
            }
            steps {
                script {
                    currentBuild.displayName = "Pushing images for ${env.BUILD_TAG}"

                    // Get a list of all the images that needs pushing - saved in the previous step
                    def tags = readJSON file: 'tags-to-push.json'

                    // Retag, using the shell. Then push, using the docker abstraction.
                    // Why the retag here - because we use a different docker prefix, and
                    // want to change the name. The idea of using a "limited" docker prefix is
                    // to make the seperation between "vip-local" and "docker-i.dbc.dk" very clear.
                    // If the Jenkins docker abstraction supported renaming, that would be great.
                    // First the rename - if any rename fails, nothing has been pushed.
                    echo "Retagging all images before pushing to repository"
                    for (int i = 0; i < tags.size(); i++) {
                        def buildTag = tags[i]
                        def pushTag = toPushTag(buildTag, DOCKER_BUILD_PREFIX, DOCKER_PUSH_PREFIX, DOCKER_BUILD_TAG, DOCKER_PUSH_TAG)
                        echo "Retagging $buildTag to $pushTag"
                        ansiColor("xterm") {
                            sh """#!/usr/bin/env bash
                            set -e
                            docker tag "${buildTag}" "${pushTag}"
                        """
			            }
			            // This project also needs a latest tag.
                        pushTag = toPushTag(buildTag, DOCKER_BUILD_PREFIX, DOCKER_PUSH_PREFIX, DOCKER_BUILD_TAG, "latest")
                        echo "Retagging $buildTag to $pushTag"
                        ansiColor("xterm") {
                            sh """#!/usr/bin/env bash
                            set -e
                            docker tag "${buildTag}" "${pushTag}"
                        """
                        }
                    }

                    echo "Pushing images to repository"
                    for (int i = 0; i < tags.size(); i++) {
                        def buildTag = tags[i]
                        def pushTag = toPushTag(buildTag, DOCKER_BUILD_PREFIX, DOCKER_PUSH_PREFIX, DOCKER_BUILD_TAG, DOCKER_PUSH_TAG)
                        // Wrap the images in docker abstractions.
                        image = docker.image(pushTag)
                        docker.withRegistry(registry, registryCredential) {
                            image.push()
                        }
                        echo "Image pushed with tag $pushTag"
                        pushTag = toPushTag(buildTag, DOCKER_BUILD_PREFIX, DOCKER_PUSH_PREFIX, DOCKER_BUILD_TAG, "latest")
                        // Wrap the images in docker abstractions.
                        image = docker.image(pushTag)
                        docker.withRegistry(registry, registryCredential) {
                            image.push()
                        }
                        echo "Image pushed with tag $pushTag"
                    }

                    // And, finally, an overview.
                    // Yes, this is not very elegant code, but debugging it is a pain, so stay simple.
                    echo "These images were pushed to the repository:"
                    for (int i = 0; i < tags.size(); i++) {
                        def buildTag = tags[i]
                        def pushTag = toPushTag(buildTag, DOCKER_BUILD_PREFIX, DOCKER_PUSH_PREFIX, DOCKER_BUILD_TAG, DOCKER_PUSH_TAG)
                        echo "=>  $pushTag"
                        pushTag = toPushTag(buildTag, DOCKER_BUILD_PREFIX, DOCKER_PUSH_PREFIX, DOCKER_BUILD_TAG, "latest")
                        echo "=>  $pushTag"
                    }

                    currentBuild.displayName = "Pushed *-${VERSION}:${DOCKER_PUSH_TAG}"
                }
            }
        }

        // This stage updates the services/vip-openagency.yml file in the project metascrum/dit-gitops-secrets with the
        // new buildnumber to ensure DIT always runs on the correct version
        stage("Update DIT") {
            agent {
                docker {
                    label workerNode
                    image "docker.dbc.dk/build-env:latest"
                    alwaysPull true
                }
            }
            when {
                expression {
                    (currentBuild.result == null || currentBuild.result == 'SUCCESS') && env.BRANCH_NAME == 'master'
                }
            }
            steps {
                script {
                    dir("deploy") {
                        sh "set-new-version services/vip-openagency.yml ${env.GITLAB_PRIVATE_TOKEN} metascrum/dit-gitops-secrets ${DOCKER_PUSH_TAG} -b master"
                    }
                }
            }
        }
    }

    post {
        always {
            junit '**/TEST-junit-jupiter.xml'
        }
        // The intention is to differentiate between master and branches. For
        // * master : All developers gets mail (with log) on every failed build, and on fixed buils. Slack to #iscrum.
        //          : Also
        // * non-master: Only culprit (if possible), no log.
        // To identify master, everything is wrapped in a script section.
        fixed {
            script {
                if ("${env.BRANCH_NAME}" == 'master') {
                    emailext(
                            to: "iscrum@dbc.dk",
                            subject: "[Jenkins] ${env.JOB_NAME} #${env.BUILD_NUMBER} back to normal",
                            mimeType: 'text/html; charset=UTF-8',
                            body: "<p>The master build is back to normal.</p><p><a href=\"${env.BUILD_URL}\">Build information</a>.</p>",
                            attachLog: false,
                    )
                    slackSend(channel: 'iscrum',
                            color: 'good',
                            message: "${env.JOB_NAME} #${env.BUILD_NUMBER} back to normal: ${env.BUILD_URL}",
                            tokenCredentialId: 'slack-global-integration-token')

                } else {
                    // this is some other branch, only send to developer
                    emailext(
                            recipientProviders: [developers()],
                            subject: "[Jenkins] ${env.BUILD_TAG} is back to normal",
                            mimeType: 'text/html; charset=UTF-8',
                            body: "<p>${env.BUILD_TAG} is back to normal. </p><p><a href=\"${env.BUILD_URL}\">Build information</a>.</p>",
                            attachLog: false,
                    )
                }
            }
        }
        failure {
            // archiveArtifacts artifacts: 'dev/docker/**/*_container_log.txt', fingerprint: true
            script {
                if ("${env.BRANCH_NAME}" == 'master') {
                    emailext(
                            recipientProviders: [developers(), culprits()],
                            to: "iscrum@dbc.dk",
                            subject: "[Jenkins] ${env.JOB_NAME} #${env.BUILD_NUMBER} failed",
                            mimeType: 'text/html; charset=UTF-8',
                            body: "<p>The master build failed. Log attached. </p><p><a href=\"${env.BUILD_URL}\">Build information</a>.</p>",
                            attachLog: true,
                    )
                    slackSend(channel: 'iscrum',
                            color: 'warning',
                            message: "${env.JOB_NAME} #${env.BUILD_NUMBER} failed and needs attention: ${env.BUILD_URL}",
                            tokenCredentialId: 'slack-global-integration-token')

                } else {
                    // this is some other branch, only send to developer
                    emailext(
                            recipientProviders: [developers()],
                            subject: "[Jenkins] ${env.BUILD_TAG} failed and needs your attention",
                            mimeType: 'text/html; charset=UTF-8',
                            body: "<p>${env.BUILD_TAG} failed and needs your attention. </p><p><a href=\"${env.BUILD_URL}\">Build information</a>.</p>",
                            attachLog: false,
                    )
                }
            }
            println("Job was a failure.")
        }
        success {
            println("Job was a succes.")
            script {
                if ("${env.BRANCH_NAME}" == 'master') {
                    slackSend(channel: 'iscrum',
                            color: 'good',
                            message: "${env.JOB_NAME} #${env.BUILD_NUMBER} completed, and pushed *-${VERSION}:${DOCKER_PUSH_TAG} to artifactory.",
                            tokenCredentialId: 'slack-global-integration-token')

                }
            }
        }
    }
}

// Takes an image, and substitutes the prefix and tag
def toPushTag(tag, prefixFrom, prefixTo, tagFrom, tagTo) {
    tag = tag.replaceFirst(/^$prefixFrom/, prefixTo)
    tag = tag.replaceFirst(/:$tagFrom$/, ":$tagTo")
    return tag
}
