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

use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as NativeQueryBuilder;

/**
 * Interface JoinBuilderInterface
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface JoinBuilderInterface
{
    /**
     * @param NativeQueryBuilder|ORMQueryBuilder $queryBuilder
     * @param JoinMap $joinMap
     * @return string|null
     */
    public function apply($queryBuilder, JoinMap $joinMap): ?string;
}