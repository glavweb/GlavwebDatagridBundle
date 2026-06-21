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

use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class NumberFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class NumberFilter extends AbstractFilter
{
    protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value): void
    {
        $field = $alias.'.'.$this->getColumnName($fieldName);

        if (\is_array($value) && $this->existsOperatorsInValues($value)) {
            foreach ($value as $item) {
                [$operator, $value] = $this->getOperatorAndValue($item);

                $this->executeCondition($queryBuilder, $operator, $field, $value);
            }
        } else {
            [$operator, $value] = $this->getOperatorAndValue($value);

            $this->executeCondition($queryBuilder, $operator, $field, $value);
        }
    }

    protected function getAllowOperators(): array
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
            self::NOT_CONTAINS,
        ];
    }

    /**
     * Default operator. Use if operator can't be defined.
     */
    protected function getDefaultOperator(): string
    {
        return self::CONTAINS;
    }
}
