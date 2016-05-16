<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Tests\Loader\Yaml;

use Glavweb\DatagridBundle\Loader\Yaml\DataSchemaYamlLoader;
use Glavweb\DatagridBundle\Tests\WebTestCase;
use Symfony\Component\Config\FileLocator;

/**
 * Class DataSchemaYamlLoaderTest
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataSchemaYamlLoaderTest extends WebTestCase
{
    /**
     * testGetConfiguration
     */
    public function testGetConfiguration()
    {
        $dataSchemaDir = $this->getContainer()->getParameter('glavweb_datagrid.data_schema_dir');
        $dataSchemaLoader = new DataSchemaYamlLoader(new FileLocator($dataSchemaDir));
        $dataSchemaLoader->load('test_load.schema.yml');

        $configuration = $dataSchemaLoader->getConfiguration();

        $this->assertEquals($configuration, [
            'class' => 'AppBundle\Entity\Article',
            'properties' => [
                'id' => null,
                'name' => null
            ]
        ]);
    }
}