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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class DatagridContext.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridContext
{
    private readonly ClassMetadata $classMetadata;

    /**
     * DatagridContext constructor.
     */
    public function __construct(
        private readonly string $class,
        private readonly EntityManager $entityManger,
        private readonly QueryBuilder $queryBuilder,
        private readonly FilterStack $filterStack,
        private readonly DataSchema $dataSchema,
        private readonly array $orderings = [],
        private readonly int $firstResult = 0,
        private readonly ?int $maxResults = null,
        private readonly string $alias = 't',
        private readonly array $parameters = [],
    ) {
        $this->classMetadata = $this->entityManger->getClassMetadata($this->class);
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

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }
}
