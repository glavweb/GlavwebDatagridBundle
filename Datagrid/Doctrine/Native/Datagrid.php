<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Datagrid\Doctrine\Native;

use Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Builder\Doctrine\Native\DatagridContext;
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
     * @var FilterStack
     */
    private $filterStack;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @param DatagridContext $context
     */
    public function __construct(DatagridContext $context)
    {
        $this->queryBuilder  = $context->getQueryBuilder();
        $this->dataSchema    = $context->getDataSchema();
        $this->filterStack   = $context->getFilterStack();
        $this->orderings     = $context->getOrderings();
        $this->firstResult   = $context->getFirstResult();
        $this->maxResults    = $context->getMaxResults();
        $this->alias         = $context->getAlias();
        $this->parameters    = $context->getParameters();
        $this->classMetadata = $context->getClassMetadata();
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
     * @return string
     */
    public function getItemAsJson(): string
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $this->fixQueryBuilder($queryBuilder, 1);

        $queryBuilderWrapper = new QueryBuilder($this->queryBuilder->getConnection());
        $uniqueAlias = uniqid('row_');
        $queryBuilderWrapper->select('
            (
                SELECT row_to_json(' . $uniqueAlias . ')
                FROM (' . $queryBuilder->getSQL() . ') as ' . $uniqueAlias . '
            ) as "data"
        ');

        foreach ($queryBuilder->getParameters() as $key => $value) {
            $queryBuilderWrapper->setParameter($key, $value);
        }

        $result = $queryBuilderWrapper->execute()->fetch();

        if (!isset($result['data']) || !$result['data']) {
            return '{}';
        }

        return $result['data'];
    }

    /**
     * @return array
     */
    public function getItem(): array
    {
        $itemData = json_decode($this->getItemAsJson(), true);

        if ($this->dataSchema) {
            return $this->dataSchema->getData($itemData);
        }

        return $itemData;
    }

    /**
     * @return string
     */
    public function getListAsJson(): string
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $this->fixQueryBuilder($queryBuilder, $this->getMaxResults());

        $queryBuilderWrapper = new QueryBuilder($this->queryBuilder->getConnection());

        $uniqueAlias = uniqid('row_');
        $queryBuilderWrapper->select('
            (
                SELECT array_to_json(array_agg(row_to_json(' . $uniqueAlias . ')))
                FROM (' . $queryBuilder->getSQL() . ') as ' . $uniqueAlias . '
            ) as "data"
        ');

        foreach ($queryBuilder->getParameters() as $key => $value) {
            $queryBuilderWrapper->setParameter($key, $value);
        }

        $result = $queryBuilderWrapper->execute()->fetch();

        if (!$result['data']) {
            return '[]';
        }

        return $result['data'];
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        $listData = json_decode($this->getListAsJson(), true);

        if ($this->dataSchema) {
            return $this->dataSchema->getList($listData);
        }

        return $listData;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('COUNT(*) as count');

        $result = $queryBuilder->execute()->fetch();

        return (int)$result['count'];
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int|null $maxResults
     */
    private function fixQueryBuilder(QueryBuilder $queryBuilder, ?int $maxResults)
    {
        $alias       = $this->getAlias();
        $firstResult = $this->getFirstResult();

        $queryBuilder->setFirstResult($firstResult);

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
        }

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
                $queryBuilder->addSelect(sprintf('(%s) as "%s"', $querySelect, $propertyName));
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
            $sortColumnName = $this->classMetadata->getColumnName($fieldName);

            // If the field name have a dot
            $fieldNameParts = explode('.', $fieldName);
            if (count($fieldNameParts) > 1) {
                $sortColumnName = array_pop($fieldNameParts);

                foreach ($fieldNameParts as $fieldPart) {
                    $sortAlias = JoinMap::makeAlias($sortAlias, $fieldPart);
                }
            }

            $queryBuilder->addOrderBy($sortAlias . '.' . $sortColumnName, $order);
        }
    }
}