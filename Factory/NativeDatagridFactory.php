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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Glavweb\DatagridBundle\Builder\Doctrine\AbstractDatagridBuilder;
use Glavweb\DatagridBundle\Builder\Doctrine\Native\DatagridBuilder;
use Glavweb\DatagridBundle\Builder\Doctrine\Native\QueryBuilderFactory;
use Glavweb\DatagridBundle\Filter\Doctrine\Native\FilterFactory;
use Glavweb\DataSchemaBundle\DataSchema\DataSchemaFactory;

/**
 * Class NativeDatagridFactory
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class NativeDatagridFactory implements DatagridFactoryInterface
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @var DataSchemaFactory
     */
    private $dataSchemaFactory;

    /**
     * @var QueryBuilderFactory
     */
    private $queryBuilderFactory;

    /**
     * DatagridFactory constructor.
     *
     * @param Registry $doctrine
     * @param DataSchemaFactory $dataSchemaFactory
     * @param FilterFactory $filterFactory
     * @param QueryBuilderFactory $queryBuilderFactory
     */
    public function __construct(Registry $doctrine, DataSchemaFactory $dataSchemaFactory, FilterFactory $filterFactory, QueryBuilderFactory $queryBuilderFactory)
    {
        $this->doctrine = $doctrine;
        $this->filterFactory = $filterFactory;
        $this->dataSchemaFactory = $dataSchemaFactory;
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * @param string $dataSchemaFile
     * @param string|null $scopeFile
     * @return AbstractDatagridBuilder
     */
    public function createBuilder(string $dataSchemaFile, string $scopeFile = null): AbstractDatagridBuilder
    {
        $builder = new DatagridBuilder(
            $this->doctrine,
            $this->filterFactory,
            $this->dataSchemaFactory,
            $this->queryBuilderFactory
        );

        $builder->setDataSchema($dataSchemaFile, $scopeFile);

        return $builder;
    }
}
