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
use Sonata\AdminBundle\Filter\FilterInterface;

/**
 * Interface DatagridBuilderInterface
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface DatagridBuilderInterface
{
    /**
     * @param array $orderings
     * @return $this
     */
    public function setOrderings($orderings);

    /**
     * @return array
     */
    public function getOrderings();

    /**
     * @param int $firstResult
     *
     * @return $this
     */
    public function setFirstResult($firstResult);

    /**
     * @return int
     */
    public function getFirstResult();

    /**
     * @param int $maxResults
     *
     * @return $this
     */
    public function setMaxResults($maxResults);

    /**
     * @return int
     */
    public function getMaxResults();

    /**
     * @param array $operators
     * @return $this
     */
    public function setOperators(array $operators);

    /**
     * @return array
     */
    public function getOperators();

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias($alias);

    /**
     * @return string
     */
    public function getAlias();

    /**
     * @param FilterInterface[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters = []);

    /**
     * @return FilterInterface[]
     */
    public function getFilters();

    /**
     * @param string $filterName
     * @return FilterInterface
     */
    public function getFilter($filterName);

    /**
     * @param string $filterName
     * @param string $type
     * @param array $options
     * @return $this
     */
    public function addFilter($filterName, $type = null, $options = []);

    /**
     * @param array $parameters
     * @return DatagridInterface
     */
//    public function build(array $parameters = []);
}