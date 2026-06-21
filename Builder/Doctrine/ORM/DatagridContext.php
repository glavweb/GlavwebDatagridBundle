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
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class DatagridContext.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridContext
{
    private array $resultSetMappingCache = [];

    /**
     * DatagridContext constructor.
     */
    public function __construct(
        private readonly string $class,
        private readonly EntityManager $entityManger,
        private readonly QueryBuilder $queryBuilder,
        private readonly FilterStack $filterStack,
        private readonly DataSchema $dataSchema,
        private readonly ?array $orderings = null,
        private readonly int $firstResult = 0,
        private readonly ?int $maxResults = null,
        private readonly string $alias = 't',
        private readonly array $parameters = [],
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getEntityManger(): EntityManager
    {
        return $this->entityManger;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getFilterStack(): FilterStack
    {
        return $this->filterStack;
    }

    public function getDataSchema(): DataSchema
    {
        return $this->dataSchema;
    }

    public function getOrderings(): array
    {
        return $this->orderings;
    }

    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getResultSetMapping(): ResultSetMapping
    {
        $class = $this->class;
        $alias = $this->alias;

        $cacheKey = md5($class.'__'.$alias);
        if (!isset($this->resultSetMappingCache[$cacheKey])) {
            $this->resultSetMappingCache[$cacheKey] = $this->createResultSetMapping($class, $alias);
        }

        return $this->resultSetMappingCache[$cacheKey];
    }

    public function getSql(): string
    {
        return $this->buildSql($this->queryBuilder, true);
    }

    public function buildSql(QueryBuilder $queryBuilder, bool $replaceSelect = true): string
    {
        $sql = $queryBuilder->getQuery()->getSQL();

        if ($replaceSelect) {
            $result = preg_match('/SELECT .*? FROM [\w]* ([^ ]*)/', $sql, $matches);
            if (!$result) {
                throw new \RuntimeException('Alias not found.');
            }

            $alias = $matches[1];
            $sql = preg_replace('/SELECT .*? FROM/', 'SELECT '.$alias.'.* FROM', $sql);
        }

        return $sql;
    }

    protected function createResultSetMapping(string $class, string $alias): ResultSetMapping
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

    public function transformOrderingForNativeSql(array $orderings): array
    {
        $rsm = $this->getResultSetMapping();
        $scalarMappings = $rsm->scalarMappings;
        foreach ($orderings as $fieldName => $sort) {
            if (($alias = array_search($fieldName, $scalarMappings, true)) !== false) {
                unset($orderings[$fieldName]);
                $orderings[$alias] = $sort;
            }
        }

        return $orderings;
    }
}
