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
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilter as BaseFilter;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class Filter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractFilter extends BaseFilter implements FilterInterface
{
    abstract protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value);

    public function filter(mixed $queryBuilder, string $alias, string $value): void
    {
        if ($this->joinMap instanceof JoinMap) {
            $alias = $this->joinBuilder->apply($queryBuilder, $this->joinMap);
        }

        $this->doFilter($queryBuilder, $alias, $this->fieldName, $value);
    }

    protected function executeCondition(QueryBuilder $queryBuilder, $operator, string $field, $value): void
    {
        $parameterName = self::makeParamName($field);
        $expr = $queryBuilder->expr();

        if ($operator == self::CONTAINS) {
            $value = mb_strtolower((string) $value, 'UTF-8');

            if ($value === '') {
                return;
            }

            if (is_numeric($value)) {
                $queryBuilder->andWhere($expr->like('CAST('.$field.' AS TEXT)', ':'.$parameterName));
            } else {
                $queryBuilder->andWhere($expr->like('LOWER('.$field.')', ':'.$parameterName));
            }

            $queryBuilder->setParameter($parameterName, "%{$value}%");
        } elseif ($operator == self::NOT_CONTAINS) {
            $value = mb_strtolower((string) $value, 'UTF-8');

            if (is_numeric($value)) {
                $queryBuilder->andWhere($expr->notLike('CAST('.$field.' AS TEXT)', ':'.$parameterName));
            } else {
                $queryBuilder->andWhere($expr->notLike('LOWER('.$field.')', ':'.$parameterName));
            }

            $queryBuilder->setParameter($parameterName, "%{$value}%");
        } elseif ($operator == self::IN) {
            $queryBuilder->andWhere($expr->in($field, ':'.$parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        } elseif ($operator == self::NIN) {
            $queryBuilder->andWhere($expr->notIn($field, ':'.$parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        } else {
            $queryBuilder->andWhere($expr->comparison($field, $operator, ':'.$parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        }
    }
}
