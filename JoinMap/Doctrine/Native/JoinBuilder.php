<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\JoinMap\Doctrine\Native;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder as NativeQueryBuilder;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class JoinBuilder.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class JoinBuilder implements JoinBuilderInterface
{
    /**
     * JoinBuilder constructor.
     */
    public function __construct(private readonly Registry $doctrine)
    {
    }

    /**
     * Apply joins and get last alias.
     *
     * @throws MappingException
     */
    public function apply(NativeQueryBuilder|ORMQueryBuilder $queryBuilder, JoinMap $joinMap): ?string
    {
        $alias = null;

        $rootAlias = $joinMap->getAlias();
        $rootClassMetadata = $joinMap->getClassMetadata();
        $executedAliases = $this->executedAliases($queryBuilder);

        $alias = $rootAlias;
        foreach ($joinMap->getJoinMap() as $path => $fields) {
            foreach ($fields as $fieldData) {
                $field = $fieldData['field'];
                $joinType = $fieldData['joinType'];
                $joinAlias = $alias.'_'.$field;

                if (!\in_array($joinAlias, $executedAliases)) {
                    if ($path == $rootAlias) {
                        $classMetadata = $rootClassMetadata;
                    } else {
                        $pathWithoutRootAlias = substr((string) $path, \strlen($rootAlias) + 1);
                        $classMetadata = $this->getClassMetadataByPath($pathWithoutRootAlias, $rootClassMetadata);
                    }

                    $joinMethod = $joinType == JoinMap::JOIN_TYPE_LEFT ? 'leftJoin' : ($joinType == JoinMap::JOIN_TYPE_INNER ? 'innerJoin' : null);

                    if (!$joinMethod) {
                        throw new \RuntimeException('Join type not defined or has wrong type.');
                    }

                    $associationMapping = $classMetadata->getAssociationMapping($field);

                    if ($associationMapping['type'] === ClassMetadata::MANY_TO_MANY) {
                        // If is owning side
                        if ($associationMapping['isOwningSide']) {
                            $joinTable = $associationMapping['joinTable'];
                            $joinTableName = $joinTable['name'];
                            $joinTableAlias = $alias.'_'.$joinTable['name'];

                            $relationToSourceKeyColumns = $associationMapping['relationToSourceKeyColumns'];

                            $queryBuilder->$joinMethod(
                                $alias,
                                $joinTableName,
                                $joinTableAlias,
                                $joinTableAlias.'.'.key($relationToSourceKeyColumns).' = '.$alias.'.'.current($relationToSourceKeyColumns)
                            );

                            $fieldClassMetadata = $this->getClassMetadata($associationMapping['targetEntity']);
                            $fieldTableName = $fieldClassMetadata->getTableName();
                            $relationToTargetKeyColumns = $associationMapping['relationToTargetKeyColumns'];

                            $queryBuilder->$joinMethod(
                                $alias,
                                $fieldTableName,
                                $joinAlias,
                                $joinAlias.'.'.current($relationToTargetKeyColumns).' = '.$joinTableAlias.'.'.key(
                                    $relationToTargetKeyColumns
                                )
                            );
                        } else {
                            $fieldClassMetadata = $this->getClassMetadata($associationMapping['targetEntity']);
                            $fieldAssociationMapping = $fieldClassMetadata->getAssociationMapping($associationMapping['mappedBy']);
                            $fieldTableName = $fieldClassMetadata->getTableName();

                            $joinTable = $fieldAssociationMapping['joinTable'];
                            $joinTableName = $joinTable['name'];
                            $joinTableAlias = $alias.'_'.$joinTable['name'];
                            $relationToSourceKeyColumns = $fieldAssociationMapping['relationToSourceKeyColumns'];
                            $relationToTargetKeyColumns = $fieldAssociationMapping['relationToTargetKeyColumns'];

                            $queryBuilder->$joinMethod(
                                $alias,
                                $joinTableName,
                                $joinTableAlias,
                                $joinTableAlias.'.'.key($relationToTargetKeyColumns).' = '.$alias.'.'.current($relationToTargetKeyColumns)
                            );

                            $queryBuilder->$joinMethod(
                                $alias,
                                $fieldTableName,
                                $joinAlias,
                                $joinAlias.'.'.current($relationToSourceKeyColumns).' = '.$joinTableAlias.'.'.key(
                                    $relationToSourceKeyColumns
                                )
                            );
                        }
                    } elseif ($associationMapping['type'] === ClassMetadata::ONE_TO_MANY) {
                        $fieldClassMetadata = $this->getClassMetadata($associationMapping['targetEntity']);
                        $fieldAssociationMapping = $fieldClassMetadata->getAssociationMapping($associationMapping['mappedBy']);
                        $fieldTableName = $fieldClassMetadata->getTableName();
                        $sourceToTargetKeyColumns = $fieldAssociationMapping['sourceToTargetKeyColumns'];

                        $queryBuilder->$joinMethod(
                            $alias,
                            $fieldTableName,
                            $joinAlias,
                            $joinAlias.'.'.key($sourceToTargetKeyColumns).' = '.$alias.'.'.current($sourceToTargetKeyColumns)
                        );
                    } elseif ($associationMapping['type'] === ClassMetadata::MANY_TO_ONE) {
                        $fieldClassMetadata = $this->getClassMetadata($associationMapping['targetEntity']);
                        $fieldTableName = $fieldClassMetadata->getTableName();
                        $sourceToTargetKeyColumns = $associationMapping['sourceToTargetKeyColumns'];

                        $queryBuilder->$joinMethod(
                            $alias,
                            $fieldTableName,
                            $joinAlias,
                            $joinAlias.'.'.current($sourceToTargetKeyColumns).' = '.$alias.'.'.key($sourceToTargetKeyColumns)
                        );
                    } elseif ($associationMapping['type'] === ClassMetadata::ONE_TO_ONE) {
                        if ($associationMapping['isOwningSide']) {
                            $fieldClassMetadata = $this->getClassMetadata($associationMapping['targetEntity']);
                            $fieldTableName = $fieldClassMetadata->getTableName();
                            $sourceToTargetKeyColumns = $associationMapping['sourceToTargetKeyColumns'];

                            $queryBuilder->leftJoin(
                                $alias,
                                $fieldTableName,
                                $joinAlias,
                                $alias.'.'.key($sourceToTargetKeyColumns).' = '.$joinAlias.'.'.current($sourceToTargetKeyColumns)
                            );
                        } else {
                            $fieldClassMetadata = $this->getClassMetadata($associationMapping['targetEntity']);
                            $fieldTableName = $fieldClassMetadata->getTableName();
                            $fieldAssociationMapping = $fieldClassMetadata->getAssociationMapping($associationMapping['mappedBy']);
                            $sourceToTargetKeyColumns = $fieldAssociationMapping['sourceToTargetKeyColumns'];

                            $queryBuilder->leftJoin(
                                $alias,
                                $fieldTableName,
                                $joinAlias,
                                $alias.'.'.current($sourceToTargetKeyColumns).' = '.$joinAlias.'.'.key($sourceToTargetKeyColumns)
                            );
                        }
                    }
                }

                $alias = $joinAlias;
            }
        }

        return $alias;
    }

    protected function getClassMetadataByPath(string $path, ClassMetadata $rootClassMetadata): ClassMetadata
    {
        $parts = explode('.', $path);
        $fieldName = array_shift($parts);

        if (!$rootClassMetadata->hasAssociation($fieldName)) {
            return $rootClassMetadata;
        }

        $fieldAssociationMapping = $rootClassMetadata->getAssociationMapping($fieldName);
        $fieldClassMetadata = $this->getClassMetadata($fieldAssociationMapping['targetEntity']);

        if ($parts !== []) {
            return $this->getClassMetadataByPath(implode('.', $parts), $fieldClassMetadata);
        }

        return $fieldClassMetadata;
    }

    private function getClassMetadata(string $className): ClassMetadata
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        return $em->getClassMetadata($className);
    }

    private function executedAliases(QueryBuilder $queryBuilder): array
    {
        return array_unique([$queryBuilder->getFromAliases(), $queryBuilder->getJoinAliases()]);
    }
}
