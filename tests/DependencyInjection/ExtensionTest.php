<?php

namespace Vox\CrudBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vox\CrudBundle\VoxCrudBundle;

class ExtensionTest extends TestCase
{
    private function getContainer(array $configs = array())
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir() . '/crud');
        $container->setParameter('kernel.bundles', array('VoxCrudBundle' => VoxCrudBundle::class));
        $bundle = new VoxCrudBundle();
        $extension = $bundle->getContainerExtension();
        $extension->load($configs, $container);
        return $container;
    }
}