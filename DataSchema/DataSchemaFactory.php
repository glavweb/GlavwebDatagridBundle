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
use Glavweb\DatagridBundle\DataSchema\Persister\PersisterFactory;
use Glavweb\DatagridBundle\DataTransformer\DataTransformerRegistry;
use Glavweb\DatagridBundle\Loader\Yaml\DataSchemaYamlLoader;
use Glavweb\DatagridBundle\Loader\Yaml\ScopeYamlLoader;
use Glavweb\DatagridBundle\Persister\EntityPersister;
use Glavweb\SecurityBundle\Security\AccessHandler;
use Glavweb\DatagridBundle\DataSchema\Placeholder;
use Glavweb\SecurityBundle\Security\QueryBuilderFilter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class DataSchemaFactory
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
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
     * @var PersisterFactory
     */
    private $persisterFactory;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var QueryBuilderFilter
     */
    private $accessQbFilter;

    /**
     * @var string
     */
    private $dataSchemaDir;

    /**
     * @var string
     */
    private $scopeDir;
    /**
     * @var Placeholder
     */
    private $placeholder;

    /**
     * DataSchema constructor.
     *
     * @param Registry $doctrine
     * @param DataTransformerRegistry $dataTransformerRegistry
     * @param PersisterFactory $persisterFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AccessHandler $accessHandler
     * @param QueryBuilderFilter $accessQbFilter
     * @param Placeholder $placeholder
     * @param string $dataSchemaDir
     * @param string $scopeDir
     */
    public function __construct(Registry $doctrine, DataTransformerRegistry $dataTransformerRegistry, PersisterFactory $persisterFactory, AuthorizationCheckerInterface $authorizationChecker, AccessHandler $accessHandler, QueryBuilderFilter $accessQbFilter, Placeholder $placeholder, $dataSchemaDir, $scopeDir)
    {
        $this->doctrine                = $doctrine;
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->persisterFactory        = $persisterFactory;
        $this->authorizationChecker    = $authorizationChecker;
        $this->accessHandler           = $accessHandler;
        $this->accessQbFilter          = $accessQbFilter;
        $this->placeholder             = $placeholder;
        $this->dataSchemaDir           = $dataSchemaDir;
        $this->scopeDir                = $scopeDir;
    }

    /**
     * @param string $dataSchemaFile
     * @param string $scopeFile
     * @param bool   $securityEnabled
     * @return DataSchema
     */
    public function createDataSchema($dataSchemaFile, $scopeFile = null, $securityEnabled = true)
    {
        $dataSchemaConfig = $this->getDataSchemaConfig($dataSchemaFile);

        $scopeConfig = null;
        if ($scopeFile) {
            $scopeConfig = $this->getScopeConfig($scopeFile);
        }

        return new DataSchema(
            $this->doctrine,
            $this->dataTransformerRegistry,
            $this->persisterFactory,
            $this->authorizationChecker,
            $this->accessHandler,
            $this->accessQbFilter,
            $this->placeholder,
            $dataSchemaConfig,
            $scopeConfig,
            $securityEnabled
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