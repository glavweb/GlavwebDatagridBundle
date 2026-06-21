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
 * Class DateTimeFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DateTimeFilter extends AbstractFilter
{
    protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value): void
    {
        $executeCondition = function (string $field, $inValue) use ($queryBuilder): void {
            [$operator, $value] = $this->getOperatorAndValue($inValue, $this->replaceOperators());

            $this->executeCondition($queryBuilder, $operator, $field, $value);
        };

        $field = $alias.'.'.$fieldName;

        if (\is_array($value) && $this->existsOperatorsInValues($value)) {
            foreach ($value as $item) {
                $executeCondition($field, $item);
            }
        } else {
            $executeCondition($field, $value);
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
     * @return array<string, string>
     */
    protected function replaceOperators(): array
    {
        return [
            self::CONTAINS => self::EQ,
            self::NOT_CONTAINS => self::NEQ,
        ];
    }

    /**
     * Default operator. Use if operator can't be defined.
     */
    protected function getDefaultOperator(): string
    {
        return self::EQ;
    }
}
