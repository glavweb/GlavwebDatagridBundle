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
     * @return $this
     */
    public function join($path, $field, $hasSelect = true, array $selectFields = [])
    {
        $this->joinMap[$path][] = [
            'field'        => $field,
            'hasSelect'    => $hasSelect,
            'selectFields' => $selectFields
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

                $queryBuilder->leftJoin($join, $alias);
            }
        }

        return $alias;
    }
}