#!/bin/bash
set -x
set -e

cd `dirname "$0"`

export COMPOSE_PROJECT_NAME=glavweb-datagrid-bundle-test
export BUNDLE_VERSION=$(git describe --tags `git rev-list --tags --max-count=1`)

docker-compose rm --force --stop
docker-compose build --build-arg BUNDLE_VERSION=$BUNDLE_VERSION
docker-compose up --no-start
set +e
docker-compose run --rm php ../scripts/run.sh
set -e
docker-compose rm --force --stop