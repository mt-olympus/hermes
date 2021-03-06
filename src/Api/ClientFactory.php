<?php

namespace Hermes\Api;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

class ClientFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $clientConfig = $config['hermes'];

        $client = new \Laminas\Http\Client($clientConfig['uri'], $clientConfig['http_client']['options']);
        $client->getRequest()->getHeaders()->addHeaders($clientConfig['headers']);

        $hermes = new Client(
            $client,
            isset($clientConfig['service_name']) ? $clientConfig['service_name'] : null,
            $clientConfig['depth']
        );
        if (isset($clientConfig['append_path'])) {
            $hermes->setAppendPath($clientConfig['append_path']);
        }
        if (isset($clientConfig['collector']) && $clientConfig['collector'] !== false) {
            $collector = $container->get($clientConfig['collector']);
            if (is_object($collector)) {
                $collector->attach($hermes);
            }
        }
        return $hermes;
    }

    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, ClientFactory::class);
    }
}
