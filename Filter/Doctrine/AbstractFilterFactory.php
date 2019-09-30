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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class AbstractFilterFactory
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractFilterFactory
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var FilterTypeGuesser
     */
    protected $filterTypeGuesser;

    /**
     * @return array
     */
    abstract protected function getTypes(): array;

    /**
     * @return JoinBuilderInterface
     */
    abstract protected function getJoinBuilder(): JoinBuilderInterface;

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
     * @return FilterInterface
     */
    public function createForEntity($entityClass, $alias, $name, $type = null, $options = [])
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->doctrine->getManager()->getClassMetadata($entityClass);

        $options = $this->fixOptions($options);
        [$fieldName, $classMetadata, $joinMap] = $this->parse($classMetadata, $alias, $name, $options);

        if (!$type) {
            $guessType = $this->filterTypeGuesser->guessType($fieldName, $classMetadata, $options);

            $options = array_merge($guessType->getOptions(), $options);
            $type    = $guessType->getType();
        }

        return $this->createByType($type, $name, $options, $fieldName, $classMetadata, $joinMap);
    }

    /**
     * @param string $type
     * @param string $name
     * @param array $options
     * @param string $fieldName
     * @param ClassMetadata $classMetadata
     * @param JoinMap|null $joinMap
     * @return FilterInterface
     */
    protected function createByType(
        string $type,
        string $name,
        array $options,
        string $fieldName,
        ClassMetadata $classMetadata,
        JoinMap $joinMap = null
    ): FilterInterface {
        $types = $this->getTypes();

        if (!isset($types[$type])) {
            throw new \RuntimeException(sprintf('Type of filter "%s" is not defined.', $type));
        }

        $class = $types[$type];

        return new $class($name, $options, $fieldName, $classMetadata, $this->getJoinBuilder(), $joinMap);
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
     * @param array $options
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function fixOptions(array $options = [])
    {
        if (!isset($options['has_select'])) {
            $options['has_select'] = true;
        }

        return $options;
    }

    /**
     * @param ClassMetadata $inClassMetadata
     * @param string $alias
     * @param string $filterName
     * @param array $options
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function parse(ClassMetadata $inClassMetadata, $alias, $filterName, array $options = [])
    {
        $fieldName     = $filterName;
        $classMetadata = $inClassMetadata;
        $joinMap       = null;

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $joinPath = $alias;
        if (strpos($filterName, '.') > 0) {
            $filterElements    = explode('.', $filterName);
            $lastFilterElement = $filterElements[count($filterElements) - 1];

            $joinMap           = new JoinMap($alias, $inClassMetadata);
            $joinFieldName     = null;
            $joinClassMetadata = $inClassMetadata;
            foreach ($filterElements as $joinFieldName) {
                if ($joinClassMetadata->hasAssociation($joinFieldName)) {
                    $isLastElement = $joinFieldName == $lastFilterElement;
                    if (!$isLastElement) {
                        $joinMap->join($joinPath, $joinFieldName, $options['has_select']);

                        $joinAssociationMapping = $joinClassMetadata->getAssociationMapping($joinFieldName);
                        $joinClassName = $joinAssociationMapping['targetEntity'];

                        $joinClassMetadata = $em->getClassMetadata($joinClassName);
                        $joinPath .= '.' . $joinFieldName;
                    }

                } else {
                    break;
                }
            }

            $classMetadata = $joinClassMetadata;
            $fieldName     = $joinFieldName;
        }

        return [$fieldName, $classMetadata, $joinMap];
    }
}
