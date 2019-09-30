<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Factory;

use Glavweb\DatagridBundle\Builder\Doctrine\AbstractDatagridBuilder;

/**
 * Class DatagridFactoryInterface
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface DatagridFactoryInterface
{
    /**
     * @param string $dataSchemaFile
     * @param string|null $scopeFile
     * @return AbstractDatagridBuilder
     */
    public function createBuilder(string $dataSchemaFile, string $scopeFile = null): AbstractDatagridBuilder;
}
