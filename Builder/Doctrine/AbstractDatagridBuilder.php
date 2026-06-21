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
use Doctrine\ORM\Mapping\MappingException;
use Glavweb\DatagridBundle\Builder\DatagridBuilderInterface;
use Glavweb\DatagridBundle\Datagrid\DatagridInterface;
use Glavweb\DatagridBundle\Exception\BuildException;
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilterFactory;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\Filter\FilterStack;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DataSchemaBundle\DataSchema\DataSchemaFactory;
use Glavweb\DataSchemaBundle\Exception\DataSchema\InvalidConfigurationException;

/**
 * Class AbstractDatagridBuilder.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractDatagridBuilder implements DatagridBuilderInterface
{
    protected FilterStack $filterStack;

    /**
     * @var mixed[]
     */
    protected array $orderings = [];

    protected ?int $firstResult = null;

    protected ?int $maxResults = null;

    /**
     * @var mixed[]
     */
    protected array $operators = [];

    protected string $alias = 't';

    protected ?string $entityClassName = null;

    protected ?JoinMap $joinMap = null;

    protected ?DataSchema $dataSchema = null;

    /**
     * @throws BuildException
     */
    abstract public function build(array $parameters = [], ?\Closure $callback = null): DatagridInterface;

    /**
     * DoctrineDatagridBuilder constructor.
     */
    public function __construct(
        protected Registry $doctrine,
        protected AbstractFilterFactory $filterFactory,
        protected DataSchemaFactory $dataSchemaFactory,
        protected AbstractQueryBuilderFactory $queryBuilderFactory,
    ) {
        $this->filterStack = new FilterStack();
    }

    public function getFilterStack(): FilterStack
    {
        return $this->filterStack;
    }

    /**
     * @return $this
     */
    public function setOrderings(array $orderings): static
    {
        $this->orderings = $orderings;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getOrderings(): array
    {
        return $this->orderings;
    }

    /**
     * @return $this
     */
    public function setFirstResult(int $firstResult): static
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    /**
     * @return $this
     */
    public function setMaxResults(int $maxResults): static
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    /**
     * @return $this
     */
    public function setOperators(array $operators): static
    {
        $this->operators = $operators;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * @return $this
     */
    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @throws BuildException
     */
    public function getEntityClassName(): ?string
    {
        if (!$this->entityClassName) {
            if (!$this->dataSchema instanceof DataSchema) {
                throw new BuildException('The Data Schema is not defined.');
            }

            $configuration = $this->dataSchema->getConfiguration();
            $this->entityClassName = $configuration['class'] ?? null;
        }

        return $this->entityClassName;
    }

    /**
     * @return $this
     */
    public function setJoinMap(JoinMap $joinMap): static
    {
        if ($this->joinMap instanceof JoinMap) {
            $this->joinMap->merge($joinMap);
        } else {
            $this->joinMap = $joinMap;
        }

        return $this;
    }

    public function getJoinMap(): ?JoinMap
    {
        return $this->joinMap;
    }

    /**
     * @param FilterInterface[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters = []): static
    {
        foreach ($filters as $filter) {
            $this->fixFilter($filter);

            $this->filterStack->add($filter);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function addFilter(string $filterName, ?string $type = null, array $options = []): static
    {
        $entityClass = $this->getEntityClassName();
        $alias = $this->getAlias();

        $filter = $this->filterFactory->createForEntity($entityClass, $alias, $filterName, $type, $options);
        $this->fixFilter($filter);

        $this->filterStack->add($filter);

        return $this;
    }

    public function getFilter(string $filterName): FilterInterface
    {
        return $this->filterStack->get($filterName);
    }

    /**
     * @return FilterInterface[]
     */
    public function getFilters(): array
    {
        return $this->filterStack->all();
    }

    /**
     * @return $this
     *
     * @throws MappingException
     * @throws InvalidConfigurationException
     */
    public function setDataSchema(string $dataSchemaFile, ?string $scopeFile = null, ?string $propertyPath = null): static
    {
        $dataSchema = $this->dataSchemaFactory->createDataSchema($dataSchemaFile, $scopeFile);

        if ($propertyPath) {
            $config = $dataSchema->getPropertyConfiguration($propertyPath);
            if (!$config) {
                throw new \InvalidArgumentException("Config for property \"{$propertyPath}\" does not exist.");
            }

            $dataSchema->setConfiguration($config);
            $dataSchema->setScopeConfig($dataSchema->getPropertyScopeConfiguration($propertyPath));
        }

        $this->dataSchema = $dataSchema;

        return $this;
    }

    public function enablePropertyCondition(string $propertyPath, string $conditionName): self
    {
        $this->dataSchema->enablePropertyCondition($propertyPath, $conditionName);

        return $this;
    }

    public function disablePropertyCondition(string $propertyPath, string $conditionName): self
    {
        $this->dataSchema->disablePropertyCondition($propertyPath, $conditionName);

        return $this;
    }

    /**
     * @return $this
     */
    public function setPropertyOrderBy(string $propertyPath, string $orderByPropertyName, string $order): self
    {
        $this->dataSchema->setPropertyOrderBy($propertyPath, $orderByPropertyName, $order);

        return $this;
    }

    private function fixFilter(FilterInterface $filter): void
    {
        $paramName = $filter->getParamName();
        if (isset($this->operators[$paramName])) {
            $options = $filter->getOptions();
            $options['operator'] = $this->operators[$paramName];

            $filter->setOptions($options);
        }
    }
}
