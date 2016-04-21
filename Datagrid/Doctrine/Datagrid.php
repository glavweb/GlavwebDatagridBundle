<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Datagrid\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Datagrid
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class Datagrid extends AbstractDatagrid
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var array
     */
    private $queryHints = [
        Query::HINT_FORCE_PARTIAL_LOAD => true
    ];

    /**
     * @param QueryBuilder $queryBuilder
     * @param array            $orderings
     * @param int              $firstResult
     * @param int              $maxResults
     * @param string           $alias
     * @param string $alias
     */
    public function __construct(QueryBuilder $queryBuilder, array $orderings = null, $firstResult = 0, $maxResults = null, $alias = 't')
    {
        $this->queryBuilder = $queryBuilder;
        $this->orderings   = (array)$orderings;
        $this->firstResult = (int)$firstResult;
        $this->maxResults  = $maxResults;
        $this->alias       = $alias;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getQueryHints()
    {
        return $this->queryHints;
    }

    /**
     * @param array $queryHints
     */
    public function setQueryHints($queryHints)
    {
        $this->queryHints = $queryHints;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setQueryHint($name, $value)
    {
        $this->queryHints[$name] = $value;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $alias        = $this->getAlias();
        $firstResult  = $this->getFirstResult();
        $maxResults   = $this->getMaxResults();
        $orderings    = $this->getOrderings();

        $queryBuilder->setFirstResult($firstResult);
        $queryBuilder->setMaxResults($maxResults);

        foreach ($orderings as $fieldName => $order) {
            $queryBuilder->addOrderBy($alias . '.' . $fieldName, $order);
        }

        $query = $queryBuilder->getQuery();
        $this->setHintsToQuery($query);

        return $query->getResult($this->getHydrationMode());
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        $totalQueryBuilder = clone $this->getQueryBuilder();
        $alias             = $this->getAlias();

        $total = $totalQueryBuilder
            ->select('COUNT(' . $alias . ')')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $total;
    }

    /**
     * @param Query $query
     */
    protected function setHintsToQuery(Query $query)
    {
        $queryHints = $this->getQueryHints();
        foreach ($queryHints as $hintName => $hintValue) {
            $query->setHint($hintName, $hintValue);
        }
    }
}