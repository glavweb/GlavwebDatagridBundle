<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter;

/**
 * Class FilterStack.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterStack
{
    /**
     * @var FilterInterface[]
     */
    private array $filters = [];

    private array $filterNamesByParams = [];

    /**
     * @return $this
     */
    public function add(FilterInterface $filter): static
    {
        $filterName = $filter->getName();
        $paramName = $filter->getParamName();

        $this->filters[$filterName] = $filter;
        $this->filterNamesByParams[$paramName] = $filterName;

        return $this;
    }

    public function get(string $filterName): FilterInterface
    {
        return $this->filters[$filterName];
    }

    public function getByParam(string $name): ?FilterInterface
    {
        if (!isset($this->filterNamesByParams[$name])) {
            return null;
        }

        $filterName = $this->filterNamesByParams[$name];

        return $this->get($filterName);
    }

    /**
     * @return FilterInterface[]
     */
    public function all(): array
    {
        return $this->filters;
    }
}
