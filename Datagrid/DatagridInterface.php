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
 * Interface DatagridInterface
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface DatagridInterface
{
    /**
     * @return array
     */
    public function getList();

    /**
     * @return int
     */
    public function getTotal();
}