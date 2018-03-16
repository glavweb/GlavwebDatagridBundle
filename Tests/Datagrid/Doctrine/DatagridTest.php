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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Glavweb\DatagridBundle\Builder\Doctrine\AbstractDatagridBuilder;
use Glavweb\DatagridBundle\Builder\Doctrine\DatagridContext;
use Glavweb\DatagridBundle\Tests\WebTestCase;

/**
 * Class DatagridTest
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridTest extends WebTestCase
{
    /**
     * @var AbstractDatagridBuilder
     */
    private $datagridBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->datagridBuilder = $this->getContainer()->get('glavweb_datagrid.doctrine_datagrid_builder');
    }

    /**
     * Test datagrid data schema
     */
    public function testDatagridDataSchema()
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article')
            ->setAlias('t')
            ->setDataSchema('article/simple_data.schema.yml')
        ;

        $datagrid = $this->datagridBuilder->build();
        $list = $datagrid->getList();

        $this->assertEquals(3, count($list));

        $this->assertEquals([
            'name' => 'Article 1',
            'slug' => 'article-1',
            'body' => 'Article about Katy'
        ], $list[0]);
    }

    /**
     * Test datagrid scope
     */
    public function testDatagridScope()
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article')
            ->setAlias('t')
            ->setDataSchema('article/simple_data.schema.yml', 'article/short.yml')
        ;

        $datagrid = $this->datagridBuilder->build();
        $list = $datagrid->getList();

        $this->assertEquals(3, count($list));

        $this->assertEquals([
            'name' => 'Article 1',
        ], $list[0]);
    }

    /**
     * Test native SQL
     */
    public function testNativeSql()
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article')
            ->setAlias('t')
            ->setDataSchema('article/simple_data.schema.yml', 'article/short.yml')
        ;

        $datagrid = $this->datagridBuilder->buildNativeSql(function (DatagridContext $context) {
            $em  = $context->getEntityManger();
            $rsm = $context->getResultSetMapping();

            $sql = 'SELECT w.*  FROM (' . $context->getSql() . ') as w';
            $query = $em->createNativeQuery($sql, $rsm);

            return $query;
        });

        $list = $datagrid->getList();
        $this->assertEquals(3, count($list));

        $this->assertEquals([
            'name' => 'Article 1',
        ], $list[0]);
    }

    /**
     * Test datagrid scope
     */
    public function testJoinFirstLevel()
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Event')
            ->setAlias('t')
            ->setDataSchema('test_joins_first_level.schema.yml')
        ;

        $datagrid = $this->datagridBuilder->build();
        $list = $datagrid->getList();

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
                ['name' => 'Session 2'],
            ],
            'tags' => [
                ['name' => 'Tag 1'],
                ['name' => 'Tag 2']
            ],
        ], $list[0]);
    }

    /**
     * Test datagrid scope
     */
    public function testJoinSecondLevel()
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article')
            ->setAlias('t')
            ->setDataSchema('test_joins_second_level.schema.yml')
        ;

        $datagrid = $this->datagridBuilder->build();
        $list = $datagrid->getList();

        $this->assertEquals([
            'name' => 'Article 2',
            'events' => [
                [
                    'name' => 'Event 3',
                    'eventDetail' => [
                        'body' => 'Body for event detail 3'
                    ],
                    'eventGroup' => [
                        'name' => 'Event group 2'
                    ],
                    'sessions' => [
                        ['name' => 'Session 8'],
                        ['name' => 'Session 9'],
                        ['name' => 'Session 10'],
                        ['name' => 'Session 11'],
                        ['name' => 'Session 12'],
                        ['name' => 'Session 13'],
                        ['name' => 'Session 14'],
                        ['name' => 'Session 15'],
                    ],
                    'tags' => [
                        ['name' => 'Tag 4'],
                        ['name' => 'Tag 5']
                    ],
                ]
            ]
        ], $list[1]);
    }

    /**
     * Test datagrid filters
     *
     * @dataProvider dataTestFilters
     *
     * @param string $filterName
     * @param array  $cases
     */
    public function testDatagridFilters($filterName, $cases)
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article')
            ->setAlias('t')
            ->setDataSchema('article/simple_data.schema.yml', 'article/short.yml')
        ;

        // Define filters
        $this->datagridBuilder
            ->addFilter($filterName)
        ;

        foreach ($cases as $key => $case) {
            $datagrid = $this->datagridBuilder->build($case['params']);
            $list = $datagrid->getList();

            $message = sprintf('Filter name: %s, Case: %s', $filterName, $key);
            $this->assertEquals($case['count'], count($list), $message);
            $this->assertEquals($case['actual'], $list, $message);
        }
    }

    /**
     * Test datagrid second filters
     *
     * @dataProvider dataTestSecondFilters
     *
     * @param string $filterName
     * @param string $paramName
     * @param array  $cases
     */
    public function testDatagridSecondFilters($filterName, $paramName, $cases)
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Event')
            ->setAlias('t')
            ->setDataSchema('event/simple_data.schema.yml', 'event/short.yml')
        ;

        // Define filters
        $this->datagridBuilder
            ->addFilter($filterName, null, [
                'param_name' => $paramName
            ])
        ;

        foreach ($cases as $key => $case) {
            $datagrid = $this->datagridBuilder->build($case['params']);
            $list = $datagrid->getList();

            $message = sprintf('Filter name: %s, Case: %s', $filterName, $key);
            $this->assertEquals($case['count'], count($list), $message);
            $this->assertEquals($case['actual'], $list, $message);
        }
    }

    /**
     * Test datagrid model filters
     *
     * @dataProvider dataTestModelFilters
     *
     * @param string $filterName
     * @param string $paramName
     * @param array  $cases
     */
    public function testDatagridModelFilters($filterName, $paramName, $cases)
    {
        $this->datagridBuilder
            ->setEntityClassName('Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article')
            ->setAlias('t')
            ->setDataSchema('article/simple_data.schema.yml', 'article/short.yml')
        ;

        // Define filters
        $this->datagridBuilder
            ->addFilter($filterName, null, [
                'param_name' => $paramName
            ])
        ;

        foreach ($cases as $key => $case) {
            $entity = $this->getEntityByName($case['entity']['className'], $case['entity']['name']);
            $datagrid = $this->datagridBuilder->build([
                $case['entity']['param'] => $entity->getId()
            ]);
            $list = $datagrid->getList();

            $message = sprintf('Filter name: %s, Case: %s', $filterName, $key);
            $this->assertEquals($case['count'], count($list), $message);
            $this->assertEquals($case['actual'], $list, $message);
        }
    }

    /**
     * @return array
     */
    public function dataTestFilters()
    {
        return [
            // String filter
            [
                'filterName' => 'body',
                'cases' => [
                    [
                        'params' => ['body' => 'Katy'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                    [
                        'params' => ['body' => '=Article about Katy'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                    [
                        'params' => ['body' => '!=Article about Katy'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Article 2'],
                            ['name' => 'Article 3']
                        ]
                    ],
                    [
                        'params' => ['body' => 'Article'],
                        'count'  => 3,
                        'actual' => [
                            ['name' => 'Article 1'],
                            ['name' => 'Article 2'],
                            ['name' => 'Article 3'],
                        ]
                    ],
                    [
                        'params' => ['body' => '!Article'],
                        'count'  => 0,
                        'actual' => []
                    ],
                ]
            ],

            // Number filter
            [
                'filterName' => 'countEvents',
                'cases' => [
                    [
                        'params' => ['countEvents' => '2'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                    [
                        'params' => ['countEvents' => '!1'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Article 1'],
                            ['name' => 'Article 3']
                        ]
                    ],
                    [
                        'params' => ['countEvents' => '<2'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Article 2'],
                            ['name' => 'Article 3']
                        ]
                    ],
                    [
                        'params' => ['countEvents' => '>1'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                ]
            ],

            // Boolean filter
            [
                'filterName' => 'publish',
                'cases' => [
                    [
                        'params' => ['publish' => '1'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                    [
                        'params' => ['publish' => '0'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Article 2'],
                            ['name' => 'Article 3']
                        ]
                    ],
                    [
                        'params' => ['publish' => '!0'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                ]
            ],

            // DateTime filter
            [
                'filterName' => 'publishAt',
                'cases' => [
                    [
                        'params' => ['publishAt' => '2016-05-09 10:00'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                    [
                        'params' => ['publishAt' => '!2016-05-09 10:00'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Article 2'],
                            ['name' => 'Article 3']
                        ]
                    ],
                    [
                        'params' => ['publishAt' => '>2016-05-09 10:00'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Article 2'],
                            ['name' => 'Article 3']
                        ]
                    ],
                    [
                        'params' => ['publishAt' => '<2016-05-10'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataTestSecondFilters()
    {
        return [
            // String filter
            [
                'filterName' => 'articles.body',
                'paramName'  => 'articleBody',
                'cases' => [
                    [
                        'params' => ['articleBody' => 'Mary'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                    [
                        'params' => ['articleBody' => '=Article about Mary'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                    [
                        'params' => ['articleBody' => '!=Article about Mary'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2']
                        ]
                    ],
                    [
                        'params' => ['articleBody' => 'Article'],
                        'count'  => 3,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2'],
                            ['name' => 'Event 3']
                        ]
                    ],
                    [
                        'params' => ['articleBody' => '!Article'],
                        'count'  => 0,
                        'actual' => []
                    ],
                ]
            ],

            // Number filter
            [
                'filterName' => 'articles.countEvents',
                'paramName'  => 'articleCountEvents',
                'cases' => [
                    [
                        'params' => ['articleCountEvents' => '1'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                    [
                        'params' => ['articleCountEvents' => '!1'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2']
                        ]
                    ],
                    [
                        'params' => ['articleCountEvents' => '>1'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2']
                        ]
                    ],
                    [
                        'params' => ['articleCountEvents' => '<2'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                ]
            ],

            // Boolean filter
            [
                'filterName' => 'articles.publish',
                'paramName'  => 'articlePublish',
                'cases' => [
                    [
                        'params' => ['articlePublish' => '1'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2']
                        ]
                    ],
                    [
                        'params' => ['articlePublish' => '0'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3'],
                        ]
                    ],
                ]
            ],

            // DateTime filter
            [
                'filterName' => 'articles.publishAt',
                'paramName'  => 'articlePublishAt',
                'cases' => [
                    [
                        'params' => ['articlePublishAt' => '2016-05-10 11:00'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                    [
                        'params' => ['articlePublishAt' => '!2016-05-10 11:00'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2']
                        ]
                    ],
                    [
                        'params' => ['articlePublishAt' => '>2016-05-09 10:00'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                    [
                        'params' => ['articlePublishAt' => '<2016-05-10 10:00'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 1'],
                            ['name' => 'Event 2'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataTestModelFilters()
    {
        return [
            [
                'filterName' => 'events',
                'paramName'  => 'events',
                'cases' => [
                    [
                        'entity' => [
                            'className' => 'Event',
                            'name'      => 'Event 1',
                            'param'     => 'events',
                        ],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 1']
                        ]
                    ],
                ]
            ],
            [
                'filterName' => 'events.tags',
                'paramName'  => 'eventTag',
                'cases' => [
                    [
                        'entity' => [
                            'className' => 'Tag',
                            'name'      => 'Tag 4',
                            'param'     => 'eventTag',
                        ],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Article 2']
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * @param $entityName
     * @param string $name
     * @return object
     */
    protected function getEntityByName($entityName, $name)
    {
        /** @var Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        /** @var EntityRepository $repository */
        $repository = $doctrine->getRepository('Glavweb\DatagridBundle\Tests\Fixtures\Entity\\' . $entityName);

        return $repository->findOneBy([
            'name' => $name
        ]);
    }
}