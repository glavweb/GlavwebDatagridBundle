<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine\Native;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
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
    private $parameters;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $classMetadata;

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
        array $orderings = [],
        int $firstResult = 0,
        int $maxResults = null,
        string $alias = 't',
        array $parameters = []
    ) {
        $this->class         = $class;
        $this->entityManger  = $entityManger;
        $this->queryBuilder  = $queryBuilder;
        $this->dataSchema    = $dataSchema;
        $this->filterStack   = $filterStack;
        $this->orderings     = $orderings;
        $this->firstResult   = $firstResult;
        $this->maxResults    = $maxResults;
        $this->alias         = $alias;
        $this->parameters    = $parameters;
        $this->classMetadata = $entityManger->getClassMetadata($class);
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
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }
}