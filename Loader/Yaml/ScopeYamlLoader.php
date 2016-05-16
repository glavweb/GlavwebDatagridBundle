<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Loader\Yaml;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ScopeYamlLoader
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class ScopeYamlLoader extends FileLoader
{
    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @param mixed $resource
     * @param null $type
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);
        $content = $this->loadFile($path);

        // empty file
        if (!$content) {
            return;
        }

        // imports
        $this->parseImports($content, $path);

        $this->loadConfiguration($content);
    }

    /**
     * @param mixed $resource
     * @param null $type
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && pathinfo($resource, PATHINFO_EXTENSION) == 'yml';
    }

    /**
     * @param string $file
     * @return mixed
     */
    private function loadFile($file)
    {
        return Yaml::parse($file);
    }

    /**
     * @param $content
     * @param $file
     * @throws \Exception
     * @throws \Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException
     */
    private function parseImports($content, $file)
    {
        if (!isset($content['imports'])) {
            return;
        }

        if (!is_array($content['imports'])) {
            throw new \InvalidArgumentException(sprintf('The "imports" key should contain an array in %s. Check your YAML syntax.', $file));
        }

        $defaultDirectory = dirname($file);
        foreach ($content['imports'] as $import) {
            if (!is_array($import)) {
                throw new \InvalidArgumentException(sprintf('The values in the "imports" key should be arrays in %s. Check your YAML syntax.', $file));
            }

            $this->setCurrentDir($defaultDirectory);
            $this->import($import['resource'], null, isset($import['ignore_errors']) ? (bool) $import['ignore_errors'] : false, $file);
        }
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $content
     */
    private function loadConfiguration(array $content)
    {
        foreach ($content as $namespace => $values) {
            if (in_array($namespace, array('imports'))) {
                continue;
            }

            if (!is_array($values)) {
                $values = array();
            }

            $this->configuration = array_merge_recursive($this->configuration, $values);
        }
    }
}