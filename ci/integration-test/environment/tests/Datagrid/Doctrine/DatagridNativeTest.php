<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Datagrid\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Glavweb\DatagridBundle\Builder\Doctrine\AbstractDatagridBuilder;
use Glavweb\DatagridBundle\Factory\DatagridFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class DatagridNativeTest
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridNativeTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @return DatagridFactoryInterface
     */
    protected function getDatagridFactory(): DatagridFactoryInterface
    {
        /** @var DatagridFactoryInterface $factory */
        $factory = self::$container->get('glavweb_datagrid.native_factory');

        return $factory;
    }

    /**
     * Test datagrid data schema
     */
    public function testDatagridDataSchema()
    {
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'article/simple_data.schema.yml'
        );

        $datagrid = $datagridBuilder->build();
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
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'article/simple_data.schema.yml',
            'article/short.yml'
        );

        $datagrid = $datagridBuilder->build();
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
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'test_joins_first_level.schema.yml'
        );

        $datagrid = $datagridBuilder->build();
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
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'test_joins_second_level.schema.yml'
        );

        $datagrid = $datagridBuilder->build();
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
     * Test simple decode
     *
     * @dataProvider dataTestDecodeWithQuerySelects
     * @param string $dataSchemaFile
     */
    public function testDecodeWithQuerySelects($dataSchemaFile)
    {
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            $dataSchemaFile
        );

        $datagrid = $datagridBuilder->build();
        $list = $datagrid->getList();

        $this->assertEquals([
            'name' => 'ARTICLE 1',
            'allEvents' => 3,
            'slugWithYear' => 'article-1_2016',
            'hasEvents' => true
        ], $list[0]);
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
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'article/simple_data.schema.yml', 
            'article/short.yml'
        );

        // Define filters
        $datagridBuilder
            ->addFilter($filterName)
        ;

        foreach ($cases as $key => $case) {
            $datagrid = $datagridBuilder->build($case['params']);
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
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'event/simple_data.schema.yml',
            'event/short.yml'
        );

        // Define filters
        $datagridBuilder
            ->addFilter($filterName, null, [
                'param_name' => $paramName
            ])
        ;

        foreach ($cases as $key => $case) {
            $datagrid = $datagridBuilder->build($case['params']);
            $list = $datagrid->getList();

            $message = sprintf('Filter name: %s, Case: %s', $filterName, $key);
            $this->assertEquals($case['count'], count($list), $message);
            $this->assertEquals($case['actual'], $list, $message);
        }
    }

    /**
     * Test datagrid second filters
     *
     * @dataProvider dataTestJoinedFilters
     *
     * @param string $filterName
     * @param string $paramName
     * @param array  $cases
     */
    public function testDatagridJoinedFilters($filterName, $paramName, $cases)
    {
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'event/simple_data.schema.yml',
            'event/short.yml'
        );

        // Define filters
        $datagridBuilder
            ->addFilter($filterName, null, [
                'param_name' => $paramName
            ])
            ->setOrderings(['name' => 'desc'])
        ;

        foreach ($cases as $key => $case) {
            $datagrid = $datagridBuilder->build($case['params']);
            $list = $datagrid->getList();

            $message = sprintf('Filter name: %s, Case: %s', $filterName, $key);
            $this->assertEquals($case['count'], count($list), $message);
            $this->assertEquals($case['actual'], $list, $message);
        }
    }

    /**
     * Test datagrid filters
     */
    public function testDatagridOneToOneJoinedFilters()
    {
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'event_detail/simple_data.schema.yml',
            'event_detail/short.yml'
        );

        // Define filters
        $datagridBuilder
            ->addFilter('event.name', null, [
                'param_name' => 'eventName'
            ])
        ;

        $datagrid = $datagridBuilder->build([
            'eventName' => 'Event 1'
        ]);

        $list = $datagrid->getList();

        $this->assertEquals($list, [
            ['body' => 'Body for event detail 1']
        ]);
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
        /** @var AbstractDatagridBuilder $datagridBuilder */
        $datagridBuilder = $this->getDatagridFactory()->createBuilder(
            'article/simple_data.schema.yml',
            'article/short.yml'
        );

        // Define filters
        $datagridBuilder
            ->addFilter($filterName, null, [
                'param_name' => $paramName
            ])
        ;

        foreach ($cases as $key => $case) {
            $entity = $this->getEntityByName($case['entity']['className'], $case['entity']['name']);
            $datagrid = $datagridBuilder->build([
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
    public function dataTestDecodeWithQuerySelects()
    {
        return [
            [
                'dataSchemaFile' => 'test_decode_with_query_selects.schema.yml'
            ]
        ];
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
    public function dataTestJoinedFilters()
    {
        return [
            // ManyToMany inversed side
            [
                'filterName' => 'articles.name',
                'paramName'  => 'articleName',
                'cases' => [
                    [
                        'params' => ['articleName' => '=Article 2'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 3']
                        ]
                    ],
                ]
            ],

            // OneToMany
            [
                'filterName' => 'sessions.name',
                'paramName'  => 'sessionName',
                'cases' => [
                    [
                        'params' => ['sessionName' => '=Session 1'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 1']
                        ]
                    ],
                ]
            ],

            // ManyToOne
            [
                'filterName' => 'eventGroup.name',
                'paramName'  => 'eventGroupName',
                'cases' => [
                    [
                        'params' => ['eventGroupName' => '=Event group 1'],
                        'count'  => 2,
                        'actual' => [
                            ['name' => 'Event 2'],
                            ['name' => 'Event 1'],
                        ]
                    ],
                ]
            ],

            // OneToOne owning side
            [
                'filterName' => 'eventDetail.body',
                'paramName'  => 'eventDetailBody',
                'cases' => [
                    [
                        'params' => ['eventDetailBody' => '=Body for event detail 1'],
                        'count'  => 1,
                        'actual' => [
                            ['name' => 'Event 1'],
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
                'filterName' => 'events.id',
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
                'filterName' => 'events.tags.id',
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
        $doctrine = self::$container->get('doctrine');

        /** @var EntityRepository $repository */
        $repository = $doctrine->getRepository('App\Entity\\' . $entityName);

        return $repository->findOneBy([
            'name' => $name
        ]);
    }
}