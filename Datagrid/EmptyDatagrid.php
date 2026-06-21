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
 * Class EmptyDatagrid.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class EmptyDatagrid implements DatagridInterface
{
    public function getOrderings(): array
    {
        return [];
    }

    public function getFirstResult(): int
    {
        return 0;
    }

    public function getMaxResults(): ?int
    {
        return null;
    }

    public function getList(): array
    {
        return [];
    }

    public function getItem(): array
    {
        return [];
    }

    public function getTotal(): int
    {
        return 0;
    }
}
