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
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class ScopeYamlLoaderTest extends WebTestCase
{
    /**
     * testGetConfiguration
     */
    public function testGetConfiguration()
    {
        $scope = $this->getContainer()->getParameter('glavweb_datagrid.scope_dir');
        $scopeLoader = new ScopeYamlLoader(new FileLocator($scope));
        $scopeLoader->load('article/view.yml');

        $configuration = $scopeLoader->getConfiguration();

        $this->assertEquals($configuration, [
            'id'   => null,
            'name' => null,
            'slug' => null,
            'body' => null,
        ]);
    }
}