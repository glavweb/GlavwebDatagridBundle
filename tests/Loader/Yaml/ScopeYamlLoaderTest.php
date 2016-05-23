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

use Glavweb\DatagridBundle\Loader\Yaml\ScopeYamlLoader;
use Glavweb\DatagridBundle\Tests\WebTestCase;
use Symfony\Component\Config\FileLocator;

/**
 * Class ScopeYamlLoaderTest
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class ScopeYamlLoaderTest extends WebTestCase
{
    /**
     * @var ScopeYamlLoader
     */
    private $scopeLoader;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $scope = $this->getContainer()->getParameter('glavweb_datagrid.scope_dir');
        $this->scopeLoader = new ScopeYamlLoader(new FileLocator($scope));
    }

    /**
     * testGetConfiguration
     */
    public function testGetConfiguration()
    {
        $this->scopeLoader->load('article/list.yml');

        $configuration = $this->scopeLoader->getConfiguration();

        $this->assertEquals([
            'id'   => null,
            'name' => null,
        ], $configuration);
    }

    /**
     * testGetConfiguration
     */
    public function testMergedConfiguration()
    {
        $this->scopeLoader->load('article/view.yml');

        $configuration = $this->scopeLoader->getConfiguration();

        $this->assertEquals([
            'id' => null,
            'name' => null,
            'slug' => null,
            'body' => null
        ], $configuration);
    }
}