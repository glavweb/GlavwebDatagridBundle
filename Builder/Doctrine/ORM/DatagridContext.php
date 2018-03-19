<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DatagridBundle\Filter\FilterStack;

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
     * @var FilterStack
     */
    private $filterStack;

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
     * @var array
     */
    private $parameters;

    /**
     * DatagridContext constructor.
     *
     * @param string $class
     * @param EntityManager $entityManger
     * @param QueryBuilder $queryBuilder
     * @param FilterStack $filterStack
     * @param DataSchema $dataSchema
     * @param array $orderings
     * @param int $firstResult
     * @param int $maxResults
     * @param string $alias
     * @param array $parameters
     */
    public function __construct(
        $class,
        EntityManager $entityManger,
        QueryBuilder $queryBuilder,
        FilterStack $filterStack,
        DataSchema $dataSchema,
        array $orderings = null,
        int $firstResult = 0,
        int $maxResults = null,
        string $alias = 't',
        array $parameters = []
    ) {
        $this->class        = $class;
        $this->entityManger = $entityManger;
        $this->queryBuilder = $queryBuilder;
        $this->dataSchema   = $dataSchema;
        $this->filterStack  = $filterStack;
        $this->orderings    = $orderings;
        $this->firstResult  = $firstResult;
        $this->maxResults   = $maxResults;
        $this->alias        = $alias;
        $this->parameters   = $parameters;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManger(): EntityManager
    {
        return $this->entityManger;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @return FilterStack
     */
    public function getFilterStack(): FilterStack
    {
        return $this->filterStack;
    }

    /**
     * @return DataSchema
     */
    public function getDataSchema(): DataSchema
    {
        return $this->dataSchema;
    }

    /**
     * @return array
     */
    public function getOrderings(): array
    {
        return $this->orderings;
    }

    /**
     * @return int
     */
    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    /**
     * @return int|null
     */
    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return ResultSetMapping
     */
    public function getResultSetMapping(): ResultSetMapping
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
    public function getSql(): string
    {
        return $this->buildSql($this->queryBuilder, true);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param bool         $replaceSelect
     * @return string
     */
    public function buildSql(QueryBuilder $queryBuilder, $replaceSelect = true): string
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
    protected function createResultSetMapping($class, $alias): ResultSetMapping
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
    public function transformOrderingForNativeSql(array $orderings): array
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