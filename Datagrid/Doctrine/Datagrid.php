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
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

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

        if ($this->dataSchema->getHydrationMode() !== null) {
            $this->setHydrationMode($this->dataSchema->getHydrationMode());
        }
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
    public function getItem()
    {
        $query = $this->createQuery();

        $query->setHydrationMode($this->getHydrationMode());
        $this->setHintsToQuery($query);

        $result = $query->getSingleResult();
        if ($this->dataSchema) {
            return $this->dataSchema->getData($result);
        }

        return $result;
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
            $query = $this->createQuery();

            $this->paginator = new Paginator($query);
        }

        return $this->paginator;
    }

    /**
     * @return Query
     */
    private function createQuery()
    {
        $queryBuilder = $this->getQueryBuilder();
        $alias        = $this->getAlias();
        $firstResult  = $this->getFirstResult();
        $maxResults   = $this->getMaxResults();

        $queryBuilder->setFirstResult($firstResult);
        $queryBuilder->setMaxResults($maxResults);

        $querySelects = $this->dataSchema->getQuerySelects();
        foreach ($querySelects as $propertyName => $querySelect) {
            if ($this->dataSchema->hasProperty($propertyName)) {
                $queryBuilder->addSelect(sprintf('(%s) as %s', $querySelect, $propertyName));
            }
        }

        $orderings = $this->getOrderings();
        foreach ($orderings as $fieldName => $order) {
            if (isset($querySelects[$fieldName]) && $this->dataSchema->hasProperty($fieldName)) {
                $queryBuilder->addOrderBy($fieldName, $order);

                continue;
            }

            if (!$this->dataSchema->hasPropertyInDb($fieldName)) {
                continue;
            }

            $sortAlias = $alias;
            $sortFieldName = $fieldName;

            // If the field name have a dot
            $fieldNameParts = explode('.', $fieldName);
            if (count($fieldNameParts) > 1) {
                $sortFieldName = array_pop($fieldNameParts);

                foreach ($fieldNameParts as $fieldPart) {
                    $sortAlias = JoinMap::makeAlias($sortAlias, $fieldPart);
                }
            }

            $queryBuilder->addOrderBy($sortAlias . '.' . $sortFieldName, $order);
        }

        $query = $queryBuilder->getQuery();

        return $query;
    }
}