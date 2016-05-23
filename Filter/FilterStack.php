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
 * Class FilterStack
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterStack
{
    /**
     * @var FilterInterface[]
     */
    private $filters = [];

    /**
     * @var array
     */
    private $filterNamesByParams = [];

    /**
     * @param FilterInterface $filter
     * @return $this
     */
    public function add(FilterInterface $filter)
    {
        $filterName = $filter->getName();
        $paramName  = $filter->getParamName();

        $this->filters[$filterName]            = $filter;
        $this->filterNamesByParams[$paramName] = $filterName;

        return $this;
    }

    /**
     * @param string $filterName
     * @return FilterInterface
     */
    public function get($filterName)
    {
        return $this->filters[$filterName];
    }

    /**
     * @param string $name
     * @return FilterInterface|null
     */
    public function getByParam($name)
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
    public function all()
    {
        return $this->filters;
    }
}