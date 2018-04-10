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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class ModelFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ModelFilter extends AbstractFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $fieldName
     * @param mixed $value
     */
    protected function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value)
    {
        [$operator, $value] = $this->getOperatorAndValue($value, [
            self::NOT_CONTAINS => self::NEQ,
        ]);

        $this->executeCondition($queryBuilder, $operator, $alias . '.' . $fieldName, $value);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $operator
     * @param string $field
     * @param mixed $value
     */
    protected function executeCondition(QueryBuilder $queryBuilder, $operator, $field, $value)
    {
        $parameterName = self::makeParamName($field);
        $expr = $queryBuilder->expr();

        if ($operator == self::IN) {
            $queryBuilder->andWhere($expr->in($field, ':' . $parameterName));

        } elseif ($operator == self::NIN) {
            $queryBuilder->andWhere($expr->notIn($field, ':' . $parameterName));

        } elseif ($operator == self::EQ) {
            $queryBuilder->andWhere($expr->eq($field, ':' . $parameterName));

        } elseif ($operator == self::NEQ) {
            $queryBuilder->andWhere($expr->neq($field, ':' . $parameterName));
        }

        $queryBuilder->setParameter($parameterName, $value);
    }

    /**
     * @param string $fieldName
     * @return mixed
     */
    protected function getAssociationType($fieldName)
    {
        $associationMapping = $this->classMetadata->getAssociationMapping($fieldName);
        $type = $associationMapping['type'];

        return $type;
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