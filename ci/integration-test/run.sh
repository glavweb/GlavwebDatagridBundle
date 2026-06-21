#!/bin/bash
set -x
set -e

cd `dirname "$0"`

export COMPOSE_PROJECT_NAME=glavweb-datagrid-bundle-test

docker compose rm --force --stop
set +e
docker compose run --build --rm php ../scripts/run.sh
set -e
docker compose rm --force --stop