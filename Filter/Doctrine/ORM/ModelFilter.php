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
 * Class ModelFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ModelFilter extends AbstractFilter
{
    protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value): void
    {
        [$operator, $value] = $this->getOperatorAndValue($value, [
            self::NOT_CONTAINS => self::NEQ,
        ]);

        $this->executeCondition($queryBuilder, $operator, $alias.'.'.$fieldName, $value);
    }

    /**
     * @param string $operator
     * @param string $field
     */
    #[\Override]
    protected function executeCondition(QueryBuilder $queryBuilder, $operator, $field, $value): void
    {
        $parameterName = self::makeParamName($field);
        $expr = $queryBuilder->expr();

        if ($operator == self::IN) {
            $queryBuilder->andWhere($expr->in($field, ':'.$parameterName));
        } elseif ($operator == self::NIN) {
            $queryBuilder->andWhere($expr->notIn($field, ':'.$parameterName));
        } elseif ($operator == self::EQ) {
            $queryBuilder->andWhere($expr->eq($field, ':'.$parameterName));
        } elseif ($operator == self::NEQ) {
            $queryBuilder->andWhere($expr->neq($field, ':'.$parameterName));
        }

        $queryBuilder->setParameter($parameterName, $value);
    }

    protected function getAssociationType(string $fieldName): mixed
    {
        $associationMapping = $this->classMetadata->getAssociationMapping($fieldName);

        return $associationMapping['type'];
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
