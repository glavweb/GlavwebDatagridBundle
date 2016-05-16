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
 * Interface DataTransformerInterface
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
interface DataTransformerInterface
{
    /**
     * @param mixed $value
     * @param array $data
     * @return mixed
     */
    public function transform($value, array $data);
}