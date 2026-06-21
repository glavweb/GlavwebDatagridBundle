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

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Glavweb\DatagridBundle\Builder\Doctrine\Native\DatagridContext;
use Glavweb\DatagridBundle\Datagrid\Doctrine\AbstractDatagrid;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class Datagrid.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class Datagrid extends AbstractDatagrid
{
    private readonly QueryBuilder $queryBuilder;

    private readonly DataSchema $dataSchema;

    private readonly string $alias;

    private readonly FilterStack $filterStack;

    /**
     * @var mixed[]
     */
    private readonly array $parameters;

    private readonly EntityManager $entityManager;

    public function __construct(DatagridContext $context)
    {
        $this->queryBuilder = $context->getQueryBuilder();
        $this->entityManager = $context->getEntityManger();
        $this->dataSchema = $context->getDataSchema();
        $this->filterStack = $context->getFilterStack();
        $this->orderings = $context->getOrderings();
        $this->firstResult = $context->getFirstResult();
        $this->maxResults = $context->getMaxResults();
        $this->alias = $context->getAlias();
        $this->parameters = $context->getParameters();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @throws Exception
     */
    public function getItemAsJson(): string
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $this->fixQueryBuilder($queryBuilder, 1);

        $queryBuilderWrapper = new QueryBuilder($this->entityManager->getConnection());
        $uniqueAlias = uniqid('row_', false);
        $queryBuilderWrapper->select(
            '
            (
                SELECT row_to_json('.$uniqueAlias.')
                FROM ('.$queryBuilder->getSQL().') as '.$uniqueAlias.'
            ) as "data"
        '
        );

        foreach ($queryBuilder->getParameters() as $key => $value) {
            $queryBuilderWrapper->setParameter($key, $value, $queryBuilder->getParameterType($key));
        }

        $result = $queryBuilderWrapper->fetchAssociative();

        if (!isset($result['data']) || !$result['data']) {
            return '{}';
        }

        return $result['data'];
    }

    public function getItem(): array
    {
        $itemData = json_decode($this->getItemAsJson(), true);

        if ($this->dataSchema) {
            return $this->dataSchema->getData($itemData);
        }

        return $itemData;
    }

    /**
     * @throws Exception
     */
    public function getListAsJson(): string
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $this->fixQueryBuilder($queryBuilder, $this->getMaxResults());

        $queryBuilderWrapper = new QueryBuilder($this->entityManager->getConnection());

        $uniqueAlias = uniqid('row_', false);
        $queryBuilderWrapper->select(
            '
            (
                SELECT array_to_json(array_agg(row_to_json('.$uniqueAlias.')))
                FROM ('.$queryBuilder->getSQL().') as '.$uniqueAlias.'
            ) as "data"
        '
        );

        foreach ($queryBuilder->getParameters() as $key => $value) {
            $queryBuilderWrapper->setParameter($key, $value, $queryBuilder->getParameterType($key));
        }

        $result = $queryBuilderWrapper->fetchAssociative();

        if (!$result['data']) {
            return '[]';
        }

        return $result['data'];
    }

    public function getList(): array
    {
        $listData = json_decode($this->getListAsJson(), true);

        if ($this->dataSchema) {
            return $this->dataSchema->getList($listData);
        }

        return $listData;
    }

    /**
     * @throws Exception
     */
    public function getTotal(): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('COUNT(*) as count');

        // Apply filter
        $this->applyFilter($queryBuilder);

        $result = $queryBuilder->fetchAssociative();

        return (int) $result['count'];
    }

    private function fixQueryBuilder(QueryBuilder $queryBuilder, ?int $maxResults): void
    {
        $alias = $this->getAlias();
        $firstResult = $this->getFirstResult();

        $queryBuilder->setFirstResult($firstResult);

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
        }

        $this->applyFilter($queryBuilder);

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
            $propertyConfig = $this->dataSchema->getPropertyConfiguration($fieldName);
            $sortColumnName = $propertyConfig['field_db_name'];

            // If the field name have a dot
            $fieldNameParts = explode('.', (string) $fieldName);
            if (\count($fieldNameParts) > 1) {
                array_pop($fieldNameParts);

                foreach ($fieldNameParts as $fieldPart) {
                    $sortAlias = JoinMap::makeAlias($sortAlias, $fieldPart);
                }
            }

            $queryBuilder->addOrderBy($sortAlias.'.'.$sortColumnName, $order);
        }
    }

    private function applyFilter(QueryBuilder $queryBuilder): void
    {
        $alias = $this->getAlias();
        $parameters = $this->clearParameters($this->parameters);
        foreach ($parameters as $key => $parameter) {
            if (!$parameter) {
                continue;
            }

            if (!\is_scalar($parameter)) {
                continue;
            }

            $jsonDecoded = json_decode($parameter);

            if (json_last_error() === \JSON_ERROR_NONE) {
                $parameters[$key] = $jsonDecoded;
            }
        }

        foreach ($parameters as $name => $value) {
            $filter = $this->filterStack->getByParam($name);

            if (!$filter instanceof FilterInterface) {
                continue;
            }

            $filter->filter($queryBuilder, $alias, $value);
        }
    }
}
