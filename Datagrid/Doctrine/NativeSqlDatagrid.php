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

use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Glavweb\DatagridBundle\Builder\Doctrine\ORM\DatagridContext;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class NativeSqlDatagrid.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class NativeSqlDatagrid extends AbstractDatagrid
{
    private readonly DataSchema $dataSchema;

    protected NativeQuery $queryCount;

    public function __construct(protected NativeQuery $query, DatagridContext $context)
    {
        $this->queryCount = clone $this->query;

        foreach ($this->query->getParameters() as $key => $parameter) {
            $this->queryCount->setParameter($key, $parameter->getValue(), $parameter->getType());
        }

        $this->dataSchema = $context->getDataSchema();
        $this->orderings = $context->transformOrderingForNativeSql($context->getOrderings());
        $this->firstResult = $context->getFirstResult();
        $this->maxResults = $context->getMaxResults();

        if ($this->dataSchema->getHydrationMode() !== null) {
            $this->setHydrationMode($this->dataSchema->getHydrationMode());
        }
    }

    public function getItem(): array
    {
        $query = $this->createQuery();

        $result = $query->getSingleResult($this->getHydrationMode());
        if ($this->dataSchema) {
            return $this->dataSchema->getData($result);
        }

        return $result;
    }

    public function getList(): array
    {
        $query = $this->createQuery();

        $result = $query->getResult($this->getHydrationMode());
        if ($this->dataSchema) {
            return $this->dataSchema->getList($result);
        }

        return $result;
    }

    public function getTotal(): int
    {
        $query = $this->queryCount;

        $sql = $query->getSQL();
        $sql = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(*) as count FROM', $sql, 1);

        $query->setSQL($sql);

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('count', 'count');

        $query->setResultSetMapping($rsm);

        return (int) $query->getSingleScalarResult();
    }

    private function createQuery(): NativeQuery
    {
        $query = $this->query;
        $sql = $query->getSQL();

        $orderings = $this->getOrderings();
        if ($orderings) {
            $orderParts = [];
            foreach ($orderings as $fieldName => $sort) {
                $orderParts[] = $fieldName.' '.$sort;
            }

            $sql .= ' ORDER BY '.implode(',', $orderParts);
        }

        $limit = $this->getMaxResults();
        if ($limit) {
            $sql .= ' LIMIT '.$limit;
        }

        $offset = $this->getFirstResult();
        if ($offset) {
            $sql .= ' OFFSET '.$offset;
        }

        $query->setSQL($sql);

        return $query;
    }
}
