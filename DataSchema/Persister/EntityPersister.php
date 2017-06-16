<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DataSchema\Persister;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Glavweb\DatagridBundle\DataSchema\DataSchema;
use Glavweb\DatagridBundle\Exception\Persister\InvalidQueryException;

/**
 * Class EntityPersister
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class EntityPersister implements PersisterInterface
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var DataSchema
     */
    private $dataSchema;

    /**
     * @var int
     */
    private $hydrationMode;

    /**
     * EntityPersister constructor.
     *
     * @param Registry $doctrine
     * @param DataSchema $dataSchema
     * @param int $hydrationMode
     */
    public function __construct(Registry $doctrine, DataSchema $dataSchema, $hydrationMode = Query::HYDRATE_ARRAY)
    {
        $this->doctrine      = $doctrine;
        $this->dataSchema    = $dataSchema;
        $this->hydrationMode = $hydrationMode;
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @param array $orderByExpressions
     * @return array
     */
    public function getManyToManyData(array $associationMapping, $id, array $databaseFields, array $conditions = [], array $orderByExpressions = [])
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields, $conditions, $orderByExpressions);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @param array $orderByExpressions
     * @return array
     */
    public function getOneToManyData(array $associationMapping, $id, array $databaseFields, array $conditions = [], array $orderByExpressions = [])
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields, $conditions, $orderByExpressions);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getManyToOneData(array $associationMapping, $id, array $databaseFields, array $conditions = [])
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields, $conditions);

        return (array)$query->getOneOrNullResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getOneToOneData(array $associationMapping, $id, array $databaseFields, array $conditions = [])
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields, $conditions);

        return (array)$query->getOneOrNullResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @param array $orderByExpressions
     * @return Query
     * @throws InvalidQueryException
     */
    protected function getQuery(array $associationMapping, $id, array $databaseFields, array $conditions = [], array $orderByExpressions = [])
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $targetClass = $associationMapping['targetEntity'];
        $joinField   = $associationMapping['isOwningSide'] ? $associationMapping['inversedBy'] : $associationMapping['mappedBy'];
        $targetAlias = uniqid('t');
        $sourceAlias = uniqid('s');

        if (!$joinField) {
            throw new InvalidQueryException(sprintf('The join filed part cannot be defined. May be you need configure association mapping for classes "%s" and "%s".',
                $associationMapping['sourceEntity'],
                $targetClass
            ));
        }

        $qb = $em->createQueryBuilder()
            ->select(sprintf('PARTIAL %s.{%s}', $targetAlias, implode(',', $databaseFields)))
            ->from($targetClass, $targetAlias)
            ->join(sprintf('%s.%s', $targetAlias, $joinField), $sourceAlias)
            ->where($sourceAlias . '.id = :sourceId')
            ->setParameter('sourceId', $id)
        ;

        foreach ($conditions as $condition) {
            $preparedCondition = $this->dataSchema->conditionPlaceholder($condition, $targetAlias);
            if ($preparedCondition) {
                $qb->andWhere($preparedCondition);
            }
        }

        foreach ($orderByExpressions as $sort => $direction) {
            $qb->addOrderBy("$targetAlias.$sort", $direction);
        }

        return $qb->getQuery()->setHydrationMode($this->hydrationMode);
    }
}
