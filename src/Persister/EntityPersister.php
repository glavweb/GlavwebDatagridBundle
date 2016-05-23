<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Persister;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

/**
 * Class EntityPersister
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class EntityPersister
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * EntityPersister constructor.
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @return array
     */
    public function getManyToManyData(array $associationMapping, $id, array $databaseFields)
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @return array
     */
    public function getOneToManyData(array $associationMapping, $id, array $databaseFields)
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @return array
     */
    public function getManyToOneData(array $associationMapping, $id, array $databaseFields)
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields);

        return (array)$query->getOneOrNullResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @return array
     */
    public function getOneToOneData(array $associationMapping, $id, array $databaseFields)
    {
        $query = $this->getQuery($associationMapping, $id, $databaseFields);

        return (array)$query->getOneOrNullResult();
    }

    /**
     * @param array $associationMapping
     * @param $id
     * @param array $databaseFields
     * @return \Doctrine\ORM\Query
     */
    protected function getQuery(array $associationMapping, $id, array $databaseFields)
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

        return $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
    }
}
