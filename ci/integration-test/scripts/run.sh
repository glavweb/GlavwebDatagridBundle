#!/bin/bash
set -x
set -e

composer require -n glavweb/datagrid-bundle:dev-master

rm src/DataFixtures/AppFixtures.php

php bin/console --env test -n doctrine:database:create
php bin/console --env test -n doctrine:schema:create -q
php bin/console --env test -n doctrine:fixtures:load

php -d xdebug.start_with_request=yes bin/phpunit --stop-on-error
