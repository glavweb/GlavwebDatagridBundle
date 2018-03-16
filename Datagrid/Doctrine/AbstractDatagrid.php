<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Datagrid\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Glavweb\DatagridBundle\Datagrid\DatagridInterface;

/**
 * Class AbstractDatagrid
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractDatagrid implements DatagridInterface
{
    /**
     * @var array
     */
    protected $orderings;

    /**
     * @var int
     */
    protected $firstResult;

    /**
     * @var int
     */
    protected $maxResults;

    /**
     * @var int|string
     */
    protected $hydrationMode = AbstractQuery::HYDRATE_ARRAY;

    /**
     * @return array
     */
    abstract public function getList();

    /**
     * @return mixed
     */
    abstract public function getTotal();

    /**
     * @return array
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @return int|string
     */
    public function getHydrationMode()
    {
        return $this->hydrationMode;
    }

    /**
     * @param int|string $hydrationMode
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->hydrationMode = $hydrationMode;
    }
}