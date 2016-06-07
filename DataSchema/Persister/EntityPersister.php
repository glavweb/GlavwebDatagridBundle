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
     * @return array
     */
    public function getManyToManyData(array $associationMapping, $id, array $databaseFields, array $conditions = [])
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields, $conditions);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getOneToManyData(array $associationMapping, $id, array $databaseFields, array $conditions = [])
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields, $conditions);

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
     * @return Query
     */
    protected function getQuery(array $associationMapping, $id, array $databaseFields, array $conditions = [])
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $targetClass = $associationMapping['targetEntity'];
        $joinField   = $associationMapping['isOwningSide'] ? $associationMapping['inversedBy'] : $associationMapping['mappedBy'];
        $targetAlias = uniqid('t');
        $sourceAlias = uniqid('s');

        $qb = $em->createQueryBuilder()
            ->select(sprintf('PARTIAL %s.{%s}', $targetAlias, implode(',', $databaseFields)))
            ->from($targetClass, $targetAlias)
            ->join(sprintf('%s.%s', $targetAlias, $joinField), $sourceAlias)
            ->where($sourceAlias . '.id = :sourceId')
            ->setParameter('sourceId', $id)
        ;

        foreach ($conditions as $condition) {
            $preparedCondition = $this->dataSchema->conditionPlaceholder($condition, $targetAlias);
            $qb->andWhere($preparedCondition);
        }

        return $qb->getQuery()->setHydrationMode($this->hydrationMode);
    }
}
