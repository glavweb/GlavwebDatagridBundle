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
 * Interface DatagridInterface.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface DatagridInterface
{
    public function getOrderings(): array;

    public function getFirstResult(): ?int;

    public function getMaxResults(): ?int;

    public function getItem(): array;

    public function getList(): array;

    public function getTotal(): int;
}
