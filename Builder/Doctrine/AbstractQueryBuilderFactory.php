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
use Doctrine\DBAL\Query\QueryBuilder as NativeQueryBuilder;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class AbstractQueryBuilderFactory
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractQueryBuilderFactory
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param array $parameters
     * @param string $alias
     * @param DataSchema $dataSchema
     * @param FilterStack $filterStack
     * @param JoinMap|null $joinMap
     * @return NativeQueryBuilder|QueryBuilder
     */
    abstract public function create(array $parameters, string $alias, DataSchema $dataSchema, FilterStack $filterStack, JoinMap $joinMap = null);

    /**
     * QueryBuilderFactory constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param $parameters
     * @return array
     */
    protected function clearParameters(array $parameters)
    {
        $parameters = array_filter($parameters, function ($value) {
            if (is_array($value) && empty($value)) {
                return false;
            }

            return $value !== null;
        });

        return $parameters;
    }

    /**
     * @param array $config
     * @param string|null $discriminator
     * @return string
     */
    protected function getClassNameByDataSchema(array $config, string $discriminator = null): string
    {
        $class = $config['class'];

        if ($discriminator && !isset($config['discriminatorMap'][$discriminator])) {
            var_dump($config, $discriminator); echo __CLASS__ . ': ' . __LINE__; exit;
        }

        if ($discriminator && isset($config['discriminatorMap'][$discriminator])) {
            $class = $config['discriminatorMap'][$discriminator];
        }

        return $class;
    }

    /**
     * @param string $class
     * @return ClassMetadata
     */
    protected function getClassMetadata(string $class): ClassMetadata
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        return $em->getClassMetadata($class);
    }

    /**
     * @param array $config
     * @param string|null $discriminator
     * @return ClassMetadata
     */
    protected function getClassMetadataByDataSchema(array $config, string $discriminator = null): ClassMetadata
    {
        return $this->getClassMetadata(
            $this->getClassNameByDataSchema($config, $discriminator)
        );
    }
}
