<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\JoinMap\Doctrine;

use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder as NativeQueryBuilder;

/**
 * Interface JoinBuilderInterface.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface JoinBuilderInterface
{
    public function apply(NativeQueryBuilder|ORMQueryBuilder $queryBuilder, JoinMap $joinMap): ?string;
}
