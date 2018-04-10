<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Datagrid\Doctrine\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Glavweb\DatagridBundle\Builder\Doctrine\ORM\DatagridContext;
use Glavweb\DatagridBundle\Datagrid\Doctrine\AbstractDatagrid;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
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
     * @var FilterStack
     */
    private $filterStack;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param DatagridContext $context
     */
    public function __construct(DatagridContext $context)
    {
        $this->queryBuilder = $context->getQueryBuilder();
        $this->dataSchema   = $context->getDataSchema();
        $this->filterStack  = $context->getFilterStack();
        $this->orderings    = $context->getOrderings();
        $this->firstResult  = $context->getFirstResult();
        $this->maxResults   = $context->getMaxResults();
        $this->alias        = $context->getAlias();
        $this->parameters   = $context->getParameters();

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
        $query = $this->createQuery(1);

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
            $query = $this->createQuery($this->getMaxResults());

            $this->paginator = new Paginator($query);
        }

        return $this->paginator;
    }

    /**
     * @param int|null $maxResults
     * @return Query
     */
    private function createQuery(?int $maxResults)
    {
        $queryBuilder = $this->getQueryBuilder();
        $alias        = $this->getAlias();
        $firstResult  = $this->getFirstResult();

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
        }

        $queryBuilder->setFirstResult($firstResult);
        $queryBuilder->setMaxResults($maxResults);

        // Apply filter
        $parameters = $this->clearParameters($this->parameters);
        foreach ($parameters as $key => $parameter) {
            if (!$parameter || !is_scalar($parameter)) {
                continue;
            }

            $jsonDecoded = json_decode($parameter);

            if (json_last_error() == JSON_ERROR_NONE) {
                $parameters[$key] = $jsonDecoded;
            }
        }

        foreach ($parameters as $name => $value) {
            $filter = $this->filterStack->getByParam($name);

            if (!$filter) {
                continue;
            }

            $filter->filter($queryBuilder, $alias, $value);
        }

        // Apply query selects
        $querySelects = $this->dataSchema->getQuerySelects();
        foreach ($querySelects as $propertyName => $querySelect) {
            if ($this->dataSchema->hasProperty($propertyName)) {
                $queryBuilder->addSelect(sprintf('(%s) as %s', $querySelect, $propertyName));
            }
        }

        // Apply orderings
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