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
 * Class NumberFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class NumberFilter extends AbstractFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param $fieldName
     * @param mixed $value
     */
    protected function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value)
    {
        $field = $alias . '.' . $fieldName;

        if (is_array($value) && $this->existsOperatorsInValues($value)) {
            foreach ($value as $item) {
                list($operator, $value) = $this->getOperatorAndValue($item);

                $this->executeCondition($queryBuilder, $operator, $field, $value);
            }

        } else {
            list($operator, $value) = $this->getOperatorAndValue($value);

            $this->executeCondition($queryBuilder, $operator, $field, $value);
        }
    }

    /**
     * @return array
     */
    protected function getAllowOperators()
    {
        return [
            self::EQ,
            self::NEQ,
            self::LT,
            self::LTE,
            self::GT,
            self::GTE,
            self::IN,
            self::NIN,
            self::CONTAINS,
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
        return self::CONTAINS;
    }
}