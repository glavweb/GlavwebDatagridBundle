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
use Glavweb\DatagridBundle\Builder\DatagridBuilderInterface;
use Glavweb\DatagridBundle\Exception\BuildException;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DataSchemaBundle\DataSchema\DataSchemaFactory;
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilterFactory;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class AbstractDatagridBuilder
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class AbstractDatagridBuilder implements DatagridBuilderInterface
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var AbstractFilterFactory
     */
    protected $filterFactory;

    /**
     * @var DataSchemaFactory
     */
    protected $dataSchemaFactory;

    /**
     * @var AbstractQueryBuilderFactory
     */
    protected $queryBuilderFactory;

    /**
     * @var FilterStack
     */
    protected $filterStack;

    /**
     * @var array
     */
    protected $orderings;

    /**
     * @var int
     */
    protected $firstResult;

    /**
     * @var int
     */
    protected $maxResults;

    /**
     * @var array
     */
    protected $operators = [];

    /**
     * @var string
     */
    protected $alias = 't';

    /**
     * @var string
     */
    protected $entityClassName;

    /**
     * @var JoinMap
     */
    protected $joinMap;

    /**
     * @var DataSchema
     */
    protected $dataSchema;

    /**
     * DoctrineDatagridBuilder constructor.
     *
     * @param Registry $doctrine
     * @param AbstractFilterFactory $filterFactory
     * @param DataSchemaFactory $dataSchemaFactory
     * @param AbstractQueryBuilderFactory $queryBuilderFactory
     */
    public function __construct(Registry $doctrine, AbstractFilterFactory $filterFactory, DataSchemaFactory $dataSchemaFactory, AbstractQueryBuilderFactory $queryBuilderFactory)
    {
        $this->doctrine            = $doctrine;
        $this->filterFactory       = $filterFactory;
        $this->dataSchemaFactory   = $dataSchemaFactory;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->filterStack         = new FilterStack();
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
     * @return null|string
     * @throws BuildException
     */
    public function getEntityClassName(): ?string
    {
        if (!$this->entityClassName) {
            if (!$this->dataSchema instanceof DataSchema) {
                throw new BuildException('The Data Schema is not defined.');
            }

            $configuration = $this->dataSchema->getConfiguration();
            $this->entityClassName = isset($configuration['class']) ? $configuration['class'] : null;
        }

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
     * @param FilterInterface[] $filters
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
     * @return FilterInterface
     */
    public function getFilter($filterName)
    {
        return $this->filterStack->get($filterName);
    }

    /**
     * @return FilterInterface[]
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
     * @param FilterInterface $filter
     */
    private function fixFilter(FilterInterface $filter)
    {
        $paramName = $filter->getParamName();
        if (isset($this->operators[$paramName])) {
            $options = $filter->getOptions();
            $options['operator'] = $this->operators[$paramName];

            $filter->setOptions($options);
        }
    }
}
