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
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class AbstractFilterFactory.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractFilterFactory
{
    protected FilterTypeGuesser $filterTypeGuesser;

    abstract protected function getTypes(): array;

    abstract protected function getJoinBuilder(): JoinBuilderInterface;

    /**
     * DoctrineDatagridBuilder constructor.
     */
    public function __construct(protected Registry $doctrine)
    {
        $this->filterTypeGuesser = new FilterTypeGuesser();
    }

    /**
     * @throws Exception
     * @throws MappingException
     */
    public function createForEntity(
        string $entityClass,
        string $alias,
        string $name,
        ?string $type = null,
        array $options = [],
    ): FilterInterface {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $classMetadata = $em->getClassMetadata($entityClass);

        $options = $this->fixOptions($options);
        [$fieldName, $classMetadata, $joinMap] = $this->parse($classMetadata, $alias, $name, $options);

        if (!$type) {
            $guessType = $this->filterTypeGuesser->guessType($em, $fieldName, $classMetadata, $options);

            $options = array_merge($guessType->getOptions(), $options);
            $type = $guessType->getType();
        }

        return $this->createByType($type, $name, $options, $fieldName, $classMetadata, $joinMap);
    }

    protected function createByType(
        string $type,
        string $name,
        array $options,
        string $fieldName,
        ClassMetadata $classMetadata,
        ?JoinMap $joinMap = null,
    ): FilterInterface {
        $types = $this->getTypes();

        if (!isset($types[$type])) {
            throw new \RuntimeException(\sprintf('Type of filter "%s" is not defined.', $type));
        }

        $class = $types[$type];

        return new $class($name, $options, $fieldName, $classMetadata, $this->getJoinBuilder(), $joinMap);
    }

    /**
     * @throws MappingException
     */
    protected function getAssociationType(ClassMetadata $classMetadata, string $fieldName): mixed
    {
        $type = null;
        if ($classMetadata->hasAssociation($fieldName)) {
            $associationMapping = $classMetadata->getAssociationMapping($fieldName);
            $type = $associationMapping['type'];
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws MappingException
     */
    private function fixOptions(array $options = []): array
    {
        if (!isset($options['has_select'])) {
            $options['has_select'] = true;
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int, string|ClassMetadata|JoinMap|null>
     *
     * @throws MappingException
     */
    private function parse(ClassMetadata $inClassMetadata, string $alias, string $filterName, array $options = []): array
    {
        $fieldName = $filterName;
        $classMetadata = $inClassMetadata;
        $joinMap = null;

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $joinPath = $alias;
        if (strpos($filterName, '.') > 0) {
            $filterElements = explode('.', $filterName);
            $lastFilterElement = $filterElements[\count($filterElements) - 1];

            $joinMap = new JoinMap($alias, $inClassMetadata);
            $joinFieldName = null;
            $joinClassMetadata = $inClassMetadata;
            foreach ($filterElements as $joinFieldName) {
                if ($joinClassMetadata->hasAssociation($joinFieldName)) {
                    $isLastElement = $joinFieldName === $lastFilterElement;
                    if (!$isLastElement) {
                        $joinMap->join($joinPath, $joinFieldName, $options['has_select']);

                        $joinAssociationMapping = $joinClassMetadata->getAssociationMapping($joinFieldName);
                        $joinClassName = $joinAssociationMapping['targetEntity'];

                        $joinClassMetadata = $em->getClassMetadata($joinClassName);
                        $joinPath .= '.'.$joinFieldName;
                    }
                } else {
                    break;
                }
            }

            $classMetadata = $joinClassMetadata;
            $fieldName = $joinFieldName;
        }

        return [$fieldName, $classMetadata, $joinMap];
    }
}
