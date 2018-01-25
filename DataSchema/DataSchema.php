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
use Glavweb\DatagridBundle\Exception\DataSchema\InvalidConfigurationException;
use Glavweb\DatagridBundle\Hydrator\Doctrine\ObjectHydrator;
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
     * @var DataSchemaFactory
     */
    private $dataSchemaFactory;

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
     * @var array
     */
    private $scopeConfig = [];

    /**
     * @var ClassMetadata[]
     */
    private $classMetadataCache;

    /**
     * @var bool
     */
    private $securityEnabled;

    /**
     * @var bool
     */
    private $withoutAssociations;

    /**
     * @var string|null
     */
    private $defaultHydratorMode;

    /**
     * @var ObjectHydrator
     */
    private $objectHydrator;

    /**
     * DataSchema constructor.
     *
     * @param DataSchemaFactory             $dataSchemaFactory
     * @param Registry                      $doctrine
     * @param DataTransformerRegistry       $dataTransformerRegistry
     * @param PersisterFactory              $persisterFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AccessHandler                 $accessHandler
     * @param QueryBuilderFilter            $accessQbFilter
     * @param Placeholder                   $placeholder
     * @param ObjectHydrator                $objectHydrator
     * @param array                         $configuration
     * @param array                         $scopeConfig
     * @param bool                          $securityEnabled
     * @param bool                          $withoutAssociations
     * @param string|null                   $defaultHydratorMode
     */
    public function __construct(
        DataSchemaFactory $dataSchemaFactory,
        Registry $doctrine,
        DataTransformerRegistry $dataTransformerRegistry,
        PersisterFactory $persisterFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        AccessHandler $accessHandler,
        QueryBuilderFilter $accessQbFilter,
        Placeholder $placeholder,
        ObjectHydrator $objectHydrator,
        array $configuration,
        array $scopeConfig = null,
        bool $securityEnabled = true,
        bool $withoutAssociations = false,
        $defaultHydratorMode = null
    ) {
        $this->dataSchemaFactory       = $dataSchemaFactory;
        $this->doctrine                = $doctrine;
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->authorizationChecker    = $authorizationChecker;
        $this->accessHandler           = $accessHandler;
        $this->accessQbFilter          = $accessQbFilter;
        $this->placeholder             = $placeholder;
        $this->objectHydrator          = $objectHydrator;
        $this->securityEnabled         = $securityEnabled;
        $this->withoutAssociations     = $withoutAssociations;
        $this->defaultHydratorMode     = $defaultHydratorMode;

        if (!isset($configuration['class'])) {
            $configuration['class'] = null;
        }
        $class = $configuration['class'];

        if (!isset($configuration['db_driver'])) {
            throw new \RuntimeException('Option "db_driver" must be defined.');
        }
        $this->persister = $persisterFactory->createPersister($configuration['db_driver'], $this);

        $this->scopeConfig = $scopeConfig;
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
     * @param array  $scopeConfig
     * @param string $parentClassName
     * @param string $parentPropertyName
     * @param array  $defaultData
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function getData(array $data, array $config = null, array $scopeConfig = null, $parentClassName = null, $parentPropertyName = null, $defaultData = [])
    {
        if ($config === null) {
            $config = $this->configuration;
        }

        if ($scopeConfig === null) {
            $scopeConfig = $this->scopeConfig;
        }

        if (!$data) {
            return $defaultData;
        }

        if (!isset($config['properties'])) {
            return $defaultData;
        }

        $preparedData = [];

        $class = $config['class'];
        if ($config['discriminatorMap'] && isset($data[$config['discriminatorColumnName']])) {
            $discriminator = $data[$config['discriminatorColumnName']];
            $class = $config['discriminatorMap'][$discriminator];
        }

        foreach ($config['properties'] as $propertyName => $propertyConfig) {
            if (isset($propertyConfig['hidden']) && $propertyConfig['hidden'] == true) {
                continue;
            }

            $propertyScopeConfig = null;
            if ($scopeConfig !== null) {
                if (!array_key_exists($propertyName, $scopeConfig)) {
                    continue;
                }

                $propertyScopeConfig = $scopeConfig[$propertyName] ?: [];
            }

            if (array_key_exists($propertyName, $data)) {
                $value = $data[$propertyName];

            } elseif (isset($propertyConfig['source']) && isset($data[$propertyConfig['source']])) {
                $value = $data[$propertyConfig['source']];

                // if property is nested object
            } elseif (isset($propertyConfig['class']) && isset($propertyConfig['properties'])) {
                $metadata = $this->getClassMetadata($class);
                if (!$metadata->hasAssociation($propertyName)) {
                    continue;
                }

                $associationMapping = $metadata->getAssociationMapping($propertyName);
                $databaseFields = self::getDatabaseFields($propertyConfig['properties']);
                $conditions     = $propertyConfig['conditions'];

                switch ($associationMapping['type']) {
                    case ClassMetadata::MANY_TO_MANY:
                        $orderByExpressions = isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : [];

                        $modelData = $this->persister->getManyToManyData(
                            $associationMapping,
                            $data['id'],
                            $databaseFields,
                            $conditions,
                            $orderByExpressions
                        );

                        $preparedData[$propertyName] = $this->getList(
                            $modelData,
                            $propertyConfig,
                            $propertyScopeConfig,
                            $class,
                            $propertyName
                        );

                        break;

                    case ClassMetadata::ONE_TO_MANY:
                        $orderByExpressions = isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : [];

                        $modelData = $this->persister->getOneToManyData(
                            $associationMapping,
                            $data['id'],
                            $databaseFields,
                            $conditions,
                            $orderByExpressions
                        );

                        $preparedData[$propertyName] = $this->getList(
                            $modelData,
                            $propertyConfig,
                            $propertyScopeConfig,
                            $class,
                            $propertyName
                        );

                        break;

                    case ClassMetadata::MANY_TO_ONE:
                        $modelData = $this->persister->getManyToOneData($associationMapping, $data['id'], $databaseFields, $conditions);
                        $preparedData[$propertyName] = $this->getData(
                            $modelData,
                            $propertyConfig,
                            $propertyScopeConfig,
                            $class,
                            $propertyName,
                            null
                        );

                        break;

                    case ClassMetadata::ONE_TO_ONE:
                        $modelData = $this->persister->getOneToOneData($associationMapping, $data['id'], $databaseFields, $conditions);
                        $preparedData[$propertyName] = $this->getData(
                            $modelData,
                            $propertyConfig,
                            $propertyScopeConfig,
                            $class,
                            $propertyName,
                            null
                        );

                        break;
                }

                continue;

            } else {
                $value = null;
            }

            if (is_array($value)) {
                if (!array_key_exists('type', $propertyConfig)) {
                    throw new \RuntimeException('Option "type" must be defined.');
                }

                if ($propertyConfig['type'] == 'entity') {
                    $preparedData[$propertyName] = $this->getData(
                        $value,
                        $propertyConfig,
                        $propertyScopeConfig,
                        $class,
                        $propertyName,
                        null
                    );

                    continue;

                } elseif ($propertyConfig['type'] == 'collection') {
                    $preparedData[$propertyName] = $this->getList(
                        $value,
                        $propertyConfig,
                        $propertyScopeConfig,
                        $class,
                        $propertyName
                    );

                    continue;
                }
            }

            if (isset($propertyConfig['decode'])) {
                $transformEvent = new TransformEvent(
                    $class,
                    $propertyName,
                    $propertyConfig,
                    $parentClassName,
                    $parentPropertyName,
                    $data,
                    $this->objectHydrator,
                    $this->dataSchemaFactory
                );
                $value = $this->decode($value, $propertyConfig['decode'], $transformEvent);

                if (is_array($value) && $propertyScopeConfig) {
                    $value = $this->getScopedData(
                        $value,
                        $propertyScopeConfig
                    );
                }
            }

            $preparedData[$propertyName] = $value;
        }

        return $preparedData;
    }

    /**
     * @param array  $list
     * @param array  $config
     * @param array  scopeConfig
     * @param string $parentClassName
     * @param string $parentPropertyName
     * @return array
     */
    public function getList(array $list, array $config = null, array $scopeConfig = null, $parentClassName = null, $parentPropertyName = null)
    {
        if ($config === null) {
            $config = $this->configuration;
        }

        if ($scopeConfig === null) {
            $scopeConfig = $this->scopeConfig;
        }

        foreach ($list as $key => $value) {
            $list[$key] = $this->getData(
                $value,
                $config,
                $scopeConfig,
                $parentClassName,
                $parentPropertyName,
                null
            );
        }

        return $list;
    }

    /**
     * @param array  $configuration
     * @param string $class
     * @param array  $scopeConfig
     * @param bool   $glavwebSecurity
     * @return array
     * @throws InvalidConfigurationException
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function prepareConfiguration(array $configuration, $class = null, array $scopeConfig = null, $glavwebSecurity = false)
    {
        $classMetadata = $class ? $this->getClassMetadata($class) : null;

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
        $configuration['class']                   = $class;
        $configuration['discriminatorColumnName'] = null;
        $configuration['discriminatorMap']        = [];

        if ($classMetadata instanceof ClassMetadata && $classMetadata->subClasses) {
            $configuration['discriminatorColumnName'] = $classMetadata->discriminatorColumn['name'];
            $configuration['discriminatorMap']        = $classMetadata->discriminatorMap;
        }

        // condition
        if (!isset($configuration['conditions'])) {
            $configuration['conditions'] = [];
        }

        // inject properties
        if (isset($configuration['schema']) && !$this->withoutAssociations) {
            $configuration = $this->injectDataSchema($configuration['schema'], $configuration);
        }

        if (isset($configuration['properties'])) {
            $properties = $configuration['properties'];

            // Set ids
            $identifierFieldNames = $classMetadata instanceof ClassMetadata ?
                $classMetadata->getIdentifierFieldNames() :
                []
            ;

            foreach ($properties as $propertyName => $propertyConfig) {
                foreach ($identifierFieldNames as $idName) {
                    if (!array_key_exists($idName, $properties)) {
                        $properties[$idName] = ['hidden' => true];
                        $configuration['properties'][$idName] = $properties[$idName];
                    }
                }
            }

            foreach ($properties as $propertyName => $propertyConfig) {
                $isAssociationField =
                    isset($propertyConfig['properties']) ||
                    isset($propertyConfig['schema'])
                ;

                $isRemove =
                    $scopeConfig !== null &&
                    !in_array($propertyName, $identifierFieldNames) &&
                    empty($propertyConfig['hidden']) &&
                    !array_key_exists($propertyName, $scopeConfig)
                ;

                // if without associations
                $isRemove = $isRemove || ($this->withoutAssociations && $isAssociationField);

                if ($isRemove) {
                    unset($configuration['properties'][$propertyName]);
                    continue;
                }

                // Set default discriminator value for property
                if (!isset($configuration['properties'][$propertyName]['discriminator'])) {
                    $configuration['properties'][$propertyName]['discriminator'] = null;
                }
                $propertyConfig = $configuration['properties'][$propertyName]; // update $propertyConfig

                // If has subclasses
                $hasPropertyClassMetadata =
                    $propertyConfig['discriminator'] &&
                    isset($configuration['discriminatorMap'][$propertyConfig['discriminator']])
                ;

                $propertyClassMetadata = $classMetadata;
                if ($hasPropertyClassMetadata) {
                    $propertyClass = $configuration['discriminatorMap'][$propertyConfig['discriminator']];
                    $propertyClassMetadata = $this->getClassMetadata($propertyClass);
                }

                if ($isAssociationField) {
                    if ($propertyConfig['discriminator'] && isset($propertyConfig['join']) && $propertyConfig['join'] != 'none') {
                        throw new InvalidConfigurationException('The join type cannot be other than "none" if the discriminator is defined.');
                    }

                    $class = isset($propertyConfig['class']) ?
                        $propertyConfig['class'] :
                        ($propertyClassMetadata instanceof ClassMetadata ?
                            $propertyClassMetadata->getAssociationTargetClass($propertyName) :
                            null
                        )
                    ;

                    $preparedConfiguration = $this->prepareConfiguration($propertyConfig, $class, $scopeConfig[$propertyName], $glavwebSecurity);
                    $configuration['properties'][$propertyName] = $preparedConfiguration;

                    // type
                    if ($preparedConfiguration && $propertyClassMetadata instanceof ClassMetadata) {
                        $associationMapping = $propertyClassMetadata->getAssociationMapping($propertyName);
                        $associationType = $associationMapping['type'];

                        $type = in_array($associationType, [ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY]) ? 'collection' : 'entity';
                        $configuration['properties'][$propertyName]['type'] = $type;
                    }

                } else {
                    if (!isset($propertyConfig['type']) && $propertyClassMetadata instanceof ClassMetadata) {
                        $configuration['properties'][$propertyName]['type'] = $propertyClassMetadata->getTypeOfField($propertyName);
                    }

                    $configuration['properties'][$propertyName]['from_db'] =
                        $propertyClassMetadata instanceof ClassMetadata &&
                        (bool)$propertyClassMetadata->getTypeOfField($propertyName)
                    ;
                }
            }
        }

        return $configuration;
    }

    /**
     * @param array $value
     * @param array $scope
     * @return array
     */
    protected function getScopedData(array $data, array $scope): array
    {
        $scopedData = [];

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $scope)) {
                if (is_array($value) && $scope[$key]) {
                    $scopedData[$key] = $this->getScopedData($value, $scope[$key]);

                } else {
                    $scopedData[$key] = $value;
                }
            }
        }

        return $scopedData;
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
     * @param TransformEvent $transformEvent
     * @return mixed
     */
    protected function decode($value, $decodeString, TransformEvent $transformEvent)
    {
        $dataTransformerNames = explode('|', $decodeString);
        $dataTransformerNames = array_map('trim', $dataTransformerNames);

        foreach ($dataTransformerNames as $dataTransformerName) {
            $hasDataTransformer = $this->dataTransformerRegistry->has($dataTransformerName);

            if ($hasDataTransformer) {
                $transformer = $this->dataTransformerRegistry->get($dataTransformerName);
                $value = $transformer->transform($value, $transformEvent);
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
            $field = null;

            $isValid = (isset($propertyData['from_db']) && $propertyData['from_db']);
            if ($isValid) {
                $field = $propertyName;
            }

            if (isset($propertyData['source'])) {
                $field = $propertyData['source'];
            }

            if ($field && !in_array($field, $databaseFields)) {
                $databaseFields[] = $field;
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

            // If any of these join fields not exist in the class -> join fields is empty
            $classMetadata = $this->getClassMetadata($joinData['class']);
            $isDifferentFields = (bool)array_diff($joinFields, $classMetadata->getFieldNames());
            if ($isDifferentFields) {
                $joinFields = [];
            }

            $joinMap->join($path, $field, true, $joinFields, $joinType, $conditionType, $condition);
        }

        return $joinMap;
    }

    /**
     * @return array
     */
    public function getQuerySelects(): array
    {
        return isset($this->configuration['query']['selects']) ? $this->configuration['query']['selects'] : [];
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasProperty(string $propertyName): bool
    {
        return $this->getPropertyConfiguration($propertyName) !== null;
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasPropertyInDb(string $propertyName): bool
    {
        $propertyConfiguration = $this->getPropertyConfiguration($propertyName);

        return $propertyConfiguration !== null &&
            isset($propertyConfiguration['from_db']) && $propertyConfiguration['from_db']
        ;
    }

    /**
     * @param string $propertyName
     * @return array|null
     */
    public function getPropertyConfiguration(string $propertyName):? array
    {
        $propertyConfiguration = $this->configuration;

        $propertyNameParts = explode('.', $propertyName);
        foreach ($propertyNameParts as $propertyNamePart) {
            if (!isset($propertyConfiguration['properties'][$propertyNamePart])) {
                return null;
            }

            $propertyConfiguration = $propertyConfiguration['properties'][$propertyNamePart];
        }

        return $propertyConfiguration;
    }

    /**
     * @return string|int|null
     */
    public function getHydrationMode()
    {
        return isset($this->configuration['hydration_mode']) ? $this->configuration['hydration_mode'] : $this->defaultHydratorMode;
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
            foreach ($properties as $key => $propertyConfig) {
                if (isset($propertyConfig['properties'])) {
                    $joinType = isset($propertyConfig['join']) && $propertyConfig['join'] != 'none' ? $propertyConfig['join'] : false;

                    if (!$joinType) {
                        continue;
                    }

                    $join      = $alias . '.' . $key;
                    $joinAlias = str_replace('.', '_', $join);

                    // Join fields
                    $joinFields = DataSchema::getDatabaseFields($propertyConfig['properties']);

                    $conditionType = isset($propertyConfig['conditionType']) ? $propertyConfig['conditionType'] : Join::WITH;
                    $conditions    = isset($propertyConfig['conditions']) ? $propertyConfig['conditions'] : [];

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
                        'class'         => $propertyConfig['class'],
                        'alias'         => $joinAlias,
                        'fields'        => $joinFields,
                        'joinType'      => $joinType,
                        'conditionType' => $conditionType,
                        'condition'     => $condition,
                    ];

                    $this->getJoinsByConfig($propertyConfig, $firstAlias, $joinAlias, $result);
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

    /**
     * @param string $dataSchemaFile
     * @param array  $configuration
     * @return array
     */
    private function injectDataSchema($dataSchemaFile, array $configuration)
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema($dataSchemaFile, null, true, true);
        $injectedConfiguration = $dataSchema->getConfiguration();

        // inject properties (save source property order)
        if (isset($injectedConfiguration['properties'])) {
            $injectedProperties = $injectedConfiguration['properties'];

            $properties = isset($configuration['properties']) ? $configuration['properties'] : [];
            foreach ($properties as $propertyName => $propertyConfig) {
                $injectedProperties[$propertyName] = $propertyConfig;
            }

            $configuration['properties'] = $injectedProperties;
            unset($injectedConfiguration['properties']);
        }

        // inject rest configuration parameters
        foreach ($injectedConfiguration as $key => $value) {
            if (!array_key_exists($key, $configuration)) {
                $configuration[$key] = $value;
            }
        }

        return $configuration;
    }
}
