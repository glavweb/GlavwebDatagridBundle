<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class ModelFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ModelFilter extends Filter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $fieldName
     * @param mixed $value
     */
    protected function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value)
    {
        list($operator, $value) = $this->getOperatorAndValue($value, [
            self::NOT_CONTAINS => self::NEQ,
        ]);

        $type = $this->getAssociationType($fieldName);
        if (in_array($type, [ClassMetadataInfo::MANY_TO_MANY, ClassMetadataInfo::ONE_TO_MANY])) {
            $this->handleToMany($queryBuilder, $alias, $operator, $fieldName, $value);
        } else {
            $this->handleToOne($queryBuilder, $alias, $operator, $fieldName, $value);
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $operator
     * @param string $fieldName
     * @param mixed $value
     */
    private function handleToOne(QueryBuilder $queryBuilder, $alias, $operator, $fieldName, $value)
    {
        $field = $alias . '.' . $fieldName;
        $this->executeCondition($queryBuilder, $operator, $field, $value);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $operator
     * @param string $fieldName
     * @param mixed $value
     */
    private function handleToMany(QueryBuilder $queryBuilder, $alias, $operator, $fieldName, $value)
    {
        $field = $alias;
        $this->executeCondition($queryBuilder, $operator, $field, $value);
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
        $classMetadata      = $this->getOption('class_metadata');
        $associationMapping = $classMetadata->getAssociationMapping($fieldName);
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