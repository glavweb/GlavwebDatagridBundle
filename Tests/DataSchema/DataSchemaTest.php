<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Tests\DataSchema;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Glavweb\DatagridBundle\DataSchema\DataSchemaFactory;
use Glavweb\DatagridBundle\DataTransformer\DataTransformerRegistry;
use Glavweb\DatagridBundle\Persister\EntityPersister;
use Glavweb\DatagridBundle\Tests\WebTestCase;

/**
 * Class DataSchemaTest
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataSchemaTest extends WebTestCase
{
    /**
     * testGetConfigurationTest
     */
    public function testGetConfiguration()
    {
        /** @var DataSchemaFactory $dataSchemaFactory */
        $dataSchemaFactory = $this->getContainer()->get('glavweb_datagrid.data_schema_factory');
        $dataSchema = $dataSchemaFactory->createDataSchema('article.schema.yml');

        $this->assertArrayHasKey('class', $dataSchema->getConfiguration());
    }



}
