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
use Doctrine\ORM\Query;
use Glavweb\DatagridBundle\Builder\Doctrine\ORM\DatagridContext;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class NativeSqlDatagrid
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class NativeSqlDatagrid extends AbstractDatagrid
{
    /**
     * @var NativeQuery
     */
    protected $query;

    /**
     * @var DataSchema
     */
    private $dataSchema;

    /**
     * @var NativeQuery
     */
    protected $queryCount;

    /**
     * @param NativeQuery     $query
     * @param DatagridContext $context
     */
    public function __construct(NativeQuery $query, DatagridContext $context)
    {
        $this->query       = $query;
        $this->queryCount  = clone $query;
        $this->queryCount->setParameters($query->getParameters());

        $this->dataSchema  = $context->getDataSchema();
        $this->orderings   = $context->transformOrderingForNativeSql($context->getOrderings());
        $this->firstResult = $context->getFirstResult();
        $this->maxResults  = $context->getMaxResults();

        if ($this->dataSchema->getHydrationMode() !== null) {
            $this->setHydrationMode($this->dataSchema->getHydrationMode());
        }
    }

    /**
     * @return array
     */
    public function getItem()
    {
        $query = $this->createQuery();

        $result = $query->getSingleResult($this->getHydrationMode());
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
        $query = $this->createQuery();

        $result = $query->getResult($this->getHydrationMode());
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
        $query = $this->queryCount;

        $sql = $query->getSQL();
        $sql = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(*) as count FROM', $sql, 1);
        $query->setSQL($sql);

        $rsm = new Query\ResultSetMapping();
        $rsm->addScalarResult('count', 'count');
        $query->setResultSetMapping($rsm);

        return (int)$query->getSingleScalarResult();
    }

    /**
     * @return NativeQuery
     */
    private function createQuery()
    {
        $query = $this->query;
        $sql = $query->getSQL();

        $orderings = $this->getOrderings();
        if ($orderings) {
            $orderParts = [];
            foreach ($orderings as $fieldName => $sort) {
                $orderParts[] = $fieldName . ' ' . $sort;
            }

            $sql .= ' ORDER BY ' . implode(',', $orderParts);
        }

        $limit = $this->getMaxResults();
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }

        $offset = $this->getFirstResult();
        if ($offset) {
            $sql .= ' OFFSET ' . $offset;
        }

        $query->setSQL($sql);
        return $query;
    }
}