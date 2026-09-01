<?php

declare(strict_types=1);

namespace Glavweb\DatagridBundle\Datagrid;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Datagrid\Doctrine\AbstractDatagrid;

/**
 * Class SimpleSqlDatagrid.
 *
 * @author Sergey Zvyagintsev <nitron.ru@gmail.com>
 */
class SimpleSqlDatagrid extends AbstractDatagrid
{
    private Connection $connection;

    private QueryBuilder $queryBuilder;

    protected array $orderings = [];

    protected ?int $firstResult = 0;

    protected ?int $maxResults = 100;

    public function __construct(QueryBuilder $queryBuilder, Connection $connection)
    {
        $this->queryBuilder = $queryBuilder;
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function getList(): array
    {
        $queryBuilder = clone $this->queryBuilder;
        $queryBuilder
            ->setParameters($this->queryBuilder->getParameters())
            ->setFirstResult($this->firstResult)
            ->setMaxResults($this->maxResults)
        ;

        foreach ($this->getOrderings() as $fieldName => $sort) {
            $queryBuilder->addOrderBy($fieldName, $sort);
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotal(): int
    {
        $wrapperQueryBuilder = $this->connection->createQueryBuilder();

        $wrapperQueryBuilder
            ->select('COUNT(subquery.*)')
            ->from(sprintf('(%s)', $this->queryBuilder->getSQL()), 'subquery')
            ->setParameters($this->queryBuilder->getParameters(), $this->queryBuilder->getParameterTypes())
        ;

        return (int) $wrapperQueryBuilder->executeQuery()->fetchOne();
    }

    /**
     * {@inheritDoc}
     */
    public function getItem(): array
    {
        $row = $this->queryBuilder->executeQuery()->fetchAssociative();

        return false === $row ? [] : $row;
    }

    public function setOrderings(array $orderings): self
    {
        $this->orderings = $orderings;

        return $this;
    }

    public function setFirstResult(int $firstResult): self
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    public function setMaxResults(int $maxResults): self
    {
        $this->maxResults = $maxResults;

        return $this;
    }
}
