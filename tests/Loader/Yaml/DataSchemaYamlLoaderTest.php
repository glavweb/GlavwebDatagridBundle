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
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataSchemaYamlLoaderTest extends WebTestCase
{
    /**
     * @var DataSchemaYamlLoader
     */
    private $dataSchemaLoader;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $dataSchemaDir = $this->getContainer()->getParameter('glavweb_datagrid.data_schema_dir');
        $this->dataSchemaLoader = new DataSchemaYamlLoader(new FileLocator($dataSchemaDir));
    }

    /**
     * testGetConfiguration
     */
    public function testGetConfiguration()
    {
        $this->dataSchemaLoader->load('test_load.schema.yml');

        $configuration = $this->dataSchemaLoader->getConfiguration();

        $this->assertEquals([
            'class' => 'Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article',
            'properties' => [
                'id' => null,
                'name' => null
            ]
        ], $configuration);
    }

    /**
     * testGetConfiguration
     */
    public function testMergedConfiguration()
    {
        $this->dataSchemaLoader->load('test_merged.schema.yml');

        $configuration = $this->dataSchemaLoader->getConfiguration();

        $this->assertEquals([
            'class' => 'Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article',
            'properties' => [
                'id' => null,
                'name' => null,
                'slug' => null,
                'body' => null
            ]
        ], $configuration);
    }
}