<?php

namespace Hermes\Api;

use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

class ClientFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $clientConfig = $config['hermes'];

        $client = new \Zend\Http\Client($clientConfig['uri'], $clientConfig['http_client']['options']);
        $client->getRequest()->getHeaders()->addHeaders($clientConfig['headers']);

        $hermes = new Client($client, isset($clientConfig['service_name']) ? $clientConfig['service_name'] : null, $clientConfig['depth']);
        if (isset($clientConfig['append_path'])) {
            $hermes->setAppendPath($clientConfig['append_path']);
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
