<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds tagged glavweb_datagrid.data_transformer services to DataTransformerRegister service.
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DataTransformerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('glavweb_datagrid.data_transformer_registry')) {
            return;
        }

        // Data transformers
        $transformerRegistryDefinition = $container->getDefinition('glavweb_datagrid.data_transformer_registry');
        foreach ($container->findTaggedServiceIds('glavweb_datagrid.data_transformer') as $id => $tags) {
            if (!isset($tags[0]['transformer_name'])) {
                continue;
            }

            $transformerRegistryDefinition->addMethodCall('add', [new Reference($id), $tags[0]['transformer_name']]);
        }
        
        // Extensions
        foreach ($container->findTaggedServiceIds('glavweb_datagrid.extension') as $id => $tags) {
            $transformerRegistryDefinition->addMethodCall('loadExtension', [new Reference($id)]);
        }
    }
}
