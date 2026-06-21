<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder;

use Glavweb\DatagridBundle\Datagrid\DatagridInterface;
use Glavweb\DatagridBundle\Filter\FilterInterface;

/**
 * Interface DatagridBuilderInterface.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface DatagridBuilderInterface
{
    /**
     * @return $this
     */
    public function setOrderings(array $orderings): static;

    public function getOrderings(): array;

    /**
     * @return $this
     */
    public function setFirstResult(int $firstResult): static;

    public function getFirstResult(): ?int;

    /**
     * @return $this
     */
    public function setMaxResults(int $maxResults): static;

    public function getMaxResults(): ?int;

    /**
     * @return $this
     */
    public function setOperators(array $operators): static;

    public function getOperators(): array;

    /**
     * @return $this
     */
    public function setAlias(string $alias): static;

    public function getAlias(): string;

    /**
     * @param FilterInterface[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters = []): static;

    /**
     * @return FilterInterface[]
     */
    public function getFilters(): array;

    public function getFilter(string $filterName): FilterInterface;

    /**
     * @return $this
     */
    public function addFilter(string $filterName, ?string $type = null, array $options = []): static;

    public function build(array $parameters = [], ?\Closure $callback = null): DatagridInterface;
}
