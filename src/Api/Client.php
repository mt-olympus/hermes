<?php

namespace Hermes\Api;

use Los\Cerberus\CerberusInterface;
use Hermes\Exception\NotAvailableException;
use Hermes\Exception\RuntimeException;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Http\Client as LaminasHttpClient;
use Ramsey\Uuid\Uuid;

class Client
{
    use EventManagerAwareTrait;

    /**
     * @const int Request timeout
     */
    const TIMEOUT = 60;

    /**
     * @var \Laminas\Http\Client Instance
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

    /**
     * Extra information. Provided by the client
     * @var mixed
     */
    private $extra;

    public function __construct(
        LaminasHttpClient $client = null,
        $serviceName = null,
        $depth = 1
    ) {
        $client = ($client instanceof LaminasHttpClient) ? $client : new LaminasHttpClient();
        $this->zendClient = $client;
        $this->serviceName = $serviceName;
        $this->depth = (int) $depth;
    }

    /**
     * Get the Laminas\Http\Client instance.
     *
     * @return \Laminas\Http\Client
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
     * @param $path string                  Example: "/v1/endpoint"
     * @param array $headers
     * @return \Hermes\Resource\Resource
     * @throws NotAvailableException
     * @throws RuntimeException
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

    public function addRequestId($id = null)
    {
        if ($id == null) {
            if (defined('REQUEST_ID')) {
                $id = REQUEST_ID;
            } else {
                $id = Uuid::uuid4();
            }
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

    public function incrementRequestDepth($request)
    {
        if (!is_object($request) || !method_exists($request, 'getHeader')) {
            return;
        }
        $depth = 0;

        if ($request instanceof ServerRequest) {
            if ($request->hasHeader('X-Request-Depth')) {
                $header = $request->getHeader('X-Request-Depth');
                $depth = $header[0];
            }
        } else {
            $headers = $request->getHeaders();
            if ($headers->has('X-Request-Depth')) {
                $header = $request->getHeader('X-Request-Depth');
                if (is_object($header)) {
                    $depth = $header->getFieldValue();
                } else {
                    $depth = $header[0];
                }
            }
        }
        $depth++;

        $headers = $this->zendClient->getRequest()->getHeaders();
        if ($headers->has('X-Request-Depth')) {
            $headers->removeHeader($headers->get('X-Request-Depth'));
        }
        $headers->addHeaderLine('X-Request-Depth', $depth);
    }

    public function addRequestTime($time)
    {
        $headers = $this->zendClient->getRequest()->getHeaders();
        if ($headers->has('X-Request-Time')) {
            $headers->removeHeader($headers->get('X-Request-Time'));
        }
        $headers->addHeaderLine('X-Request-Time', sprintf('%2.2fms', $time));

        return $this;
    }

    public function addRequestName($name = null)
    {
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
            ->setRawBody('')
            ->setParameterGet($data);

        return $this->doRequest($path, $headers);
    }

    public function post($path, $data, array $headers = [], array $queryParams = [])
    {
        if (is_array($data)) {
            $data = json_encode($data, null, 100);
        }
        $this->zendClient->setMethod('POST')
            ->setRawBody($data)
            ->setParameterGet($queryParams);

        return $this->doRequest($path, $headers);
    }

    public function put($path, $data, array $headers = [], array $queryParams = [])
    {
        if (is_array($data)) {
            $data = json_encode($data, null, 100);
        }
        $this->zendClient->setMethod('PUT')
            ->setRawBody($data)
            ->setParameterGet($queryParams);

        return $this->doRequest($path, $headers);
    }

    public function patch($path, $data, array $headers = [], array $queryParams = [])
    {
        if (is_array($data)) {
            $data = json_encode($data, null, 100);
        }
        $this->zendClient->setMethod('PATCH')
            ->setRawBody($data)
            ->setParameterGet($queryParams);

        return $this->doRequest($path, $headers);
    }

    public function delete($path, array $headers = [], array $queryParams = [])
    {
        $this->zendClient->setMethod('DELETE')
            ->setRawBody('')
            ->setParameterGet($queryParams);

        $oldHeaders = $this->zendClient->getRequest()->getHeaders();
        if ($oldHeaders->has('Content-Type')) {
            $oldHeaders->removeHeader($oldHeaders->get('Content-Type'));
        }

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
    /**
     * @return mixed $extra
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param $extra
     * @return $this
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }
}
