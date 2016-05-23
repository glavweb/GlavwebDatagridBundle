<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class FilterFactory
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterFactory
{
    /**
     * @var array
     */
    private $types = [
        'doctrine_orm_string'   => 'Glavweb\DatagridBundle\Filter\Doctrine\StringFilter',
        'doctrine_orm_number'   => 'Glavweb\DatagridBundle\Filter\Doctrine\NumberFilter',
        'doctrine_orm_boolean'  => 'Glavweb\DatagridBundle\Filter\Doctrine\BooleanFilter',
        'doctrine_orm_datetime' => 'Glavweb\DatagridBundle\Filter\Doctrine\DateTimeFilter',
        'doctrine_orm_enum'     => 'Glavweb\DatagridBundle\Filter\Doctrine\EnumFilter',
        'doctrine_orm_model'    => 'Glavweb\DatagridBundle\Filter\Doctrine\ModelFilter',
        'doctrine_orm_callback' => 'Glavweb\DatagridBundle\Filter\Doctrine\CallbackFilter',
    ];

    /**
     * @var FilterTypeGuesser
     */
    private $filterTypeGuesser;

    /**
     * DoctrineDatagridBuilder constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine          = $doctrine;
        $this->filterTypeGuesser = new FilterTypeGuesser();
    }

    /**
     * @param $entityClass
     * @param $alias
     * @param $name
     * @param null $type
     * @param array $options
     * @return Filter
     */
    public function createForEntity($entityClass, $alias, $name, $type = null, $options = [])
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->doctrine->getManager()->getClassMetadata($entityClass);
        $options = $this->defineOptions($classMetadata, $alias, $name, $options);

        if (!$type) {
            $guessType = $this->filterTypeGuesser->guessType($options['field_name'], $options['class_metadata'], $options);

            $options = array_merge($guessType->getOptions(), $options);
            $type    = $guessType->getType();
        }

        return $this->create($name, $type, $options);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string $fieldName
     * @return mixed
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getAssociationType(ClassMetadata $classMetadata, $fieldName)
    {
        $type = null;
        if ($classMetadata->hasAssociation($fieldName)) {
            $associationMapping = $classMetadata->getAssociationMapping($fieldName);
            $type = $associationMapping['type'];
        }

        return $type;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return Filter
     */
    public function create($name, $type = null, $options = [])
    {
        if (!isset($this->types[$type])) {
            throw new \RuntimeException(sprintf('Type %s is not defined', $type));
        }
        $class = $this->types[$type];

        return new $class($name, $options);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string $alias
     * @param string $filterName
     * @param array $options
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function defineOptions(ClassMetadata $classMetadata, $alias, $filterName, array $options = [])
    {
        $em = $this->doctrine->getManager();

        $options['class_metadata'] = $classMetadata;
        $options['field_name']     = $filterName;

        $joinMap  = null;
        $joinPath = $alias;
        if (strpos($filterName, '.') > 0) {
            $filterElements    = explode('.', $filterName);
            $lastFilterElement = $filterElements[count($filterElements) - 1];

            $joinMap           = new JoinMap($alias);
            $joinFieldName     = null;
            $joinClassMetadata = $classMetadata;
            foreach ($filterElements as $joinFieldName) {
                if ($joinClassMetadata->hasAssociation($joinFieldName)) {
                    $isLastElement = $joinFieldName == $lastFilterElement;
                    if (!$isLastElement) {
                        $joinMap->join($joinPath, $joinFieldName);

                        $joinAssociationMapping = $joinClassMetadata->getAssociationMapping($joinFieldName);
                        $joinClassName = $joinAssociationMapping['targetEntity'];

                        $joinClassMetadata = $em->getClassMetadata($joinClassName);
                        $joinPath .= '.' . $joinFieldName;
                    }

                } else {
                    break;
                }
            }

            $options['class_metadata'] = $joinClassMetadata;
            $options['field_name']     = $joinFieldName;
        }

        // Add join for to-many associations
        $associationType = $this->getAssociationType($options['class_metadata'], $options['field_name']);
        if (in_array($associationType, [ClassMetadataInfo::MANY_TO_MANY, ClassMetadataInfo::ONE_TO_MANY])) {
            if (!$joinMap) {
                $joinMap = new JoinMap($alias);
            }

            $joinMap->join($joinPath, $options['field_name']);
        }

        $options['join_map'] = $joinMap;

        return $options;
    }
}