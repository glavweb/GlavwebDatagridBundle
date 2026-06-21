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

    /**
     * @var mixed[]
     */
    private array $queryHints = [];

    private ?Paginator $paginator = null;

    private readonly FilterStack $filterStack;

    /**
     * @var mixed[]
     */
    private readonly array $parameters;

    public function __construct(DatagridContext $context)
    {
        $this->queryBuilder = $context->getQueryBuilder();
        $this->dataSchema = $context->getDataSchema();
        $this->filterStack = $context->getFilterStack();
        $this->orderings = $context->getOrderings();
        $this->firstResult = $context->getFirstResult();
        $this->maxResults = $context->getMaxResults();
        $this->alias = $context->getAlias();
        $this->parameters = $context->getParameters();

        if ($this->dataSchema->getHydrationMode() !== null) {
            $this->setHydrationMode($this->dataSchema->getHydrationMode());
        }
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
     * @return mixed[]
     */
    public function getQueryHints(): array
    {
        return $this->queryHints;
    }

    public function setQueryHints(array $queryHints): void
    {
        $this->queryHints = $queryHints;
    }

    public function setQueryHint(string $name, mixed $value): void
    {
        $this->queryHints[$name] = $value;
    }

    public function getItem(): array
    {
        $query = $this->createQuery(null);

        $query->setHydrationMode($this->getHydrationMode());
        $this->setHintsToQuery($query);

        $result = $query->getSingleResult();
        if ($this->dataSchema) {
            return $this->dataSchema->getData($result);
        }

        return $result;
    }

    public function getList(): array
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

    public function getTotal(): int
    {
        $paginator = $this->getPaginator();

        return $paginator->count();
    }

    protected function setHintsToQuery(Query $query): void
    {
        $queryHints = $this->getQueryHints();
        foreach ($queryHints as $hintName => $hintValue) {
            $query->setHint($hintName, $hintValue);
        }
    }

    protected function getPaginator(): ?Paginator
    {
        if (!$this->paginator instanceof Paginator) {
            $query = $this->createQuery($this->getMaxResults());

            $this->paginator = new Paginator($query);
        }

        return $this->paginator;
    }

    private function createQuery(?int $maxResults): Query
    {
        $queryBuilder = $this->getQueryBuilder();
        $alias = $this->getAlias();
        $firstResult = $this->getFirstResult();

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
        }

        $queryBuilder->setFirstResult($firstResult);
        $queryBuilder->setMaxResults($maxResults);

        // Apply filter
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

        // Apply query selects
        $querySelects = $this->dataSchema->getQuerySelects();
        foreach ($querySelects as $propertyName => $querySelect) {
            if ($this->dataSchema->hasProperty($propertyName)) {
                $queryBuilder->addSelect(\sprintf('(%s) as %s', $querySelect, $propertyName));
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
            $fieldNameParts = explode('.', (string) $fieldName);
            if (\count($fieldNameParts) > 1) {
                $sortFieldName = array_pop($fieldNameParts);

                foreach ($fieldNameParts as $fieldPart) {
                    $sortAlias = JoinMap::makeAlias($sortAlias, $fieldPart);
                }
            }

            $queryBuilder->addOrderBy($sortAlias.'.'.$sortFieldName, $order);
        }

        return $queryBuilder->getQuery();
    }
}
