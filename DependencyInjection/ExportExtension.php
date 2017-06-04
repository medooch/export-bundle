<?php

namespace Medooch\Bundles\ExportBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ExportExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['entities'])) {
            throw new \Exception('The entities node must be defined!');
        }

        $entities = $config['entities'];
        foreach ($entities as $key => $parameters) {
            if (!isset($parameters['query'])) {
                throw new \Exception('The query node must be defined!');
            } else {
                $container->setParameter($key, $parameters);
            }
        }
    }
}
