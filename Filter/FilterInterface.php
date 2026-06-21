<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter;

/**
 * Interface FilterInterface.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface FilterInterface
{
    public function getName(): mixed;

    public function getOptions(): array;

    public function setOptions(array $options);

    public function getParamName(): string;

    public function getOption(string $name): mixed;

    public function filter(mixed $queryBuilder, string $alias, string $value);
}
