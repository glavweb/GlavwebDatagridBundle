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
 * Class TransformEvent
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class TransformEvent
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var array
     */
    private $propertyConfig;

    /**
     * @var string
     */
    private $parentClassName;

    /**
     * @var string
     */
    private $parentPropertyName;

    /**
     * @var array
     */
    private $data;

    /**
     * TransformEvent constructor.
     *
     * @param string $className
     * @param string $propertyName
     * @param array  $propertyConfig
     * @param string $parentClassName
     * @param string $parentPropertyName
     * @param array  $data
     */
    public function __construct($className, $propertyName, array $propertyConfig, $parentClassName, $parentPropertyName, array $data)
    {
        $this->className          = $className;
        $this->propertyName       = $propertyName;
        $this->propertyConfig     = $propertyConfig;
        $this->parentClassName    = $parentClassName;
        $this->parentPropertyName = $parentPropertyName;
        $this->data               = $data;
    }

    /**
     * @return mixed
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return mixed
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return array
     */
    public function getPropertyConfig()
    {
        return $this->propertyConfig;
    }

    /**
     * @return string
     */
    public function getParentClassName()
    {
        return $this->parentClassName;
    }

    /**
     * @return string
     */
    public function getParentPropertyName()
    {
        return $this->parentPropertyName;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}