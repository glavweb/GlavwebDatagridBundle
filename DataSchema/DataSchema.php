<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DataSchema;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Glavweb\DatagridBundle\DataTransformer\DataTransformerRegistry;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\DatagridBundle\Persister\EntityPersister;

/**
 * Class DataSchema
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataSchema
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var DataTransformerRegistry
     */
    private $dataTransformerRegistry;

    /**
     * @var EntityPersister
     */
    private $entityPersister;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @var ClassMetadata[]
     */
    private $classMetadataCache;

    /**
     * DataSchema constructor.
     *
     * @param Registry $doctrine
     * @param DataTransformerRegistry $dataTransformerRegistry
     * @param EntityPersister $entityPersister
     * @param array $configuration
     * @param array $scopeConfig
     */
    public function __construct(Registry $doctrine, DataTransformerRegistry $dataTransformerRegistry, EntityPersister $entityPersister, array $configuration, array $scopeConfig = null)
    {
        $this->doctrine                 = $doctrine;
        $this->dataTransformerRegistry  = $dataTransformerRegistry;
        $this->entityPersister          = $entityPersister;

        if (!isset($configuration['class'])) {
            throw new \RuntimeException('Option "class" must be defined.');
        }
        $class = $configuration['class'];

        $this->configuration = $this->prepareConfiguration($configuration, $class, $scopeConfig);
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $data
     * @param array $config
     * @return array
     */
    public function getData(array $data, array $config = null)
    {
        if ($config === null) {
            $config = $this->configuration;
        }

        if (!isset($config['properties'])) {
            return [];
        }

        $preparedData = [];
        foreach ($config['properties'] as $key => $info) {
            if (isset($info['hidden']) && $info['hidden'] == true) {
                continue;
            }

            if (isset($data[$key])) {
                $value = $data[$key];

            } elseif (isset($info['source']) && isset($data[$info['source']])) {
                $value = $data[$info['source']];

            } elseif (isset($info['properties']) && isset($info['class'])) {
                $metadata = $this->getClassMetadata($config['class']);
                if (!$metadata->hasAssociation($key)) {
                    continue;
                }

                /** @var EntityManager $em */
                $associationMapping = $metadata->getAssociationMapping($key);
                $databaseFields = self::getDatabaseFields($info['properties']);

                switch ($associationMapping['type']) {
                    case ClassMetadata::MANY_TO_MANY:
                        $modelData = $this->entityPersister->getManyToManyData($associationMapping, $data['id'], $databaseFields);
                        $preparedData[$key] = $this->getList($modelData, $info);

                        break;

                    case ClassMetadata::ONE_TO_MANY:
                        $modelData = $this->entityPersister->getOneToManyData($associationMapping, $data['id'], $databaseFields);
                        $preparedData[$key] = $this->getList($modelData, $info);

                        break;

                    case ClassMetadata::MANY_TO_ONE:
                        $modelData = $this->entityPersister->getManyToOneData($associationMapping, $data['id'], $databaseFields);
                        $preparedData[$key] = $this->getData($modelData, $info);

                        break;

                    case ClassMetadata::ONE_TO_ONE:
                        $modelData = $this->entityPersister->getOneToOneData($associationMapping, $data['id'], $databaseFields);
                        $preparedData[$key] = $this->getData($modelData, $info);

                        break;
                }

                continue;

            } else {
                continue;
            }

            if (is_array($value)) {
                $subConfig = $config['properties'][$key];

                if ($subConfig['type'] == 'entity') {
                    $preparedData[$key] = $this->getData($value, $config['properties'][$key]);

                    continue;

                } elseif ($subConfig['type'] == 'collection') {
                    foreach ($value as $subKey => $subInfo) {
                        $preparedData[$key][$subKey] = $this->getData($subInfo, $config['properties'][$key]);
                    }

                    continue;
                }
            }

            if (isset($info['decode'])) {
                $value = $this->decode($value, $info['decode'], $data);
            }

            $preparedData[$key] = $value;
        }

        return $preparedData;
    }

    /**
     * @param array $list
     * @param array $config
     * @return array
     */
    public function getList(array $list, array $config = null)
    {
        if ($config === null) {
            $config = $this->configuration;
        }

        foreach ($list as $key => $value) {
            $list[$key] = $this->getData($value, $config);
        }

        return $list;
    }

    /**
     * @param array $configuration
     * @param string $class
     * @param array $scopeConfig
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function prepareConfiguration(array $configuration, $class, array $scopeConfig = null)
    {
        $classMetadata = $this->getClassMetadata($class);

        if (isset($configuration['properties'])) {
            $properties = $configuration['properties'];

            // Set ids
            $identifierFieldNames = $classMetadata->getIdentifierFieldNames();
            foreach ($properties as $name => $value) {
                foreach ($identifierFieldNames as $idName) {
                    if (!array_key_exists($idName, $properties)) {
                        $properties[$idName] = ['hidden' => true];
                        $configuration['properties'][$idName] = $properties[$idName];
                    }
                }
            }

            foreach ($properties as $name => $value) {
                $isRemove =
                    $scopeConfig !== null &&
                    !in_array($name, $identifierFieldNames) &&
                    empty($value['hidden']) &&
                    !array_key_exists($name, $scopeConfig)
                ;

                if ($isRemove) {
                    unset($configuration['properties'][$name]);
                    continue;
                }

                if (isset($value['properties'])) {
                    // class
                    $class = $classMetadata->getAssociationTargetClass($name);
                    $configuration['properties'][$name]['class'] = $class;

                    // type
                    $associationMapping = $classMetadata->getAssociationMapping($name);
                    $associationType = $associationMapping['type'];
                    $type = in_array($associationType, [ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY]) ? 'collection' : 'entity';
                    $configuration['properties'][$name]['type'] = $type;

                    // properties
                    $preparedConfiguration = $this->prepareConfiguration($value, $class, $scopeConfig[$name]);
                    $configuration['properties'][$name]['properties'] = $preparedConfiguration['properties'];

                } else {
                    if (!isset($value['type'])) {
                        $configuration['properties'][$name]['type'] = $classMetadata->getTypeOfField($name);
                    }

                    $configuration['properties'][$name]['from_db'] = (bool)$classMetadata->getTypeOfField($name);
                }
            }
        }

        return $configuration;
    }

    /**
     * @param string $class
     * @return ClassMetadata
     */
    protected function getClassMetadata($class)
    {
        if (!isset($this->classMetadataCache[$class])) {
            $classMetadata = $this->doctrine->getManager()->getClassMetadata($class);

            $this->classMetadataCache[$class] = $classMetadata;
        }

        return $this->classMetadataCache[$class];
    }

    /**
     * @param mixed  $value
     * @param string $decodeString
     * @param array  $data
     * @return mixed
     */
    protected function decode($value, $decodeString, array $data)
    {
        $dataTransformerNames = explode('|', $decodeString);
        $dataTransformerNames = array_map('trim', $dataTransformerNames);

        foreach ($dataTransformerNames as $dataTransformerName) {
            $hasDataTransformer = $this->dataTransformerRegistry->has($dataTransformerName);

            if ($hasDataTransformer) {
                $transformer = $this->dataTransformerRegistry->get($dataTransformerName) ;
                $value = $transformer->transform($value, $data);
            }
        }

        return $value;
    }

    /**
     * @param array $properties
     * @return array
     */
    public static function getDatabaseFields(array $properties)
    {
        $databaseFields = [];
        foreach ($properties as $propertyName => $propertyData) {
            $isValid = (isset($propertyData['from_db']) && $propertyData['from_db']);

            if ($isValid) {
                $databaseFields[] = $propertyName;
            }

            if (isset($propertyData['source'])) {
                $databaseFields[] = $propertyData['source'];
            }
        }

        return $databaseFields;
    }

    /**
     * @param string  $alias
     * @return JoinMap
     */
    public function createJoinMap($alias)
    {
        $joinMap          = new JoinMap($alias);
        $dataSchemaConfig = $this->getConfiguration();

        $joins = $this->getJoinsByConfig($dataSchemaConfig, $alias);
        foreach ($joins as $fullPath => $joinData) {
            $pathElements = explode('.', $fullPath);
            $field = array_pop($pathElements);
            $path  = implode('.', $pathElements);

            if (($key = array_search($path, $joins)) !== false) {
                $path = $key;
            }

            $joinFields = $joinData['fields'];
            $joinType   = $joinData['joinType'];
            $joinMap->join($path, $field, true, $joinFields, $joinType);
        }

        return $joinMap;
    }

    /**
     * @param array $config
     * @param string $firstAlias
     * @param string $alias
     * @param array $result
     * @return array
     */
    private function getJoinsByConfig(array $config, $firstAlias, $alias = null, &$result = [])
    {
        if (!$alias) {
            $alias = $firstAlias;
        }

        if (isset($config['properties'])) {
            $properties = $config['properties'];
            foreach ($properties as $key => $value) {
                if (isset($value['properties'])) {
                    $joinType = isset($value['join']) && $value['join'] != 'none' ? $value['join'] : false;

                    if (!$joinType) {
                        continue;
                    }

                    $join       = $alias . '.' . $key;
                    $joinAlias  = str_replace('.', '_', $join);

                    // Join fields
                    $joinFields = DataSchema::getDatabaseFields($value['properties']);

                    $result[$join] = [
                        'alias'    => $joinAlias,
                        'fields'   => $joinFields,
                        'joinType' => $joinType
                    ];

                    $this->getJoinsByConfig($value, $firstAlias, $joinAlias, $result);
                }
            }
        }

        return $result;
    }
}
