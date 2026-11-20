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
 * Class EnumFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class EnumFilter extends AbstractFilter
{
    protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value): void
    {
        $field = $alias.'.'.$fieldName;
        if (\is_array($value) && $this->runNamedOperators($value, function (string $operator, mixed $item) use ($queryBuilder, $field): void {
            $this->executeCondition($queryBuilder, $operator, $field, $item);
        }, [self::NOT_CONTAINS => self::NEQ])) {
            return;
        }

        [$operator, $value] = $this->getOperatorAndValue($value, [
            self::NOT_CONTAINS => self::NEQ,
        ]);
        $this->executeCondition($queryBuilder, $operator, $field, $value);
    }

    protected function getAllowOperators(): array
    {
        return [
            self::EQ,
            self::NEQ,
            self::IN,
            self::NIN,
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
