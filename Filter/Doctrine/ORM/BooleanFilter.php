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
 * Class BooleanFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class BooleanFilter extends AbstractFilter
{
    protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value): void
    {
        [$operator, $value] = $this->getOperatorAndValue($value, [
            self::NOT_CONTAINS => self::NEQ,
        ]);

        $field = $alias.'.'.$fieldName;
        $this->executeCondition($queryBuilder, $operator, $field, $value);
    }

    protected function getAllowOperators(): array
    {
        return [
            self::EQ,
            self::NEQ,
            self::NOT_CONTAINS,
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
