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

use Glavweb\DatagridBundle\Extension\ExtensionInterface;

/**
 * Class DataTransformerRegistry
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataTransformerRegistry
{
    /**
     * @var DataTransformerInterface[]
     */
    private $registry = [];

    /**
     * @param DataTransformerInterface $dataTransformer
     * @param string $name
     */
    public function add(DataTransformerInterface $dataTransformer, $name)
    {
        $this->registry[$name] = $dataTransformer;
    }

    /**
     * @param string $name
     * @return DataTransformerInterface
     */
    public function get($name)
    {
        return $this->registry[$name];
    }

    /**
     * @param string $name
     * @return DataTransformerInterface
     */
    public function has($name)
    {
        return isset($this->registry[$name]);
    }

    /**
     * @param ExtensionInterface $extension
     */
    public function loadExtension(ExtensionInterface $extension)
    {
        $dataTransformers = $extension->getDataTransformers();
        foreach ($dataTransformers as $name => $transformer) {
            $this->add($transformer, $name);
        }
    }
}