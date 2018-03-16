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
 * Interface FilterInterface
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
interface FilterInterface
{
    /**
     * @return mixed
     */
    public function getName();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param array $options
     */
    public function setOptions($options);

    /**
     * @return string
     */
    public function getParamName();

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * @param mixed $queryBuilder
     * @param string $alias
     * @param string $value
     */
    public function filter($queryBuilder, $alias, $value);
}