<?php

namespace Hermes\Api;

use Cerberus\CerberusInterface;
use Hermes\Exception\NotAvailableException;
use Hermes\Exception\RuntimeException;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Http\Client as ZendHttpClient;
use Ramsey\Uuid\Uuid;

final class Client
{
    use EventManagerAwareTrait;

    /**
     * @const int Request timeout
     */
    const TIMEOUT = 60;

    /**
     * @var \Zend\Http\Client Instance
     */
    private $zendClient;

    /**
     * Depth to generate the response from the _embedded resources.
     *
     * @var int
     */
    private $depth;

    private $circuitBreaker;

    private $loadBalance;

    private $serviceName;

    private $responseTime = 0;

    private $appendPath = false;

    public function __construct(
        ZendHttpClient $client = null,
        $serviceName = null,
        $depth = 1
    ) {
        $client = ($client instanceof ZendHttpClient) ? $client : new ZendHttpClient();
        $this->zendClient = $client;
        $this->serviceName = $serviceName;
        $this->depth = (int) $depth;
    }

    /**
     * Get the Zend\Http\Client instance.
     *
     * @return Zend\Http\Client
     */
    public function getZendClient()
    {
        return $this->zendClient;
    }

    public function getResponse()
    {
        return $this->zendClient->getResponse();
    }

    private function isAvailable()
    {
        if ($this->circuitBreaker !== null) {
            return $this->circuitBreaker->isAvailable($this->serviceName);
        }

        if ($this->loadBalance !== null) {
            return $this->loadBalance;
        }

        return true;
    }

    private function reportFailure()
    {
        if ($this->circuitBreaker === null) {
            return;
        }

        $this->circuitBreaker->reportFailure($this->serviceName);
    }

    private function reportSuccess()
    {
        if ($this->circuitBreaker === null) {
            return;
        }

        $this->circuitBreaker->reportSuccess($this->serviceName);
    }

    /**
     * Perform the request to api server.
     *
     * @param String $path    Example: "/v1/endpoint"
     * @param Array  $headers
     */
    private function doRequest($path, $headers = [])
    {
        if (!$this->isAvailable()) {
            throw new NotAvailableException('Service not available.');
        }

        if ($this->loadBalance !== null) {
            $uri = $this->loadBalance->getUri($this->serviceName);
            $this->zendClient->setUri($uri);
        }
        if ($this->appendPath && strlen($path) > 0 && $this->zendClient->getUri()->getPath() != '/') {
            $path = $this->zendClient->getUri()->getPath() . $path;
        }
        $this->zendClient->getUri()->setPath($path);

        $this->zendClient->getRequest()->getHeaders()->addHeaders($headers);

        if (!$this->zendClient->getRequest()->getHeaders()->has('X-Request-Id')) {
            $this->addRequestId();
        }

        $this->addRequestName($this->serviceName);

        $this->getEventManager()->trigger('request.pre', $this);

        $this->responseTime = 0;

        try {
            $requestTime = microtime(true);

            $zendHttpResponse = $this->zendClient->send();

            $this->responseTime = (float) sprintf('%.2f', (microtime(true) - $requestTime) * 1000);

            $this->addRequestTime($this->responseTime);

            $response = new Response($this->zendClient, $zendHttpResponse, $this->depth);
            $this->reportSuccess();
        } catch (RuntimeException $ex) {
            $this->reportFailure();
            $this->getEventManager()->trigger('request.fail', $this, $ex);
            throw new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        } catch (\Exception $ex) {
            $this->reportFailure();
            $this->getEventManager()->trigger('request.fail', $this, $ex);
            throw new RuntimeException($ex->getMessage(), 500, $ex);
        }

        $this->getEventManager()->trigger('request.post', $this);

        $content = $response->getContent();

        return $content;
    }

    public function addRequestId($id = null) {
        if ($id == null) {
            $id = Uuid::uuid4();
        }

        $headers = $this->zendClient->getRequest()->getHeaders();
        if ($headers->has('X-Request-Id')) {
            $headers->removeHeader($headers->get('X-Request-Id'));
        }
        $headers->addHeaderLine('X-Request-Id', $id);

        return $this;
    }

    public function importRequestId($request)
    {
        if (!is_object($request) || !method_exists($request, 'getHeader')) {
            return;
        }
        $header = $request->getHeader('X-Request-Id');
        if (!$header) {
            return;
        }
        if (is_object($header)) {
            $this->addRequestId($header->getFieldValue());
        } else {
            $this->addRequestId($header[0]);
        }
    }

    public function addRequestTime($time) {
        $headers = $this->zendClient->getRequest()->getHeaders();
        if ($headers->has('X-Request-Time')) {
            return;
        }
        $headers->addHeaderLine('X-Request-Time', sprintf('%2.2fms', $time));

        return $this;
    }

    public function addRequestName($name = null) {
        if (empty($name)) {
            return;
        }
        $headers = $this->zendClient->getRequest()->getHeaders();
        if ($headers->has('X-Request-Name')) {
            return;
        }
        $headers->addHeaderLine('X-Request-Name', $name);

        return $this;
    }

    public function get($path, array $data = [], array $headers = [])
    {
        $this->zendClient->setMethod('GET')
                         ->setParameterGet($data);

        return $this->doRequest($path, $headers);
    }

    public function post($path, array $data, array $headers = [])
    {
        $this->zendClient->setMethod('POST')
                         ->setRawBody(json_encode($data));

        return $this->doRequest($path, $headers);
    }

    public function put($path, array $data, array $headers = [])
    {
        $this->zendClient->setMethod('PUT')
                         ->setRawBody(json_encode($data));

        return $this->doRequest($path, $headers);
    }

    public function patch($path, array $data, array $headers = [])
    {
        $this->zendClient->setMethod('PATCH')
                         ->setRawBody(json_encode($data));

        return $this->doRequest($path, $headers);
    }

    public function delete($path, array $headers = [])
    {
        $this->zendClient->setMethod('DELETE');

        return $this->doRequest($path, $headers);
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function setDepth($depth)
    {
        $this->depth = (int) $depth;

        return $this;
    }

    public function getCircuitBreaker()
    {
        return $this->circuitBreaker;
    }

    public function setCircuitBreaker(CerberusInterface $circuitBreaker)
    {
        $this->circuitBreaker = $circuitBreaker;

        return $this;
    }

    public function getLoadBalance()
    {
        return $this->loadBalance;
    }

    public function setLoadBalance($loadBalance)
    {
        $this->loadBalance = $loadBalance;
        return $this;
    }

    public function getServiceName()
    {
        return $this->serviceName;
    }

    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    public function getResponseTime()
    {
        return $this->responseTime;
    }

    public function setAppendPath($appendPath)
    {
        $this->appendPath = (bool) $appendPath;
        return $this;
    }
}
