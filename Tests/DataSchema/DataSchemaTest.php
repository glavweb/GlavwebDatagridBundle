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
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
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
     * @var DataSchemaFactory
     */
    private $dataSchemaFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dataSchemaFactory = $this->getContainer()->get('glavweb_datagrid.data_schema_factory');
    }

    /**
     * testGetConfiguration
     */
    public function testGetConfiguration()
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema('test_load.schema.yml');

        $this->assertArrayHasKey('class', $dataSchema->getConfiguration());
    }

    /**
     * testSimpleData
     */
    public function testSimpleData()
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema('simple_data.schema.yml');
        $articleData = $this->getArticleDataByName('Article 1');

        $this->assertEquals($articleData, $dataSchema->getData($articleData));
    }

    /**
     * testJoinsFirstLevel
     */
    public function testEmbedsFirstLevel()
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema('test_embeds_first_level.schema.yml');
        $eventData = $this->getEventDataByName('Event 1');

        $this->assertEquals([
            'name' => 'Event 1',

            'eventDetail' => [
                'body' => 'Body for event detail 1'
            ],

            'eventGroup' => [
                'name' => 'Event group 1'
            ],

            'sessions' => [
                ['name' => 'Session 1'],
                ['name' => 'Session 2']
            ],

            'tags' => [
                ['name' => 'Tag 1'],
                ['name' => 'Tag 2']
            ]

        ], $dataSchema->getData($eventData));
    }

    /**
     * testJoinsFirstLevel
     */
    public function testEmbedsSecondLevel()
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema('test_embeds_second_level.schema.yml');
        $articleData = $this->getArticleDataByName('Article 1');

        $this->assertEquals([
            'name' => 'Article 1',
            'events' => [
                [
                    'name' => 'Event 1',
                    'eventDetail' => [
                        'body' => 'Body for event detail 1'
                    ],

                    'eventGroup' => [
                        'name' => 'Event group 1'
                    ],

                    'sessions' => [
                        ['name' => 'Session 1'],
                        ['name' => 'Session 2']
                    ],

                    'tags' => [
                        ['name' => 'Tag 1'],
                        ['name' => 'Tag 2']
                    ]
                ],
                [
                    'name' => 'Event 2',
                    'eventDetail' => [
                        'body' => 'Body for event detail 2'
                    ],

                    'eventGroup' => [
                        'name' => 'Event group 1'
                    ],

                    'sessions' => [
                        ['name' => 'Session 3'],
                        ['name' => 'Session 4'],
                        ['name' => 'Session 5'],
                        ['name' => 'Session 6'],
                        ['name' => 'Session 7']
                    ],

                    'tags' => [
                        ['name' => 'Tag 3']
                    ]
                ]
            ]

        ], $dataSchema->getData($articleData));
    }

    /**
     * @param string $name
     * @return array
     * @throws \Exception
     */
    protected function getArticleDataByName($name)
    {
        /** @var Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        /** @var EntityRepository $articleRepository */
        $articleRepository = $doctrine->getRepository('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article');

        $qb = $articleRepository->createQueryBuilder('t')
            ->where('t.name = :name')
            ->setParameter('name', $name)
        ;

        $data = (array)$qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->getOneOrNullResult();

        return $data;
    }

    /**
     * @param string $name
     * @return array
     * @throws \Exception
     */
    protected function getEventDataByName($name)
    {
        /** @var Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        /** @var EntityRepository $eventRepository */
        $eventRepository = $doctrine->getRepository('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Event');

        $qb = $eventRepository->createQueryBuilder('t')
            ->where('t.name = :name')
            ->setParameter('name', $name)
        ;

        $data = (array)$qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->getOneOrNullResult();

        return $data;
    }

}
