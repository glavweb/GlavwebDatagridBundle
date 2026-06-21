<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder as NativeQueryBuilder;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DataSchemaBundle\DataSchema\Placeholder;

/**
 * Class AbstractQueryBuilderFactory.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractQueryBuilderFactory
{
    abstract public function create(
        array $parameters,
        string $alias,
        DataSchema $dataSchema,
        FilterStack $filterStack,
        ?JoinMap $joinMap = null,
    ): NativeQueryBuilder|QueryBuilder;

    /**
     * QueryBuilderFactory constructor.
     */
    public function __construct(protected Registry $doctrine, protected Placeholder $placeholder)
    {
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function getClassNameByDataSchema(array $config, ?string $discriminator = null): string
    {
        $class = $config['class'];

        if ($discriminator && !isset($config['discriminatorMap'][$discriminator])) {
            var_dump($config, $discriminator);
            echo self::class.': '.__LINE__;
            exit;
        }

        if ($discriminator && isset($config['discriminatorMap'][$discriminator])) {
            return $config['discriminatorMap'][$discriminator];
        }

        return $class;
    }

    protected function getClassMetadata(string $class): ClassMetadata
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        return $em->getClassMetadata($class);
    }

    protected function getClassMetadataByDataSchema(array $config, ?string $discriminator = null): ClassMetadata
    {
        return $this->getClassMetadata(
            $this->getClassNameByDataSchema($config, $discriminator)
        );
    }
}
