<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Extension;

use Glavweb\DatagridBundle\DataTransformer\DataTransformerInterface;

/**
 * Class ExtensionInterface
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
interface ExtensionInterface
{
    /**
     * @return DataTransformerInterface[]
     */
    public function getDataTransformers();
}