<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\DataSchema\DataSchema;

/**
 * Class DatagridContext
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridContext
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var EntityManager
     */
    private $entityManger;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var DataSchema
     */
    private $dataSchema;

    /**
     * @var array
     */
    private $orderings;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var array
     */
    private $resultSetMappingCache = [];

    /**
     * DatagridContext constructor.
     *
     * @param string        $class
     * @param EntityManager $entityManger
     * @param QueryBuilder  $queryBuilder
     * @param DataSchema    $dataSchema
     * @param array         $orderings
     * @param int           $firstResult
     * @param int           $maxResults
     * @param string        $alias
     */
    public function __construct($class, EntityManager $entityManger, QueryBuilder $queryBuilder, DataSchema $dataSchema = null, array $orderings = null, $firstResult = 0, $maxResults = null, $alias = 't')
    {
        $this->class        = $class;
        $this->entityManger = $entityManger;
        $this->queryBuilder = $queryBuilder;
        $this->dataSchema   = $dataSchema;
        $this->orderings    = (array)$orderings;
        $this->firstResult  = (int)$firstResult;
        $this->maxResults   = $maxResults;
        $this->alias        = $alias;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManger()
    {
        return $this->entityManger;
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
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return DataSchema
     */
    public function getDataSchema()
    {
        return $this->dataSchema;
    }

    /**
     * @return array
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return ResultSetMapping
     */
    public function getResultSetMapping()
    {
        $class = $this->class;
        $alias = $this->alias;

        $cacheKey = md5($class . '__' .$alias);
        if (!isset($this->resultSetMappingCache[$cacheKey])) {
            $this->resultSetMappingCache[$cacheKey] = $this->createResultSetMapping($class, $alias);
        }

        return $this->resultSetMappingCache[$cacheKey];
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->buildSql($this->queryBuilder, true);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param bool         $replaceSelect
     * @return string
     */
    public function buildSql(QueryBuilder $queryBuilder, $replaceSelect = true)
    {
        $sql = $queryBuilder->getQuery()->getSQL();

        if ($replaceSelect) {
            $result = preg_match('/SELECT .*? FROM [\w]* ([^ ]*)/', $sql, $matches);
            if (!$result) {
                throw new \RuntimeException('Alias not found.');
            }

            $alias = $matches[1];
            $sql = preg_replace('/SELECT .*? FROM/', 'SELECT ' . $alias . '.* FROM', $sql);
        }

        return $sql;
    }

    /**
     * @param string $class
     * @param string $alias
     * @return ResultSetMapping
     */
    protected function createResultSetMapping($class, $alias)
    {
        $em = $this->entityManger;

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult($class, $alias);

        $classMetaData = $em->getClassMetadata($class);
        $fieldNames = $classMetaData->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $rsm->addFieldResult($alias, $classMetaData->getColumnName($fieldName), $fieldName);
        }

        return $rsm;
    }

    /**
     * @param array $orderings
     * @return array
     */
    public function transformOrderingForNativeSql(array $orderings)
    {
        $rsm = $this->getResultSetMapping();
        $scalarMappings = $rsm->scalarMappings;
        foreach ($orderings as $fieldName => $sort) {
            if (($alias = array_search($fieldName, $scalarMappings)) !== false) {
                unset($orderings[$fieldName]);
                $orderings[$alias] = $sort;
            }
        }

        return $orderings;
    }
}