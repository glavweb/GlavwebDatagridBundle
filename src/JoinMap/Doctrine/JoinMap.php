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

use Doctrine\ORM\QueryBuilder;

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
     * @var array
     */
    private $joinMap = [];

    /**
     * JoinMap constructor.
     *
     * @param string $alias
     */
    public function __construct($alias)
    {
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
     * @return $this
     */
    public function join($path, $field, $hasSelect = true, array $selectFields = [], $joinType = 'left')
    {
        $this->joinMap[$path][] = [
            'field'        => $field,
            'hasSelect'    => $hasSelect,
            'selectFields' => $selectFields,
            'joinType'     => $joinType
        ];

        return $this;
    }

    /**
     * Apply joins and get last alias.
     *
     * @param QueryBuilder $queryBuilder
     * @return string|null
     */
    public function apply(QueryBuilder $queryBuilder)
    {
        $alias = null;

        $executedAliases = $queryBuilder->getAllAliases();
        foreach ($this->joinMap as $path => $fields) {
            foreach ($fields as $fieldData) {
                $field        = $fieldData['field'];
                $hasSelect    = $fieldData['hasSelect'];
                $selectFields = $fieldData['selectFields'];
                $joinType     = $fieldData['joinType'];

                $pathAlias = str_replace('.', '_', $path);
                $alias     = $pathAlias . '_' . $field;
                $join      = $pathAlias . '.' . $field;

                if (in_array($alias, $executedAliases)) {
                    continue;
                }

                if ($hasSelect) {
                    if (!$selectFields) {
                        $queryBuilder->addSelect($alias);

                    } else {
                        $queryBuilder->addSelect(sprintf('PARTIAL %s.{%s}', $alias, implode(',', $selectFields)));
                    }
                }

                if ($joinType == self::JOIN_TYPE_LEFT) {
                    $queryBuilder->leftJoin($join, $alias);

                } elseif ($joinType == self::JOIN_TYPE_INNER) {
                    $queryBuilder->innerJoin($join, $alias);

                } else {
                    throw new \RuntimeException('Join type not defined or has wrong type.');
                }
            }
        }

        return $alias;
    }

    /**
     * @param JoinMap $joinMap
     */
    public function merge(JoinMap $joinMap)
    {
        $this->joinMap = array_merge_recursive($this->joinMap, $joinMap);
    }
}