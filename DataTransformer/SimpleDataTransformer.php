<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DataTransformer;

/**
 * Class SimpleDataTransformer
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class SimpleDataTransformer implements DataTransformerInterface
{
    /**
     * @var mixed
     */
    private $callable;

    /**
     * SimpleDataTransformer constructor.
     *
     * @param mixed $callable
     */
    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \RuntimeException('$callable argument must be callable.');
        }

        $this->callable = $callable;
    }

    /**
     * @param mixed $value
     * @param array $data
     * @return mixed
     */
    public function transform($value, array $data)
    {
        return call_user_func($this->callable, $value, $data);
    }
}