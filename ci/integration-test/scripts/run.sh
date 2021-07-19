#!/bin/bash
set -x
set -e


#../scripts/copy.sh

composer require glavweb/datagrid-bundle orm-fixtures

rm src/DataFixtures/AppFixtures.php

../scripts/copy.sh

php bin/console --env test about

php bin/console --env test -n doctrine:database:create
php bin/console --env test -n doctrine:schema:create
php bin/console --env test -n doctrine:fixtures:load

php bin/phpunit --stop-on-error

#../scripts/copy.sh
