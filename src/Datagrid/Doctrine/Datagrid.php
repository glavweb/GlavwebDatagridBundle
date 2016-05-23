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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Glavweb\DatagridBundle\Builder\Doctrine\DatagridContext;
use Glavweb\DatagridBundle\DataSchema\DataSchema;

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
     * @var DataSchema
     */
    private $dataSchema;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var array
     */
    private $queryHints = [];

    /**
     * @var Paginator
     */
    private $paginator;

    /**
     * @param DatagridContext $context
     */
    public function __construct(DatagridContext $context)
    {
        $this->queryBuilder = $context->getQueryBuilder();
        $this->dataSchema   = $context->getDataSchema();
        $this->orderings    = $context->getOrderings();
        $this->firstResult  = $context->getFirstResult();
        $this->maxResults   = $context->getMaxResults();
        $this->alias        = $context->getAlias();
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
        $paginator = $this->getPaginator();

        $query = $paginator->getQuery();
        $query->setHydrationMode($this->getHydrationMode());
        $this->setHintsToQuery($query);

        $result = $paginator->getIterator()->getArrayCopy();
        if ($this->dataSchema) {
            return $this->dataSchema->getList($result);
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        $paginator = $this->getPaginator();

        return (int)$paginator->count();
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

    /**
     * @return Paginator
     */
    protected function getPaginator()
    {
        if (!$this->paginator) {
            $queryBuilder = $this->getQueryBuilder();
            $alias        = $this->getAlias();
            $firstResult  = $this->getFirstResult();
            $maxResults   = $this->getMaxResults();

            $queryBuilder->setFirstResult($firstResult);
            $queryBuilder->setMaxResults($maxResults);

            $orderings = $this->getOrderings();
            foreach ($orderings as $fieldName => $order) {
                $queryBuilder->addOrderBy($alias . '.' . $fieldName, $order);
            }

            $query = $queryBuilder->getQuery();
            $this->paginator = new Paginator($query);
        }

        return $this->paginator;
    }
}