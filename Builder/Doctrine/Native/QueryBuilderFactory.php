<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine\Native;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Glavweb\DatagridBundle\Builder\Doctrine\AbstractQueryBuilderFactory;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class QueryBuilderFactory.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class QueryBuilderFactory extends AbstractQueryBuilderFactory
{
    public function create(
        array $parameters,
        string $alias,
        DataSchema $dataSchema,
        FilterStack $filterStack,
        ?JoinMap $joinMap = null,
    ): QueryBuilder {
        return $this->createQueryBuilderBySchema(
            $dataSchema->getConfiguration(),
            $alias,
            [],
            [],
            [],
            true
        );
    }

    /**
     * @param array<string, mixed> $dataSchemaConfig
     */
    private function createQueryBuilderBySchema(
        array $dataSchemaConfig,
        string $alias,
        array $inConditions = [],
        array $inJoins = [],
        array $inOrderBy = [],
        bool $hasFrom = true,
    ): QueryBuilder {
        /** @var EntityManager $em */
        /** @var Connection $connection */
        $em = $this->doctrine->getManager();
        $connection = $this->doctrine->getConnection();

        $queryBuilder = new QueryBuilder($connection);
        $properties = $dataSchemaConfig['properties'];

        if ($hasFrom) {
            $queryBuilder->from($dataSchemaConfig['tableName'], $alias);
        }

        $selectParts = [];
        $selects = $dataSchemaConfig['query']['selects'] ?? [];

        // Discriminator
        if (isset($dataSchemaConfig['discriminatorColumnName'])) {
            $selectParts[] = $alias.'.'.$dataSchemaConfig['discriminatorColumnName'];
        }

        foreach ($properties as $propertyName => $property) {
            $part = null;

            // Field
            if (isset($property['field_db_name']) && $property['from_db']) {
                $part = $this->fieldSelectPart($alias, $propertyName, $property);
            }

            // Select
            if ($selects && $property['source'] && \array_key_exists($property['source'], $selects)) {
                $part = $this->fieldSourceSelectPart($property['source'], $selects[$property['source']]);
            }

            // Entity
            if ($property['type'] === 'entity') {
                $part = $this->entitySelectPart(
                    $queryBuilder,
                    $alias,
                    $propertyName,
                    $property,
                    $dataSchemaConfig
                );
            }

            // Collection
            if ($property['type'] === 'collection') {
                $part = $this->collectionSelectPart(
                    $queryBuilder,
                    $alias,
                    $propertyName,
                    $property,
                    $dataSchemaConfig
                );
            }

            if ($part) {
                $selectParts[] = $part;
            }
        }

        if ($selectParts !== []) {
            $queryBuilder->select(implode(',', $selectParts));
        }

        foreach ($inConditions as $condition) {
            $queryBuilder->andWhere($condition);
        }

        if (!empty($dataSchemaConfig['conditions'])) {
            $conditions = $dataSchemaConfig['conditions'];
            foreach ($conditions as $conditionConfig) {
                if (!$conditionConfig['enabled']) {
                    continue;
                }

                $preparedCondition = $this->placeholder->condition($conditionConfig['condition'], $alias);
                if ($preparedCondition) {
                    $queryBuilder->andWhere($preparedCondition);
                }
            }
        }

        // Apply doctrine filters
        $classMetadata = $this->getClassMetadata($dataSchemaConfig['class']);
        foreach ($em->getFilters()->getEnabledFilters() as $filter) {
            $filterConstraint = $filter->addFilterConstraint($classMetadata, $alias);

            if ($filterConstraint) {
                $queryBuilder->andWhere($filterConstraint);
            }
        }

        foreach ($inJoins as $join) {
            $queryBuilder->leftJoin(
                $join['fromAlias'],
                $join['joinTable'],
                $join['joinAlias'],
                $join['joinCondition']
            );
        }

        // Order by
        foreach ($inOrderBy as $sort => $order) {
            $queryBuilder->addOrderBy($alias.'.'.$sort, $order);
        }

        return $queryBuilder;
    }

    /**
     * @param array<string, mixed> $property
     */
    private function fieldSelectPart(string $alias, string $propertyName, array $property): string
    {
        $part = $alias.'.'.$property['field_db_name'];

        if ($property['field_db_name'] !== $propertyName) {
            $part .= ' as "'.$propertyName.'"';
        }

        return $part;
    }

    private function fieldSourceSelectPart(string $propertyName, string $select): string
    {
        return "({$select}) as \"{$propertyName}\"";
    }

    /**
     * @param array<string, mixed> $propertyConfig
     * @param array<string, mixed> $dataSchemaConfig
     */
    protected function entitySelectPart(
        QueryBuilder $queryBuilder,
        string $alias,
        string $propertyName,
        array $propertyConfig,
        array $dataSchemaConfig,
    ): string {
        $classMetadata = $this->getClassMetadataByDataSchema($dataSchemaConfig, $propertyConfig['discriminator']);
        $propertyAssociationMapping = $classMetadata->getAssociationMapping($propertyName);
        $propertyAlias = $alias.'_'.$propertyName;

        // Get $condition for WHERE or JOIN clause
        if ($propertyAssociationMapping['isOwningSide']) {
            $sourceToTargetKeyColumns = $propertyAssociationMapping['sourceToTargetKeyColumns'];

            $condition = $queryBuilder->expr()->eq(
                $propertyAlias.'.'.current($sourceToTargetKeyColumns),
                $alias.'.'.key($sourceToTargetKeyColumns)
            );
        } else {
            $targetEntityClassMetadata = $this->getClassMetadata($propertyAssociationMapping['targetEntity']);
            $targetEntityAssociationMapping = $targetEntityClassMetadata->getAssociationMapping($propertyAssociationMapping['mappedBy']);
            $sourceToTargetKeyColumns = $targetEntityAssociationMapping['sourceToTargetKeyColumns'];

            $condition = $queryBuilder->expr()->eq(
                $propertyAlias.'.'.key($sourceToTargetKeyColumns),
                $alias.'.'.current($sourceToTargetKeyColumns)
            );
        }

        $conditions = [];

        // If has "From" clause add join to parent without conditions
        if ($queryBuilder->hasFrom()) {
            $propertyClassMetadata = $this->getClassMetadata($propertyConfig['class']);

            $tableName = $propertyAssociationMapping['isOwningSide'] ?
                $propertyClassMetadata->getTableName() :
                $this->getClassMetadata($propertyAssociationMapping['targetEntity'])->getTableName();

            $queryBuilder->leftJoin($alias, $tableName, $propertyAlias, $condition);
        } else {
            $conditions[] = $condition;
        }

        $subSqlQueryBuilder = $this->createQueryBuilderBySchema(
            $propertyConfig,
            $propertyAlias,
            $conditions,
            [],
            [],
            $conditions !== [] // If has conditions add FROM clause
        );

        $uniqueAlias = uniqid('row_', false);

        return '
            (
                SELECT row_to_json('.$uniqueAlias.')
                FROM ('.$subSqlQueryBuilder->getSQL().') as '.$uniqueAlias.'
            ) as "'.$propertyName.'"
        ';
    }

    /**
     * @param array<string, mixed> $propertyConfig
     * @param array<string, mixed> $dataSchemaConfig
     */
    protected function collectionSelectPart(
        QueryBuilder $queryBuilder,
        string $alias,
        string $propertyName,
        array $propertyConfig,
        array $dataSchemaConfig,
    ): string {
        $uniqueAlias = uniqid('row_', false);
        $propertyAlias = $alias.'_'.$propertyName;
        $classMetadata = $this->getClassMetadataByDataSchema($dataSchemaConfig, $propertyConfig['discriminator']);
        $propertyAssociationMapping = $classMetadata->getAssociationMapping($propertyName);

        $conditions = [];
        $joins = [];

        if (!empty($propertyConfig['orderBy'])) {
            $orderBy = $propertyConfig['orderBy'];
        } else {
            $orderBy = $propertyAssociationMapping['orderBy'] ?? [];
        }

        // Many-To-Many
        if ($propertyAssociationMapping['type'] === ClassMetadata::MANY_TO_MANY) {
            // Owning Side
            if ($propertyAssociationMapping['isOwningSide']) {
                $joinTable = $propertyAssociationMapping['joinTable'];
                $relationToSourceKeyColumns = $propertyAssociationMapping['relationToSourceKeyColumns'];
                $relationToTargetKeyColumns = $propertyAssociationMapping['relationToTargetKeyColumns'];
                $propertyJoinAlias = $propertyAlias.'_join';

                $joins[] = [
                    'fromAlias' => $propertyAlias,
                    'joinTable' => $joinTable['name'],
                    'joinAlias' => $propertyJoinAlias,
                    'joinCondition' => $queryBuilder->expr()->eq(
                        $propertyJoinAlias.'.'.key($relationToTargetKeyColumns),
                        $propertyAlias.'.'.current($relationToTargetKeyColumns)
                    ),
                ];

                $conditions[] = $queryBuilder->expr()->eq(
                    $propertyJoinAlias.'.'.key($relationToSourceKeyColumns),
                    $alias.'.'.current($relationToSourceKeyColumns)
                );
            } else {
                $targetEntityClassMetadata = $this->getClassMetadata($propertyAssociationMapping['targetEntity']);
                $targetEntityAssociationMapping = $targetEntityClassMetadata->getAssociationMapping(
                    $propertyAssociationMapping['mappedBy']
                );

                $joinTable = $targetEntityAssociationMapping['joinTable'];
                $relationToSourceKeyColumns = $targetEntityAssociationMapping['relationToSourceKeyColumns'];
                $relationToTargetKeyColumns = $targetEntityAssociationMapping['relationToTargetKeyColumns'];
                $propertyJoinAlias = $propertyAlias.'_join';

                $joins[] = [
                    'fromAlias' => $propertyAlias,
                    'joinTable' => $joinTable['name'],
                    'joinAlias' => $propertyJoinAlias,
                    'joinCondition' => $queryBuilder->expr()->eq(
                        $propertyJoinAlias.'.'.key($relationToSourceKeyColumns),
                        $propertyAlias.'.'.current($relationToTargetKeyColumns)
                    ),
                ];

                $conditions[] = $queryBuilder->expr()->eq(
                    $propertyJoinAlias.'.'.key($relationToTargetKeyColumns),
                    $alias.'.'.current($relationToSourceKeyColumns)
                );
            }

        // One-To-Many
        } elseif ($propertyAssociationMapping['type'] === ClassMetadata::ONE_TO_MANY) {
            $targetEntityClassMetadata = $this->getClassMetadata($propertyAssociationMapping['targetEntity']);
            $targetEntityAssociationMapping = $targetEntityClassMetadata->getAssociationMapping($propertyAssociationMapping['mappedBy']);
            $sourceToTargetKeyColumns = $targetEntityAssociationMapping['sourceToTargetKeyColumns'];

            $conditions[] = $queryBuilder->expr()->eq(
                $propertyAlias.'.'.key($sourceToTargetKeyColumns),
                $alias.'.'.current($sourceToTargetKeyColumns)
            );
        }

        $subSqlQueryBuilder = $this->createQueryBuilderBySchema(
            $propertyConfig,
            $propertyAlias,
            $conditions,
            $joins,
            $orderBy,
            true
        );

        return '
            (
                SELECT array_to_json(array_agg(row_to_json('.$uniqueAlias.')))
                FROM ('.$subSqlQueryBuilder->getSQL().') as '.$uniqueAlias.'
            ) as "'.$propertyName.'"                      
        ';
    }
}
