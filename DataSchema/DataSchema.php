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
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Glavweb\DatagridBundle\DataSchema\Persister\PersisterFactory;
use Glavweb\DatagridBundle\DataSchema\Persister\PersisterInterface;
use Glavweb\DatagridBundle\DataTransformer\DataTransformerRegistry;
use Glavweb\DatagridBundle\DataTransformer\TransformEvent;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\SecurityBundle\Security\AccessHandler;
use Glavweb\SecurityBundle\Security\QueryBuilderFilter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class DataSchema
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
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
     * @var PersisterInterface
     */
    private $persister;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var QueryBuilderFilter
     */
    private $accessQbFilter;

    /**
     * @var Placeholder
     */
    private $placeholder;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @var ClassMetadata[]
     */
    private $classMetadataCache;

    /**
     * @var bool
     */
    private $securityEnabled;

    /**
     * DataSchema constructor.
     *
     * @param Registry $doctrine
     * @param DataTransformerRegistry $dataTransformerRegistry
     * @param PersisterFactory $persisterFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AccessHandler $accessHandler
     * @param QueryBuilderFilter $accessQbFilter
     * @param Placeholder $placeholder
     * @param array $configuration
     * @param array $scopeConfig
     * @param bool $securityEnabled
     */
    public function __construct(Registry $doctrine, DataTransformerRegistry $dataTransformerRegistry, PersisterFactory $persisterFactory, AuthorizationCheckerInterface $authorizationChecker, AccessHandler $accessHandler, QueryBuilderFilter $accessQbFilter, Placeholder $placeholder, array $configuration, array $scopeConfig = null, $securityEnabled = true)
    {
        $this->doctrine                = $doctrine;
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->authorizationChecker    = $authorizationChecker;
        $this->accessHandler           = $accessHandler;
        $this->accessQbFilter          = $accessQbFilter;
        $this->placeholder             = $placeholder;
        $this->securityEnabled         = $securityEnabled;

        if (!isset($configuration['class'])) {
            throw new \RuntimeException('Option "class" must be defined.');
        }
        $class = $configuration['class'];

        if (!isset($configuration['db_driver'])) {
            throw new \RuntimeException('Option "db_driver" must be defined.');
        }
        $this->persister = $persisterFactory->createPersister($configuration['db_driver'], $this);

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
     * @param array  $data
     * @param array  $config
     * @param string $parentClassName
     * @param string $parentPropertyName
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function getData(array $data, array $config = null, $parentClassName = null, $parentPropertyName = null)
    {
        if ($config === null) {
            $config = $this->configuration;
        }

        if (!isset($config['properties'])) {
            return [];
        }

        $preparedData = [];

        foreach ($config['properties'] as $propertyName => $propertyConfig) {
            if (isset($propertyConfig['hidden']) && $propertyConfig['hidden'] == true) {
                continue;
            }

            if (isset($data[$propertyName])) {
                $value = $data[$propertyName];

            } elseif (isset($propertyConfig['source']) && isset($data[$propertyConfig['source']])) {
                $value = $data[$propertyConfig['source']];

            } elseif (isset($propertyConfig['properties']) && isset($propertyConfig['class'])) {
                $metadata = $this->getClassMetadata($config['class']);
                if (!$metadata->hasAssociation($propertyName)) {
                    continue;
                }

                $associationMapping = $metadata->getAssociationMapping($propertyName);
                $databaseFields = self::getDatabaseFields($propertyConfig['properties']);
                $conditions     = $propertyConfig['conditions'];

                switch ($associationMapping['type']) {
                    case ClassMetadata::MANY_TO_MANY:
                        $modelData = $this->persister->getManyToManyData($associationMapping, $data['id'], $databaseFields, $conditions);
                        $preparedData[$propertyName] = $this->getList(
                            $modelData,
                            $propertyConfig,
                            $config['class'],
                            $propertyName
                        );

                        break;

                    case ClassMetadata::ONE_TO_MANY:
                        $modelData = $this->persister->getOneToManyData($associationMapping, $data['id'], $databaseFields, $conditions);
                        $preparedData[$propertyName] = $this->getList(
                            $modelData,
                            $propertyConfig,
                            $config['class'],
                            $propertyName
                        );

                        break;

                    case ClassMetadata::MANY_TO_ONE:
                        $modelData = $this->persister->getManyToOneData($associationMapping, $data['id'], $databaseFields, $conditions);
                        $preparedData[$propertyName] = $this->getData(
                            $modelData,
                            $propertyConfig,
                            $config['class'],
                            $propertyName
                        );

                        break;

                    case ClassMetadata::ONE_TO_ONE:
                        $modelData = $this->persister->getOneToOneData($associationMapping, $data['id'], $databaseFields, $conditions);
                        $preparedData[$propertyName] = $this->getData(
                            $modelData,
                            $propertyConfig,
                            $config['class'],
                            $propertyName
                        );

                        break;
                }

                continue;

            } else {
                continue;
            }

            if (is_array($value)) {
                $subConfig = $config['properties'][$propertyName];

                if ($subConfig['type'] == 'entity') {
                    $preparedData[$propertyName] = $this->getData(
                        $value,
                        $config['properties'][$propertyName],
                        $config['class'],
                        $propertyName
                    );

                    continue;

                } elseif ($subConfig['type'] == 'collection') {
                    foreach ($value as $subKey => $subInfo) {
                        $preparedData[$propertyName][$subKey] = $this->getData(
                            $subInfo,
                            $config['properties'][$propertyName],
                            $config['class'],
                            $propertyName
                        );
                    }

                    continue;
                }
            }

            if (isset($propertyConfig['decode'])) {
                $transformEvent = new TransformEvent($config['class'], $propertyName, $propertyConfig, $parentClassName, $parentPropertyName);
                $value = $this->decode($value, $propertyConfig['decode'], $data, $transformEvent);
            }

            $preparedData[$propertyName] = $value;
        }

        return $preparedData;
    }

    /**
     * @param array  $list
     * @param array  $config
     * @param string $parentClassName
     * @param string $parentPropertyName
     * @return array
     */
    public function getList(array $list, array $config = null, $parentClassName = null, $parentPropertyName = null)
    {
        if ($config === null) {
            $config = $this->configuration;
        }

        foreach ($list as $key => $value) {
            $list[$key] = $this->getData($value, $config, $parentClassName, $parentPropertyName);
        }

        return $list;
    }

    /**
     * @param array  $configuration
     * @param string $class
     * @param array  $scopeConfig
     * @param bool   $glavwebSecurity
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function prepareConfiguration(array $configuration, $class, array $scopeConfig = null, $glavwebSecurity = false)
    {
        $classMetadata = $this->getClassMetadata($class);

        $glavwebSecurity = $this->securityEnabled ?
            isset($configuration['glavweb_security']) ? $configuration['glavweb_security'] : $glavwebSecurity :
            false
        ;

        // roles
        if (!isset($configuration['roles'])) {
            $configuration['roles'] = [];
        }

        if ($glavwebSecurity) {
            $configuration['roles'] = array_merge(
                $configuration['roles'],
                $this->getSecurityRoles($class)
            );
        }

        $isGranted = $this->isGranted($configuration['roles']);
        if (!$isGranted) {
            return [];
        }

        // class
        $configuration['class'] = $class;

        // condition
        if (!isset($configuration['conditions'])) {
            $configuration['conditions'] = [];
        }

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
                    $class = $classMetadata->getAssociationTargetClass($name);

                    $preparedConfiguration = $this->prepareConfiguration($value, $class, $scopeConfig[$name], $glavwebSecurity);
                    $configuration['properties'][$name] = $preparedConfiguration;

                    // type
                    if ($preparedConfiguration) {
                        $associationMapping = $classMetadata->getAssociationMapping($name);
                        $associationType = $associationMapping['type'];

                        $type = in_array($associationType, [ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY]) ? 'collection' : 'entity';
                        $configuration['properties'][$name]['type'] = $type;
                    }

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
     * @param mixed          $value
     * @param string         $decodeString
     * @param array          $data
     * @param TransformEvent $transformEvent
     * @return mixed
     */
    protected function decode($value, $decodeString, array $data, TransformEvent $transformEvent)
    {
        $dataTransformerNames = explode('|', $decodeString);
        $dataTransformerNames = array_map('trim', $dataTransformerNames);

        foreach ($dataTransformerNames as $dataTransformerName) {
            $hasDataTransformer = $this->dataTransformerRegistry->has($dataTransformerName);

            if ($hasDataTransformer) {
                $transformer = $this->dataTransformerRegistry->get($dataTransformerName);
                $value = $transformer->transform($value, $data, $transformEvent);
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

            $joinFields    = $joinData['fields'];
            $joinType      = $joinData['joinType'];
            $conditionType = $joinData['conditionType'];
            $condition     = $joinData['condition'];

            $joinMap->join($path, $field, true, $joinFields, $joinType, $conditionType, $condition);
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

                    $join      = $alias . '.' . $key;
                    $joinAlias = str_replace('.', '_', $join);

                    // Join fields
                    $joinFields = DataSchema::getDatabaseFields($value['properties']);

                    $conditionType = isset($value['conditionType']) ? $value['conditionType'] : Join::WITH;
                    $conditions    = isset($value['conditions']) ? $value['conditions'] : [];

                    $preparedConditions = [];
                    foreach ($conditions as $condition) {
                        if ($condition) {
                            $preparedCondition = $this->conditionPlaceholder($condition, $joinAlias);
                            if ($preparedCondition) {
                                $preparedConditions[] = '(' . $preparedCondition . ')';
                            }
                        }
                    }
                    $condition = implode('AND', $preparedConditions);

                    $result[$join] = [
                        'alias'         => $joinAlias,
                        'fields'        => $joinFields,
                        'joinType'      => $joinType,
                        'conditionType' => $conditionType,
                        'condition'     => $condition,

                    ];

                    $this->getJoinsByConfig($value, $firstAlias, $joinAlias, $result);
                }
            }
        }

        return $result;
    }

    /**
     * @param array $roles
     * @return bool
     */
    protected function isGranted(array $roles)
    {
        if (empty($roles)) {
            return true;
        }

        foreach ($roles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $class
     * @return array
     */
    public function getSecurityRoles($class)
    {
        $roles = [];

        $masterListRole = $this->accessHandler->getRole($class, 'LIST');
        if ($masterListRole) {
            $roles[] = $masterListRole;
        }

        return $roles;
    }

    /**
     * @param string $condition
     * @param string $alias
     * @param UserInterface $user
     * @return string
     */
    public function conditionPlaceholder($condition, $alias, UserInterface $user = null)
    {
        return $this->placeholder->condition($condition, $alias, $user);
    }
}
