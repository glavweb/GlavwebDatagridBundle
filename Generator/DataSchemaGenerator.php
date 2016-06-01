<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Generator;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Inflector\Inflector;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class FixtureGenerator
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class DataSchemaGenerator extends Generator
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var string
     */
    private $dataSchemaDir;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var string
     */
    private $templateFile;

    /**
     * @var string
     */
    private $file;

    /**
     * @var bool
     */
    private $fileGenerated = false;

    /**
     * @var bool
     */
    private $fileTemplateUpdated = false;

    /**
     * @param KernelInterface $kernel
     * @param Registry $doctrine
     * @param string $dataSchemaDir
     * @param array|string $skeletonDirectories
     */
    public function __construct(KernelInterface $kernel, Registry $doctrine, $dataSchemaDir, $skeletonDirectories)
    {
        $this->kernel        = $kernel;
        $this->doctrine      = $doctrine;
        $this->dataSchemaDir = $dataSchemaDir;

        $this->setSkeletonDirs($skeletonDirectories);
    }

    /**
     * @param string $modelClass
     * @throws \RuntimeException
     */
    public function generate($modelClass)
    {
        $this->modelClass   = $modelClass;
        $this->templateFile = $this->getTemplatePath($modelClass);
        $this->file         = $this->getFilePath($modelClass);

        $fields       = $this->getFields($modelClass);
        $associations = $this->getAssociations($modelClass);

        $this->fileTemplateUpdated = file_exists($this->templateFile);

        $this->renderFile('data_schema.yml.twig', $this->templateFile, array(
            'class'        => $modelClass,
            'fields'       => $fields,
            'associations' => $associations
        ));

        if (!file_exists($this->file)) {
            $this->renderFile('data_schema.yml.twig', $this->file, array(
                'class'        => $modelClass,
                'fields'       => $fields,
                'associations' => $associations
            ));
            $this->fileGenerated = true;
        }
    }

    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return boolean
     */
    public function isFileGenerated()
    {
        return $this->fileGenerated;
    }

    /**
     * @return boolean
     */
    public function isFileTemplateUpdated()
    {
        return $this->fileTemplateUpdated;
    }

    /**
     * @param string $modelClass
     * @return string
     */
    private function getTemplatePath($modelClass)
    {
        return $this->getFilePath($modelClass, '_examples/');
    }

    /**
     * @param string $modelClass
     * @param string $embeddedDir
     * @return string
     */
    private function getFilePath($modelClass, $embeddedDir = '')
    {
        $dataSchemaName = $this->getDataSchemaName($modelClass);
        $subDirs   = $this->getSubDirs($modelClass);
        $subDirs   = array_map(function ($item) {
            return Inflector::tableize($item);
        }, $subDirs);

        $fixturePath = $this->dataSchemaDir . '/' . $embeddedDir
            . ($subDirs ? implode('/', $subDirs) . '/' : '')
            .  $dataSchemaName . '.yml'
        ;

        return $fixturePath;
    }

    /**
     * @param string $modelClass
     * @return string
     */
    private function getDataSchemaName($modelClass)
    {
        $modelBasename = $this->getModelBasename($modelClass);
        $dataSchemaName = Inflector::tableize($modelBasename) . '.schema';

        return $dataSchemaName;
    }

    /**
     * @param $modelClass
     * @return mixed
     */
    public function getModelBasename($modelClass)
    {
        $modelBasename = current(array_slice(explode('\\', $modelClass), -1));

        return $modelBasename;
    }

    /**
     * @param string $modelClass
     * @return BundleInterface
     */
    public function getBundle($modelClass)
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            if (strpos($modelClass, $bundle->getNamespace() . '\\') === 0) {
                return $bundle;
            }
        }

        throw new \RuntimeException('The bundle not found for model class "' . $modelClass . '".');
    }

    /**
     * @param string $modelClass
     * @return array
     */
    private function getSubDirs($modelClass)
    {
        $bundle        = $this->getBundle($modelClass);
        $modelBasename = $this->getModelBasename($modelClass);

        $entityPath = $bundle->getNamespace() . '\Entity\\';
        if (strpos($modelClass, $entityPath) === 0) {
            $modelFullName = substr($modelClass, strlen($entityPath));

            if ($modelFullName == $modelBasename) {
                return [];
            }

            if (strrpos($modelFullName, $modelBasename) > 0) {
                $subDirs = substr($modelFullName, 0, -strlen($modelBasename)-1);

                return explode('\\', $subDirs);
            }
        }

        throw new \RuntimeException('The model class should contain "' . $entityPath . '".');
    }

    /**
     * @param $modelClass
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function getFields($modelClass)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $this->doctrine->getManager()->getClassMetadata($modelClass);

        $fields     = [];
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            $type = $fieldMapping['type'];

            $fields[$fieldName] = $type;
        }

        return $fields;
    }

    /**
     * @param $modelClass
     * @return array
     */
    private function getAssociations($modelClass)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $this->doctrine->getManager()->getClassMetadata($modelClass);

        $associations        = [];
        $associationMappings = $metadata->getAssociationMappings();
        foreach ($associationMappings as $associationMapping) {
            $fieldName = $associationMapping['fieldName'];
            $associationModelClass = $associationMapping['targetEntity'];
            $associationFields     = $this->getFields($associationModelClass);

            $associations[$fieldName] = [
                'class'  => $associationModelClass,
                'fields' => $associationFields
            ];
        }

        return $associations;
    }
}
