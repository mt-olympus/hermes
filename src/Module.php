<?php

namespace Hermes;

use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;

/**
 * @codeCoverageIgnore
 */
class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\ClassMapAutoloader' => [
                __DIR__.'/../autoload_classmap.php',
            ],
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__.'/../src/'.str_replace('\\', '/', __NAMESPACE__),
                ],
            ],
        ];
    }

    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }
}
