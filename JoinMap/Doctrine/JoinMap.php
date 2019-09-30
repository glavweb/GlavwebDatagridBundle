<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\JoinMap\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class JoinMap
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class JoinMap
{
    /**
     * Constants of join types
     */
    const JOIN_TYPE_LEFT = 'left';
    const JOIN_TYPE_INNER = 'inner';

    /**
     * @var string
     */
    private $alias;

    /**
     * @var ClassMetadata
     */
    private $classMetadata;

    /**
     * @var array
     */
    private $joinMap = [];

    /**
     * JoinMap constructor.
     *
     * @param string $alias
     * @param ClassMetadata $classMetadata
     */
    public function __construct($alias, ClassMetadata $classMetadata)
    {
        $this->alias = $alias;
        $this->classMetadata = $classMetadata;

        $this->joinMap[$alias] = [];
    }

    /**
     * @param string $path
     * @param string $field
     * @return string
     */
    public static function makeAlias($path, $field)
    {
        return str_replace('.', '_', $path) . '_' . $field;
    }

    /**
     * @param string $path
     * @param string $field
     * @param bool $hasSelect
     * @param array $selectFields
     * @param string $joinType
     * @param string $conditionType
     * @param string $condition
     * @return $this
     */
    public function join($path, $field, $hasSelect = true, array $selectFields = [], $joinType = 'left', $conditionType = Join::WITH, $condition = null)
    {
        $this->joinMap[$path][] = [
            'field'         => $field,
            'hasSelect'     => $hasSelect,
            'selectFields'  => $selectFields,
            'joinType'      => $joinType,
            'conditionType' => $conditionType,
            'condition'     => $condition
        ];

        return $this;
    }

    /**
     * @param JoinMap $joinMap
     */
    public function merge(JoinMap $joinMap)
    {
        $this->joinMap = array_merge_recursive($this->joinMap, $joinMap);
    }

    /**
     * @return array
     */
    public function getJoinMap()
    {
        return $this->joinMap;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }
}