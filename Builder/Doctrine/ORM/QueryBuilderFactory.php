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
use Glavweb\DataSchemaBundle\DataSchema\Placeholder;
use Glavweb\DataSchemaBundle\Service\DataSchemaService;

/**
 * Class QueryBuilderFactory.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class QueryBuilderFactory extends AbstractQueryBuilderFactory
{
    /**
     * QueryBuilderFactory constructor.
     */
    public function __construct(
        Registry $doctrine,
        Placeholder $placeholder,
        private readonly JoinBuilder $joinBuilder,
        private readonly DataSchemaService $dataSchemaService,
    ) {
        parent::__construct($doctrine, $placeholder);
    }

    public function create(
        array $parameters,
        string $alias,
        DataSchema $dataSchema,
        FilterStack $filterStack,
        ?JoinMap $joinMap = null,
    ): QueryBuilder {
        /** @var EntityRepository $repository */
        $dataSchemaConfig = $dataSchema->getConfiguration();
        $repository = $this->doctrine->getRepository($dataSchemaConfig['class']);

        // Create query builder
        $queryBuilder = $repository->createQueryBuilder($alias);

        // Apply joins
        if (!empty($dataSchemaConfig['conditions'])) {
            foreach ($dataSchemaConfig['conditions'] as $conditionConfig) {
                if (!$conditionConfig['enabled']) {
                    continue;
                }

                $preparedCondition = $this->placeholder->condition($conditionConfig['condition'], $alias);
                if ($preparedCondition) {
                    $queryBuilder->andWhere($preparedCondition);
                }
            }
        }

        $joinMapFromDataSchema = $this->createJoinMap($dataSchema, $alias);

        if ($joinMapFromDataSchema) {
            if ($joinMap instanceof JoinMap) {
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

    public function createJoinMap(DataSchema $dataSchema, string $alias): JoinMap
    {
        $dataSchemaConfig = $dataSchema->getConfiguration();
        $joinMap = new JoinMap($alias, $this->getClassMetadata($dataSchemaConfig['class']));

        $joins = $this->getJoinsByConfig($dataSchema, $dataSchemaConfig, $alias);
        foreach ($joins as $fullPath => $joinData) {
            $pathElements = explode('.', (string) $fullPath);
            $field = array_pop($pathElements);
            $path = implode('.', $pathElements);

            if (($key = array_search($path, $joins, true)) !== false) {
                $path = $key;
            }

            $joinFields = $joinData['fields'];
            $joinType = $joinData['joinType'];
            $conditionType = $joinData['conditionType'];
            $condition = $joinData['condition'];

            // If any of these join fields not exist in the class -> join fields is empty
            $classMetadata = $this->getClassMetadata($joinData['class']);
            $isDifferentFields = (bool) array_diff($joinFields, $classMetadata->getFieldNames());
            if ($isDifferentFields) {
                $joinFields = [];
            }

            $joinMap->join($path, $field, true, $joinFields, $joinType, $conditionType, $condition);
        }

        return $joinMap;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $result
     */
    private function getJoinsByConfig(
        DataSchema $dataSchema,
        array $config,
        string $firstAlias,
        ?string $alias = null,
        array &$result = [],
    ): array {
        if (!$alias) {
            $alias = $firstAlias;
        }

        if (isset($config['properties'])) {
            $properties = $config['properties'];
            foreach ($properties as $key => $propertyConfig) {
                if (!empty($propertyConfig['properties'])) {
                    $joinType = isset($propertyConfig['join']) && $propertyConfig['join'] !== JoinMap::JOIN_TYPE_NONE ?
                        $propertyConfig['join'] : false;

                    if (!$joinType) {
                        continue;
                    }

                    $join = $alias.'.'.$key;
                    $joinAlias = str_replace('.', '_', $join);

                    // Join fields
                    $joinFields = $this->dataSchemaService->getDatabaseFields($propertyConfig);

                    $conditionType = $propertyConfig['conditionType'] ?? Join::WITH;
                    $conditions = $propertyConfig['conditions'] ?? [];

                    $preparedConditions = [];
                    foreach ($conditions as $conditionConfig) {
                        if (!$conditionConfig['enabled']) {
                            continue;
                        }

                        $preparedCondition = $dataSchema->conditionPlaceholder($conditionConfig['condition'], $joinAlias);
                        if ($preparedCondition) {
                            $preparedConditions[] = '('.$preparedCondition.')';
                        }
                    }

                    $condition = implode('AND', $preparedConditions);

                    $result[$join] = [
                        'class' => $propertyConfig['class'],
                        'alias' => $joinAlias,
                        'fields' => $joinFields,
                        'joinType' => $joinType,
                        'conditionType' => $conditionType,
                        'condition' => $condition,
                    ];

                    $this->getJoinsByConfig($dataSchema, $propertyConfig, $firstAlias, $joinAlias, $result);
                }
            }
        }

        return $result;
    }
}
