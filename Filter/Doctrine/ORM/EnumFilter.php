<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Class EnumFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class EnumFilter extends AbstractFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param $fieldName
     * @param mixed $value
     */
    protected function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value)
    {
        list($operator, $value) = $this->getOperatorAndValue($value, [
            self::NOT_CONTAINS => self::NEQ,
        ]);

        $field = $alias . '.' . $fieldName;
        $this->executeCondition($queryBuilder, $operator, $field, $value);
    }

    /**
     * @return array
     */
    protected function getAllowOperators()
    {
        return [
            self::EQ,
            self::NEQ,
            self::IN,
            self::NIN,
            self::NOT_CONTAINS
        ];
    }

    /**
     * Default operator. Use if operator can't defined.
     *
     * @return string
     */
    protected function getDefaultOperator()
    {
        return self::EQ;
    }
}