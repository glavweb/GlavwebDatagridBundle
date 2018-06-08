<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Hydrator\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Exception;

/**
 * Class ObjectHydrator
 *
 * The class based on https://github.com/pmill/doctrine-array-hydrator/blob/master/src/ArrayHydrator.php
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ObjectHydrator
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var bool
     */
    protected $hydrateAssociationReferences = true;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->entityManager = $doctrine->getManager();
    }

    /**
     * @param object|string $entity
     * @param array  $data
     * @return object
     * @throws Exception
     */
    public function hydrate($entity, array $data)
    {
        if (is_string($entity) && class_exists($entity)) {
            $entity = new $entity;

        } elseif (!is_object($entity)) {
            throw new Exception('Entity passed to ObjectHydrator::hydrate() must be a class name or entity object');
        }

        $entity = $this->hydrateProperties($entity, $data);
        $entity = $this->hydrateAssociations($entity, $data);

        return $entity;
    }

    /**
     * @param boolean $hydrateAssociationReferences
     */
    public function setHydrateAssociationReferences($hydrateAssociationReferences)
    {
        $this->hydrateAssociationReferences = $hydrateAssociationReferences;
    }

    /**
     * @param $entity
     * @param $data
     * @return object
     */
    protected function hydrateProperties($entity, $data)
    {
        $reflectionClass = new \ReflectionClass($entity);
        $metaData = $this->entityManager->getClassMetadata(get_class($entity));

        foreach ($metaData->getFieldNames() as $propertyName) {
            if (isset($data[$propertyName])) {
                $entity = $this->setProperty($entity, $propertyName, $data[$propertyName], $reflectionClass);
            }
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param $data
     * @return mixed
     */
    protected function hydrateAssociations($entity, $data)
    {
        $metaData = $this->entityManager->getClassMetadata(get_class($entity));

        foreach ($metaData->getAssociationMappings() as $propertyName => $mapping) {
            if (isset($data[$propertyName])) {
                if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE])) {
                    $metaData = $this->entityManager->getClassMetadata($mapping['targetEntity']);

                    if (count($metaData->identifier) !== 1) {
                        throw new \RuntimeException('Identifier must be single.');
                    }
                    $identifier = $metaData->identifier[0];

                    if (!isset($data[$propertyName][$identifier])) {
                        throw new \RuntimeException('Identifier "' . $identifier . '" not found in "' . $propertyName . '" data.');
                    }

                    $entity = $this->hydrateToOneAssociation($entity, $propertyName, $mapping, $data[$propertyName][$identifier]);
                }

                if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY])) {
                    $entity = $this->hydrateToManyAssociation($entity, $propertyName, $mapping, $data[$propertyName]);
                }
            }
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyName
     * @param $mapping
     * @param $value
     * @return mixed
     */
    protected function hydrateToOneAssociation($entity, $propertyName, $mapping, $value)
    {
        $reflectionClass = new \ReflectionClass($entity);
        $toOneAssociationObject = $this->fetchAssociationEntity($mapping['targetEntity'], $value);

        if (!is_null($toOneAssociationObject)) {
            $entity = $this->setProperty($entity, $propertyName, $toOneAssociationObject, $reflectionClass);
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyName
     * @param $mapping
     * @param $value
     * @return mixed
     */
    protected function hydrateToManyAssociation($entity, $propertyName, $mapping, $value)
    {
        $reflectionClass = new \ReflectionClass($entity);
        $values = is_array($value) ? $value : [$value];
        $associationObjects = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                $associationObjects[] = $this->hydrate($mapping['targetEntity'], $value);

            } elseif ($associationObject = $this->fetchAssociationEntity($mapping['targetEntity'], $value)) {
                $associationObjects[] = $associationObject;
            }
        }

        $entity = $this->setProperty($entity, $propertyName, $associationObjects, $reflectionClass);

        return $entity;
    }

    /**
     * @param object $entity
     * @param string $propertyName
     * @param mixed  $value
     * @param \ReflectionClass $reflectionObject
     * @return mixed
     * @throws Exception
     */
    protected function setProperty($entity, $propertyName, $value, $reflectionObject = null)
    {
        $reflectionObject = $reflectionObject ?: new \ReflectionClass($entity);

        if (!$reflectionObject->hasProperty($propertyName)) {
            $parentReflectionClass = $reflectionObject->getParentClass();
            if (!$parentReflectionClass) {
                throw new \Exception(sprintf('Property "%s" not found in class "%s".', $propertyName, $reflectionObject->getName()));
            }

            return $this->setProperty($entity, $propertyName, $value, $parentReflectionClass);
        }

        $property = $reflectionObject->getProperty($propertyName);

        $property->setAccessible(true);
        $property->setValue($entity, $value);

        return $entity;
    }

    /**
     * @param string $className
     * @param int    $id
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function fetchAssociationEntity($className, $id)
    {
        if ($this->hydrateAssociationReferences) {
            return $this->entityManager->getReference($className, $id);
        }

        return $this->entityManager->find($className, $id);
    }
}