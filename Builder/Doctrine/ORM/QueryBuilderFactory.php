<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\Builder\Doctrine\AbstractQueryBuilderFactory;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\DatagridBundle\JoinMap\Doctrine\ORM\JoinBuilder;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DataSchemaBundle\Service\DataSchemaService;
use Glavweb\DataSchemaBundle\DataSchema\Placeholder;

/**
 * Class QueryBuilderFactory
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class QueryBuilderFactory extends AbstractQueryBuilderFactory
{
    /**
     * @var JoinBuilder
     */
    private $joinBuilder;

    /**
     * @var DataSchemaService
     */
    private $dataSchemaService;

    /**
     * QueryBuilderFactory constructor.
     *
     * @param Registry          $doctrine
     * @param Placeholder       $placeholder
     * @param JoinBuilder       $joinBuilder
     * @param DataSchemaService $dataSchemaService
     */
    public function __construct(Registry $doctrine, Placeholder $placeholder, JoinBuilder $joinBuilder, DataSchemaService $dataSchemaService)
    {
        parent::__construct($doctrine, $placeholder);

        $this->joinBuilder = $joinBuilder;
        $this->dataSchemaService = $dataSchemaService;
    }

    /**
     * @param array $parameters
     * @param string $alias
     * @param DataSchema $dataSchema
     * @param FilterStack $filterStack
     * @param JoinMap|null $joinMap
     * @return QueryBuilder
     */
    public function create(array $parameters, string $alias, DataSchema $dataSchema, FilterStack $filterStack, JoinMap $joinMap = null)
    {
        /** @var EntityRepository $repository */
        $dataSchemaConfig = $dataSchema->getConfiguration();
        $repository = $this->doctrine->getRepository($dataSchemaConfig['class']);

        // Create query builder
        $queryBuilder = $repository->createQueryBuilder($alias);

        // Apply joins
        if (isset($dataSchemaConfig['conditions'])) {
            foreach ($dataSchemaConfig['conditions'] as $condition) {
                $preparedCondition = $this->placeholder->condition($condition, $alias);
                if ($preparedCondition) {
                    $queryBuilder->andWhere($preparedCondition);
                }
            }
        }

        $joinMapFromDataSchema = $this->createJoinMap($dataSchema, $alias);

        if ($joinMapFromDataSchema) {
            if ($joinMap) {
                $joinMap->merge($joinMapFromDataSchema);

            } else {
                $joinMap = $joinMapFromDataSchema;
            }
        }

        if ($joinMap) {
            $this->joinBuilder->apply($queryBuilder, $joinMap);
        }

        return $queryBuilder;
    }

    /**
     * @param DataSchema $dataSchema
     * @param string $alias
     * @return JoinMap
     */
    public function createJoinMap(DataSchema $dataSchema, $alias)
    {
        $dataSchemaConfig = $dataSchema->getConfiguration();
        $joinMap          = new JoinMap($alias, $this->getClassMetadata($dataSchemaConfig['class']));

        $joins = $this->getJoinsByConfig($dataSchema, $dataSchemaConfig, $alias);
        foreach ($joins as $fullPath => $joinData) {
            $pathElements = explode('.', $fullPath);
            $field = array_pop($pathElements);
            $path  = implode('.', $pathElements);

            if (($key = array_search($path, $joins)) !== false) {
                $path = $key;
            }

            $joinFields    = $joinData['fields'];
            $joinType      = $joinData['joinType'];
            $conditionType = $joinData['conditionType'];
            $condition     = $joinData['condition'];

            // If any of these join fields not exist in the class -> join fields is empty
            $classMetadata = $this->getClassMetadata($joinData['class']);
            $isDifferentFields = (bool)array_diff($joinFields, $classMetadata->getFieldNames());
            if ($isDifferentFields) {
                $joinFields = [];
            }

            $joinMap->join($path, $field, true, $joinFields, $joinType, $conditionType, $condition);
        }

        return $joinMap;
    }

    /**
     * @param DataSchema $dataSchema
     * @param array $config
     * @param string $firstAlias
     * @param string $alias
     * @param array $result
     * @return array
     */
    private function getJoinsByConfig(DataSchema $dataSchema, array $config, $firstAlias, $alias = null, &$result = [])
    {
        if (!$alias) {
            $alias = $firstAlias;
        }

        if (isset($config['properties'])) {
            $properties = $config['properties'];
            foreach ($properties as $key => $propertyConfig) {
                if (isset($propertyConfig['properties']) && !empty($propertyConfig['properties'])) {
                    $joinType = isset($propertyConfig['join']) && $propertyConfig['join'] !== JoinMap::JOIN_TYPE_NONE ?
                        $propertyConfig['join'] : false;

                    if (!$joinType) {
                        continue;
                    }

                    $join      = $alias . '.' . $key;
                    $joinAlias = str_replace('.', '_', $join);

                    // Join fields
                    $joinFields = $this->dataSchemaService->getDatabaseFields($propertyConfig);

                    $conditionType = $propertyConfig['conditionType'] ?? Join::WITH;
                    $conditions    = $propertyConfig['conditions'] ?? [];

                    $preparedConditions = [];
                    foreach ($conditions as $condition) {
                        if ($condition) {
                            $preparedCondition = $dataSchema->conditionPlaceholder($condition, $joinAlias);
                            if ($preparedCondition) {
                                $preparedConditions[] = '(' . $preparedCondition . ')';
                            }
                        }
                    }
                    $condition = implode('AND', $preparedConditions);

                    $result[$join] = [
                        'class'         => $propertyConfig['class'],
                        'alias'         => $joinAlias,
                        'fields'        => $joinFields,
                        'joinType'      => $joinType,
                        'conditionType' => $conditionType,
                        'condition'     => $condition,
                    ];

                    $this->getJoinsByConfig($dataSchema, $propertyConfig, $firstAlias, $joinAlias, $result);
                }
            }
        }

        return $result;
    }
}