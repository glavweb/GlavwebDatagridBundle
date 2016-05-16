<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\Builder\DatagridBuilderInterface;
use Glavweb\DatagridBundle\Datagrid\DatagridInterface;
use Glavweb\DatagridBundle\Datagrid\Doctrine\Datagrid;
use Glavweb\DatagridBundle\Datagrid\Doctrine\NativeSqlDatagrid;
use Glavweb\DatagridBundle\Datagrid\EmptyDatagrid;
use Glavweb\DatagridBundle\DataSchema\DataSchema;
use Glavweb\DatagridBundle\DataSchema\DataSchemaFactory;
use Glavweb\DatagridBundle\Exception\Exception;
use Glavweb\DatagridBundle\Filter\Doctrine\FilterFactory;
use Glavweb\DatagridBundle\Filter\Doctrine\Filter;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class Builder
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridBuilder implements DatagridBuilderInterface
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var FilterFactory
     */
    protected $filterFactory;

    /**
     * @var DataSchemaFactory
     */
    private $dataSchemaFactory;

    /**
     * @var array
     */
    private $orderings;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var array
     */
    private $operators = [];

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $entityClassName;

    /**
     * @var JoinMap
     */
    private $joinMap;

    /**
     * @var DataSchema
     */
    private $dataSchema;

    /**
     * @var Filter[]
     */
    private $filters = [];

    /**
     * @var array
     */
    private $filterNamesByParams = [];

    /**
     * DoctrineDatagridBuilder constructor.
     *
     * @param Registry $doctrine
     * @param FilterFactory $filterFactory
     * @param DataSchemaFactory $dataSchemaFactory
     */
    public function __construct(Registry $doctrine, FilterFactory $filterFactory, DataSchemaFactory $dataSchemaFactory)
    {
        $this->doctrine          = $doctrine;
        $this->filterFactory     = $filterFactory;
        $this->dataSchemaFactory = $dataSchemaFactory;
    }

    /**
     * @param array $orderings
     * @return $this
     */
    public function setOrderings($orderings)
    {
        $this->orderings = $orderings;

        return $this;
    }

    /**
     * @return array
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * @param int $firstResult
     *
     * @return $this
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * @param int $maxResults
     *
     * @return $this
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @param array $operators
     * @return $this
     */
    public function setOperators(array $operators)
    {
        $this->operators = $operators;

        return $this;
    }

    /**
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param mixed $entityClassName
     *
     * @return $this
     */
    public function setEntityClassName($entityClassName)
    {
        $this->entityClassName = $entityClassName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * @param JoinMap $joinMap
     *
     * @return $this
     */
    public function setJoinMap(JoinMap $joinMap)
    {
        if ($this->joinMap) {
            $this->joinMap->merge($joinMap);

        } else {
            $this->joinMap = $joinMap;
        }

        return $this;
    }

    /**
     * @return JoinMap|null
     */
    public function getJoinMap()
    {
        return $this->joinMap;
    }

    /**
     * @param Filter[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters = [])
    {
        foreach ($filters as $filterName => $filterValue) {
            $this->addFilter($filterName, $filterValue);
        }

        return $this;
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $filterName
     * @return Filter
     */
    public function getFilter($filterName)
    {
        return $this->filters[$filterName];
    }

    /**
     * @param string $filterName
     * @param string $type
     * @param array $options
     * @return $this
     */
    public function addFilter($filterName, $type = null, $options = [])
    {
        $entityClass = $this->getEntityClassName();
        $alias       = $this->getAlias();

        $filter = $this->filterFactory->createForEntity($entityClass, $alias, $filterName, $type, $options);
        $this->fixFilter($filter);

        $paramName = $filter->getParamName();
        $this->filters[$filterName]            = $filter;
        $this->filterNamesByParams[$paramName] = $filterName;

        return $this;
    }

    /**
     * @param string $dataSchemaFile
     * @param string $scopeFile
     */
    public function setDataSchema($dataSchemaFile, $scopeFile = null)
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema($dataSchemaFile, $scopeFile);
        $this->dataSchema = $dataSchema;
    }

    /**
     * @param array $parameters
     * @param \Closure $callback
     * @return Datagrid
     */
    public function build(array $parameters = [], $callback = null)
    {
        $orderings   = $this->getOrderings();
        $firstResult = $this->getFirstResult();
        $maxResults  = $this->getMaxResults();
        $alias       = $this->getAlias();

        try {
            $queryBuilder = $this->createQueryBuilder($parameters);

            if (is_callable($callback)) {
                $callback($queryBuilder, $alias);
            }

            $datagrid = new Datagrid($queryBuilder, $this->dataSchema, $orderings, $firstResult, $maxResults, $alias);

        } catch (Exception $e) {
            $datagrid = new EmptyDatagrid();
        }

        return $datagrid;
    }

    /**
     * @param \Closure $callback
     * @param array $parameters
     * @return DatagridInterface
     */
    public function buildNativeSql($callback, array $parameters = [])
    {
        $orderings   = $this->getOrderings();
        $firstResult = $this->getFirstResult();
        $maxResults  = $this->getMaxResults();
        $alias       = $this->getAlias();

        try {
            $em           = $this->doctrine->getManager();
            $queryBuilder = $this->createQueryBuilder($parameters);
            $subQuery     = $this->buildSql($queryBuilder);
            $rsm          = $this->createResultSetMapping($this->getEntityClassName(), $alias);

            if (!is_callable($callback)) {
                throw new \RuntimeException('Callback must be callable.');
            }

            $query = $callback($subQuery, $rsm, $em);
            if (!$query instanceof NativeQuery) {
                throw new \RuntimeException('Callback must be return instance of Doctrine\ORM\NativeQuery.');
            }

            // Add conditions
            $this->addNativeConditions($parameters, $rsm, $query);

            $orderings = $this->transformOrderingForNativeSql((array)$orderings, $rsm);
            $this->setOrderings($orderings);

            $datagrid = new NativeSqlDatagrid($query, $this->dataSchema, $orderings, $firstResult, $maxResults, $alias);

        } catch (Exception $e) {
            $datagrid = new EmptyDatagrid();
        }

        return $datagrid;
    }

    /**
     * @param array $parameters
     * @return QueryBuilder
     */
    protected function createQueryBuilder(array $parameters)
    {
        /** @var ClassMetadata $classMetadata */
        $repository    = $this->getEntityRepository();
        $alias         = $this->getAlias();

        // Create query builder
        $queryBuilder  = $repository->createQueryBuilder($alias);

        // Apply joins
        $joinMap = $this->getJoinMap();

        if ($this->dataSchema) {
            $joinMapFromDataSchema = $this->createJoinMapByDataSchema($this->dataSchema);
            if ($joinMapFromDataSchema) {
                if ($joinMap) {
                    $joinMap->merge($joinMapFromDataSchema);
                } else {
                    $joinMap = $joinMapFromDataSchema;
                }
            }
        }

        if ($joinMap) {
            $joinMap->apply($queryBuilder);
        }

        // Apply filter
        $parameters = $this->clearParameters($parameters);
        foreach ($parameters as $key => $parameter) {
            if (!$parameter || !is_scalar($parameter)) {
                continue;
            }

            $jsonDecoded = json_decode($parameter);

            if (json_last_error() == JSON_ERROR_NONE) {
                $parameters[$key] = $jsonDecoded;
            }
        }

        foreach ($parameters as $name => $value) {
            $filter = $this->getFilterByParam($name);

            if (!$filter) {
                continue;
            }

            $filter->filter($queryBuilder, $alias, $value);
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param bool         $replaceSelect
     * @return string
     */
    public function buildSql(QueryBuilder $queryBuilder, $replaceSelect = true)
    {
        $sql = $queryBuilder->getQuery()->getSQL();

        if ($replaceSelect) {
            $result = preg_match('/SELECT .*? FROM [\w]* ([^ ]*)/', $sql, $matches);
            if (!$result) {
                throw new \RuntimeException('Alias not found.');
            }

            $alias = $matches[1];
            $sql = preg_replace('/SELECT .*? FROM/', 'SELECT ' . $alias . '.* FROM', $sql);
        }

        return $sql;
    }

    /**
     * @param string $class
     * @param string $alias
     * @return ResultSetMapping
     */
    protected function createResultSetMapping($class, $alias)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult($class, $alias);

        $classMetaData = $em->getClassMetadata($class);
        $fieldNames = $classMetaData->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $rsm->addFieldResult($alias, $classMetaData->getColumnName($fieldName), $fieldName);
        }

        return $rsm;
    }

    /**
     * @param array $fields
     * @param ResultSetMapping $rsm
     * @param NativeQuery $query
     */
    private function addNativeConditions(array $fields, ResultSetMapping $rsm, NativeQuery $query)
    {
        $expr = new Query\Expr();

        $whereParts = [];
        $scalarMappings = $rsm->scalarMappings;
        foreach ($scalarMappings as $fieldAlias => $fieldName) {
            if (!isset($fields[$fieldName])) {
                continue;
            }

            $value = $fields[$fieldName];
            list($operator, $value) = Filter::guessOperator($value);

            if ($operator == Filter::CONTAINS) {
                $whereParts[] = $expr->like($fieldAlias, $expr->literal("%$value%"));

            } elseif ($operator == Filter::NOT_CONTAINS) {
                $whereParts[] = $expr->notLike($fieldAlias, $expr->literal("%$value%"));

            } elseif ($operator == Filter::IN) {
                $whereParts[] = $expr->in($fieldAlias, $value);

            } elseif ($operator == Filter::NIN) {
                $whereParts[] = $expr->notIn($fieldAlias, $value);

            } else {
                $whereParts[] = new Comparison($fieldAlias, $operator, $expr->literal($value));
            }
        }

        $uniqueAlias = 'a_' . uniqid();
        $sql = 'SELECT ' . $uniqueAlias . '.* FROM (' . $query->getSQL() . ') AS ' . $uniqueAlias;

        if ($whereParts) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        $query->setSQL($sql);
    }

    /**
     * @param array $orderings
     * @param ResultSetMapping $rsm
     * @return array
     */
    private function transformOrderingForNativeSql(array $orderings, ResultSetMapping $rsm)
    {
        $scalarMappings = $rsm->scalarMappings;
        foreach ($orderings as $fieldName => $sort) {
            if (($alias = array_search($fieldName, $scalarMappings)) !== false) {
                unset($orderings[$fieldName]);
                $orderings[$alias] = $sort;
            }
        }

        return $orderings;
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->doctrine->getRepository($this->getEntityClassName());
    }

    /**
     * @param $parameters
     * @return array
     */
    protected function clearParameters(array $parameters)
    {
        $parameters = array_filter($parameters, function ($value) {
            if (is_array($value) && empty($value)) {
                return false;
            }
            
            return $value !== null;
        });

        return $parameters;
    }

    /**
     * @return ClassMetadata
     */
    protected function getClassMetadata()
    {
        $em = $this->doctrine->getManager();

        return $em->getClassMetadata($this->getEntityClassName());
    }

    /**
     * @param string $name
     * @return Filter|null
     */
    protected function getFilterByParam($name)
    {
        if (!isset($this->filterNamesByParams[$name])) {
            return null;
        }

        $filterName = $this->filterNamesByParams[$name];

        return $this->getFilter($filterName);
    }

    /**
     * @param Filter $filter
     */
    private function fixFilter(Filter $filter)
    {
        $paramName = $filter->getParamName();
        if (isset($this->operators[$paramName])) {
            $options = $filter->getOptions();
            $options['operator'] = $this->operators[$paramName];

            $filter->setOptions($options);
        }
    }

    /**
     * @param DataSchema $dataSchema
     * @param JoinMap $joinMap
     * @return JoinMap
     */
    protected function createJoinMapByDataSchema(DataSchema $dataSchema, JoinMap $joinMap = null)
    {
        $alias = $this->alias;

        if (!$alias) {
            throw new \RuntimeException('Alias not defined.');
        }

        if (!$joinMap) {
            $joinMap = new JoinMap($alias);
        }

        $dataSchemaConfig = $dataSchema->getConfiguration();
        $joins = $this->getJoinsByDataSchemaConfig($dataSchemaConfig, $alias);
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
    protected function getJoinsByDataSchemaConfig(array $config, $firstAlias, $alias = null, &$result = [])
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
                    $joinFields = [];
                    foreach ($value['properties'] as $propertyName => $propertyData) {
                        $isValid = (isset($propertyData['from_db']) && $propertyData['from_db']);

                        if ($isValid) {
                            $joinFields[] = $propertyName;
                        }

                        if (isset($propertyData['source'])) {
                            $joinFields[] = $propertyData['source'];
                        }
                    }

                    $result[$join] = [
                        'alias'    => $joinAlias,
                        'fields'   => $joinFields,
                        'joinType' => $joinType
                    ];

                    $alias = $joinAlias;
                    $this->getJoinsByDataSchemaConfig($value, $firstAlias, $alias, $result);
                    $alias = $firstAlias;
                }
            }
        }

        return $result;
    }
}