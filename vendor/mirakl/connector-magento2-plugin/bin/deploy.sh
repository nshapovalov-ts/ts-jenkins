#!/usr/bin/env bash

SCRIPT=${0##*/}
IFS=$'\n'
USAGE="\
Mirakl Magento 2 Connector Packager
Copyright © 2021 Mirakl. www.mirakl.com - info@mirakl.com
All rights reserved. Tous droits réservés.
Strictly Confidential, this data may not be reproduced or redistributed.
Use of this data is pursuant to a license agreement with Mirakl.
Usage: VERSION=1.0.0 deploy.sh
"

echo "$USAGE"

# Init Maven
export MAVEN_HOME=$JENKINS_HOME/tools/hudson.tasks.Maven_MavenInstallation/maven-3

if [ -z "${VERSION}" ]; then
    echo "Need to set VERSION"
    exit 1
fi

BASEDIR="$(dirname $0)/.."

# Go to root directory
cd "$BASEDIR"

# Specify target directory for zip generation
TARGET="build"

if [ ! -d "$TARGET" ]; then
    mkdir -p "$TARGET"
fi

FILENAME="$TARGET/mirakl-magento2-connector-$VERSION.zip"

function build_and_deploy
{
    rm -rf $FILENAME
    zip -r $FILENAME \
        composer.json \
        README.md \
        registration.php \
        phpunit.xml.dist \
        LICENSE.txt \
        Adminhtml \
        Api/ \
        Catalog/ \
        Connector/ \
        Core/ \
        Event/ \
        FrontendDemo/ \
        Mci/ \
        Mcm/ \
        Process/ \
        Sync/ \

    ${MAVEN_HOME}/bin/mvn \
        -B deploy:deploy-file \
        -Durl="http://artifactory.mirakl.net/artifactory/mirakl-ext-repo" \
        -DrepositoryId="mirakl-ext-repo" \
        -Dfile="$FILENAME" \
        -DgroupId="com.mirakl" \
        -DartifactId="mirakl-magento2-connector" \
        -Dversion="$VERSION" \
        -Dpackaging="zip" \
        -DgeneratePom=false
}

build_and_deploy
