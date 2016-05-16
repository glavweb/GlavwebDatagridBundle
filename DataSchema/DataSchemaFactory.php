<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DataSchema;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Glavweb\DatagridBundle\DataTransformer\DataTransformerRegistry;
use Glavweb\DatagridBundle\Loader\Yaml\DataSchemaYamlLoader;
use Glavweb\DatagridBundle\Loader\Yaml\ScopeYamlLoader;
use Glavweb\DatagridBundle\Persister\EntityPersister;
use Symfony\Component\Config\FileLocator;

/**
 * Class DataSchemaFactory
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataSchemaFactory
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var DataTransformerRegistry
     */
    private $dataTransformerRegistry;

    /**
     * @var EntityPersister
     */
    private $entityPersister;

    /**
     * @var string
     */
    private $dataSchemaDir;

    /**
     * @var string
     */
    private $scopeDir;

    /**
     * DataSchema constructor.
     *
     * @param Registry $doctrine
     * @param DataTransformerRegistry $dataTransformerRegistry
     * @param EntityPersister $entityPersister
     * @param string $dataSchemaDir
     * @param string $scopeDir
     */
    public function __construct(Registry $doctrine, DataTransformerRegistry $dataTransformerRegistry, EntityPersister $entityPersister, $dataSchemaDir, $scopeDir)
    {
        $this->doctrine                = $doctrine;
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->entityPersister         = $entityPersister;
        $this->dataSchemaDir           = $dataSchemaDir;
        $this->scopeDir                = $scopeDir;
    }

    /**
     * @param string $dataSchemaFile
     * @param string $scopeFile
     * @return DataSchema
     */
    public function createDataSchema($dataSchemaFile, $scopeFile = null)
    {
        $dataSchemaConfig = $this->getDataSchemaConfig($dataSchemaFile);

        $scopeConfig = null;
        if ($scopeFile) {
            $scopeConfig = $this->getScopeConfig($scopeFile);
        }

        return new DataSchema(
            $this->doctrine,
            $this->dataTransformerRegistry,
            $this->entityPersister,
            $dataSchemaConfig,
            $scopeConfig
        );
    }

    /**
     * @param string $dataSchemaFile
     * @return array
     */
    public function getDataSchemaConfig($dataSchemaFile)
    {
        $dataSchemaLoader = new DataSchemaYamlLoader(new FileLocator($this->dataSchemaDir));
        $dataSchemaLoader->load($dataSchemaFile);

        return $dataSchemaLoader->getConfiguration();
    }

    /**
     * @param string $scopeFile
     * @return array
     */
    private function getScopeConfig($scopeFile)
    {
        $scopeLoader = new ScopeYamlLoader(new FileLocator($this->scopeDir));
        $scopeLoader->load($scopeFile);

        return $scopeLoader->getConfiguration();
    }
}