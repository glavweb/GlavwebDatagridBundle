<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine\Native;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;
use Glavweb\DatagridBundle\Filter\FilterStack;

/**
 * Class DatagridContext
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridContext
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var EntityManager
     */
    private $entityManger;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var FilterStack
     */
    private $filterStack;

    /**
     * @var DataSchema
     */
    private $dataSchema;

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
     * @var string
     */
    private $alias;

    /**
     * DatagridContext constructor.
     *
     * @param string        $class
     * @param EntityManager $entityManger
     * @param QueryBuilder  $queryBuilder
     * @param FilterStack   $filterStack
     * @param DataSchema    $dataSchema
     * @param array         $orderings
     * @param int           $firstResult
     * @param int           $maxResults
     * @param string        $alias
     */
    public function __construct($class, EntityManager $entityManger, QueryBuilder $queryBuilder, FilterStack $filterStack, DataSchema $dataSchema, array $orderings = null, $firstResult = 0, $maxResults = null, $alias = 't')
    {
        $this->class        = $class;
        $this->entityManger = $entityManger;
        $this->queryBuilder = $queryBuilder;
        $this->dataSchema   = $dataSchema;
        $this->filterStack  = $filterStack;
        $this->orderings    = (array)$orderings;
        $this->firstResult  = (int)$firstResult;
        $this->maxResults   = $maxResults;
        $this->alias        = $alias;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManger()
    {
        return $this->entityManger;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return FilterStack
     */
    public function getFilterStack()
    {
        return $this->filterStack;
    }

    /**
     * @return DataSchema
     */
    public function getDataSchema()
    {
        return $this->dataSchema;
    }

    /**
     * @return array
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}