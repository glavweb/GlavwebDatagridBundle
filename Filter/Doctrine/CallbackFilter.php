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

use Doctrine\ORM\QueryBuilder;

/**
 * Class CallbackFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class CallbackFilter extends Filter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param $fieldName
     * @param mixed $value
     */
    protected function doFilter(QueryBuilder $queryBuilder, $alias, $fieldName, $value)
    {
        $callback = $this->getOption('callback');
        if (!is_callable($callback)) {
            throw new \RuntimeException(sprintf('Please provide a valid callback option "filter" for field "%s"', $this->getName()));
        }

        call_user_func($callback, $queryBuilder, $alias, $fieldName, $value);
    }

    /**
     * @return array
     */
    protected function getAllowOperators()
    {
        return [];
    }

    /**
     * Default operator. Use if operator can't defined.
     *
     * @return string
     */
    protected function getDefaultOperator()
    {
        return null;
    }
}