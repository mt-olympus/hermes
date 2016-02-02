<?php

namespace Hermes\Api;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ClientFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');
        $clientConfig = $config['hermes'];

        $client = new \Zend\Http\Client($clientConfig['uri'], $clientConfig['http_client']['options']);
        $client->getRequest()->getHeaders()->addHeaders($clientConfig['headers']);

        $hermes = new Client($client, isset($clientConfig['service_name']) ? $clientConfig['service_name'] : null, $clientConfig['depth']);
        if (isset($clientConfig['append_path'])) {
            $hermes->setAppendPath($clientConfig['append_path']);
        }
        return $hermes;
    }
}
