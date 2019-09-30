<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine\Native;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class DateTimeFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DateTimeFilter extends AbstractFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param $fieldName
     * @param mixed $value
     */
    protected function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value)
    {
        $executeCondition = function ($field, $inValue) use ($queryBuilder) {
            [$operator, $value] = $this->getOperatorAndValue($inValue, $this->replaceOperators());

            $this->executeCondition($queryBuilder, $operator, $field, $value);
        };

        $field = $alias . '.' . $this->getColumnName($fieldName);

        if (is_array($value) && $this->existsOperatorsInValues($value)) {
            foreach ($value as $item) {
                $executeCondition($field, $item);
            }

        } else {
            $executeCondition($field, $value);
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
     * @return array
     */
    protected function replaceOperators(): array
    {
        return [
            self::CONTAINS => self::EQ,
            self::NOT_CONTAINS => self::NEQ
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