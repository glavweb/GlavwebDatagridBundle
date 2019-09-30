<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Datagrid;

/**
 * Class EmptyDatagrid
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class EmptyDatagrid implements DatagridInterface
{
    /**
     * @return array
     */
    public function getOrderings()
    {
        return [];
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return 0;
    }

    /**
     * @return int|null
     */
    public function getMaxResults()
    {
        return null;
    }

    /**
     * @return array
     */
    public function getList()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getItem()
    {
        return [];
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return 0;
    }
}