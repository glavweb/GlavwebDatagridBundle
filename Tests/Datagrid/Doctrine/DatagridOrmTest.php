<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Tests\Datagrid\Doctrine;

use Glavweb\DatagridBundle\Factory\DatagridFactoryInterface;

/**
 * Class DatagridOrmTest
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridOrmTest extends DatagridNativeTest
{
    /**
     * @return DatagridFactoryInterface
     */
    protected function getDatagridFactory(): DatagridFactoryInterface
    {
        /** @var DatagridFactoryInterface $factory */
        $factory = $this->getContainer()->get('glavweb_datagrid.orm_factory');

        return $factory;
    }

    /**
     * @return array
     */
    public function dataTestDecodeWithQuerySelects()
    {
        return [
            [
                'dataSchemaFile' => 'test_decode_with_query_selects_orm.schema.yml'
            ]
        ];
    }
}