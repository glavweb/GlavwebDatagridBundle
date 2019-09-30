<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\JoinMap\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class JoinBuilder
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class JoinBuilder implements JoinBuilderInterface
{
    /**
     * Apply joins and get last alias.
     *
     * @param QueryBuilder $queryBuilder
     * @param JoinMap $joinMap
     * @return null|string
     */
    public function apply($queryBuilder, JoinMap $joinMap): ?string
    {
        $alias = null;

        $executedAliases = $queryBuilder->getAllAliases();
        foreach ($joinMap->getJoinMap() as $path => $fields) {
            foreach ($fields as $fieldData) {
                $field         = $fieldData['field'];
                $hasSelect     = $fieldData['hasSelect'];
                $selectFields  = $fieldData['selectFields'];
                $joinType      = $fieldData['joinType'];
                $conditionType = $fieldData['conditionType'];
                $condition     = $fieldData['condition'];

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

                if ($joinType == JoinMap::JOIN_TYPE_LEFT) {
                    $queryBuilder->leftJoin($join, $alias, $conditionType, $condition);

                } elseif ($joinType == JoinMap::JOIN_TYPE_INNER) {
                    $queryBuilder->innerJoin($join, $alias, $conditionType, $condition);

                } else {
                    throw new \RuntimeException('Join type not defined or has wrong type.');
                }
            }
        }

        return $alias;
    }
}