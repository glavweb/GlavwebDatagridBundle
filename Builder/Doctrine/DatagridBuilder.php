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
use Glavweb\DatagridBundle\Filter\FilterStack;
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
     * @var FilterStack
     */
    private $filterStack;

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
    private $alias = 't';

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
        $this->filterStack       = new FilterStack();
    }

    /**
     * @return FilterStack
     */
    public function getFilterStack()
    {
        return $this->filterStack;
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

        $this->filterStack->add($filter);

        return $this;
    }

    /**
     * @param string $filterName
     * @return Filter
     */
    public function getFilter($filterName)
    {
        return $this->filterStack->get($filterName);
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filterStack->all();
    }

    /**
     * @param string $dataSchemaFile
     * @param string $scopeFile
     * @return $this
     */
    public function setDataSchema($dataSchemaFile, $scopeFile = null)
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema($dataSchemaFile, $scopeFile);
        $this->dataSchema = $dataSchema;

        return $this;
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
            /** @var EntityManager $em */
            $em           = $this->doctrine->getManager();
            $queryBuilder = $this->createQueryBuilder($parameters);

            $datagridContext = new DatagridContext(
                $this->getEntityClassName(),
                $em,
                $queryBuilder,
                $this->filterStack,
                $this->dataSchema,
                $orderings,
                $firstResult,
                $maxResults,
                $alias
            );

            if (is_callable($callback)) {
                $callback($datagridContext);
            }

            $datagrid = new Datagrid($datagridContext);

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
            /** @var EntityManager $em */
            $em           = $this->doctrine->getManager();
            $queryBuilder = $this->createQueryBuilder($parameters);

            if (!is_callable($callback)) {
                throw new \RuntimeException('Callback must be callable.');
            }

            $datagridContext = new DatagridContext(
                $this->getEntityClassName(),
                $em,
                $queryBuilder,
                $this->filterStack,
                $this->dataSchema,
                $orderings,
                $firstResult,
                $maxResults,
                $alias
            );

            $query = $callback($datagridContext);
            if (!$query instanceof NativeQuery) {
                throw new \RuntimeException('Callback must be return instance of Doctrine\ORM\NativeQuery.');
            }

            // Add conditions
            $rsm = $datagridContext->getResultSetMapping();
            $this->addNativeConditions($parameters, $rsm, $query);

            $datagrid = new NativeSqlDatagrid($query, $datagridContext);

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
        $queryBuilder = $repository->createQueryBuilder($alias);

        // Apply joins
        $joinMap = $this->getJoinMap();

        if ($this->dataSchema) {
            $dataSchemaConfig = $this->dataSchema->getConfiguration();
            if (isset($dataSchemaConfig['conditions'])) {
                foreach ($dataSchemaConfig['conditions'] as $condition) {
                    $preparedCondition = $this->dataSchema->conditionPlaceholder($condition, $alias);
                    $queryBuilder->andWhere($preparedCondition);
                }
            }

            $joinMapFromDataSchema = $this->dataSchema->createJoinMap($this->alias);
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
        return $this->filterStack->getByParam($name);
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
}