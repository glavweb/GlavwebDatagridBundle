<?php

namespace Glavweb\DatagridBundle\Doctrine\DBAL\Query;

use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;

/**
 * Class QueryBuilder.
 *
 * @author Sergey Zvyagintsev <nitron.ru@gmail.com>
 */
class QueryBuilder extends BaseQueryBuilder
{
    private array $from = [];

    /**
     * @var string[]
     */
    private array $joinAliases = [];

    #[\Override]
    public function from(string $table, ?string $alias = null): self
    {
        $this->from[$table] = $alias;

        return parent::from($table, $alias);
    }

    #[\Override]
    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        $this->joinAliases[] = $alias;

        return parent::leftJoin($fromAlias, $join, $alias, $condition);
    }

    #[\Override]
    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        $this->joinAliases[] = $alias;

        return parent::rightJoin($fromAlias, $join, $alias, $condition);
    }

    #[\Override]
    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        $this->joinAliases[] = $alias;

        return parent::innerJoin($fromAlias, $join, $alias, $condition);
    }

    public function hasFrom(): bool
    {
        return $this->from !== [];
    }

    /**
     * @return string[]
     */
    public function getJoinAliases(): array
    {
        return $this->joinAliases;
    }

    /**
     * @return string[]
     */
    public function getFromAliases(): array
    {
        return array_filter(array_values($this->from), static fn ($alias): bool => $alias !== null);
    }
}
