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
use Glavweb\DatagridBundle\Builder\Doctrine\ORM\DatagridBuilder;
use Glavweb\DatagridBundle\Builder\Doctrine\ORM\QueryBuilderFactory;
use Glavweb\DatagridBundle\Filter\Doctrine\ORM\FilterFactory;
use Glavweb\DataSchemaBundle\DataSchema\DataSchemaFactory;

/**
 * Class ORMDatagridFactory.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ORMDatagridFactory implements DatagridFactoryInterface
{
    /**
     * DatagridFactory constructor.
     */
    public function __construct(
        private readonly Registry $doctrine,
        private readonly DataSchemaFactory $dataSchemaFactory,
        private readonly FilterFactory $filterFactory,
        private readonly QueryBuilderFactory $queryBuilderFactory,
    ) {
    }

    public function createBuilder(string $dataSchemaFile, ?string $scopeFile = null, ?string $propertyPath = null): AbstractDatagridBuilder
    {
        $builder = new DatagridBuilder(
            $this->doctrine,
            $this->filterFactory,
            $this->dataSchemaFactory,
            $this->queryBuilderFactory
        );

        $builder->setDataSchema($dataSchemaFile, $scopeFile, $propertyPath);

        return $builder;
    }
}
