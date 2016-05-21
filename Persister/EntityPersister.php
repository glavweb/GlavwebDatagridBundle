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
 * @author Nilov Andrey <nilov@glavweb.ru>
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
     * @param array $properties
     * @return array
     */
    public function getManyToManyData(array $associationMapping, $id, array $properties)
    {
        $query = $this->getQuery($associationMapping, $id, $properties);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $properties
     * @return array
     */
    public function getOneToManyData(array $associationMapping, $id, array $properties)
    {
        $query = $this->getQuery($associationMapping, $id, $properties);

        return $query->getArrayResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $properties
     * @return array
     */
    public function getManyToOneData(array $associationMapping, $id, array $properties)
    {
        $query = $this->getQuery($associationMapping, $id, $properties);

        return (array)$query->getOneOrNullResult();
    }

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $properties
     * @return array
     */
    public function getOneToOneData(array $associationMapping, $id, array $properties)
    {
        $query = $this->getQuery($associationMapping, $id, $properties);

        return (array)$query->getOneOrNullResult();
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function getSelectedFields(array $properties)
    {
        $selectFields = ['id'];
        foreach ($properties as $propertyName => $propertyData) {
            $isValid = (isset($propertyData['from_db']) && $propertyData['from_db']);

            if ($isValid) {
                $selectFields[] = $propertyName;
            }

            if (isset($propertyData['source'])) {
                $selectFields[] = $propertyData['source'];
            }
        }

        return $selectFields;
    }

    /**
     * @param array $associationMapping
     * @param $id
     * @param array $properties
     * @return \Doctrine\ORM\Query
     */
    protected function getQuery(array $associationMapping, $id, array $properties)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $targetClass = $associationMapping['targetEntity'];
        $joinField   = $associationMapping['isOwningSide'] ? $associationMapping['inversedBy'] : $associationMapping['mappedBy'];
        $targetAlias = uniqid('t');
        $sourceAlias = uniqid('s');

        $selectFields = $this->getSelectedFields($properties);

        $qb = $em->createQueryBuilder()
            ->select(sprintf('PARTIAL %s.{%s}', $targetAlias, implode(',', $selectFields)))
            ->from($targetClass, $targetAlias)
            ->join(sprintf('%s.%s', $targetAlias, $joinField), $sourceAlias)
            ->where($sourceAlias . '.id = :sourceId')
            ->setParameter('sourceId', $id)
        ;

        return $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
    }
}
