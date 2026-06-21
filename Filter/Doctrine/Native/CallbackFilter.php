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
 * Class CallbackFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class CallbackFilter extends AbstractFilter
{
    protected function doFilter(QueryBuilder $queryBuilder, string $alias, string $fieldName, mixed $value): void
    {
        $callback = $this->getOption('callback');
        if (!\is_callable($callback)) {
            throw new \RuntimeException(\sprintf('Please provide a valid callback option "filter" for field "%s"', $this->getName()));
        }

        \call_user_func($callback, $queryBuilder, $alias, $this->getColumnName($fieldName), $value, $fieldName);
    }

    protected function getAllowOperators(): array
    {
        return [];
    }

    /**
     * Default operator. Use if operator can't be defined.
     */
    protected function getDefaultOperator(): ?string
    {
        return null;
    }
}
