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

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilter as BaseFilter;

/**
 * Class AbstractFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractFilter extends BaseFilter implements FilterInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $fieldName
     * @param mixed $value
     * @return
     */
    protected abstract function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value);

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param mixed $value
     */
    public function filter($queryBuilder, $alias, $value)
    {
        if ($this->joinMap) {
            $alias = $this->joinBuilder->apply($queryBuilder, $this->joinMap);
        }

        $this->doFilter($queryBuilder, $alias, $this->fieldName, $value);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $operator
     * @param $field
     * @param $value
     */
    protected function executeCondition(QueryBuilder $queryBuilder, $operator, $field, $value)
    {
        $parameterName = self::makeParamName($field);
        $expr = $queryBuilder->expr();

        if ($operator == self::CONTAINS) {
            $value = mb_strtolower($value, 'UTF-8');

            if ($value === '') {
                return;
            }

            if (is_numeric($value)) {
                $queryBuilder->andWhere($expr->like('CAST(' . $field . ' AS TEXT)', ':' . $parameterName));

            } else {
                $queryBuilder->andWhere($expr->like('LOWER(' . $field . ')', ':' . $parameterName));
            }

            $queryBuilder->setParameter($parameterName, "%$value%");

        } elseif ($operator == self::NOT_CONTAINS) {
            $value = mb_strtolower($value, 'UTF-8');

            if (is_numeric($value)) {
                $queryBuilder->andWhere($expr->notLike('CAST(' . $field . ' AS TEXT)', ':' . $parameterName));

            } else {
                $queryBuilder->andWhere($expr->notLike('LOWER(' . $field . ')', ':' . $parameterName));
            }

            $queryBuilder->setParameter($parameterName, "%$value%");

        } elseif ($operator == self::IN) {
            $queryBuilder->andWhere($expr->in($field, ':' . $parameterName));
            $queryBuilder->setParameter($parameterName, $value);

        } elseif ($operator == self::NIN) {
            $queryBuilder->andWhere($expr->notIn($field, ':' . $parameterName));
            $queryBuilder->setParameter($parameterName, $value);

        } else {
            $queryBuilder->andWhere(new Comparison($field, $operator, ':' . $parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        }
    }
}