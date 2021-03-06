<?php

namespace Hermes\Api;

use Laminas\Http\Client as LaminasHttpClient;
use Laminas\Http\Response as LaminasHttpResponse;
use Hermes\Exception\RuntimeException;
use Nocarrier\Hal;
use Hermes\Resource\Resource;

final class Response
{
    /**
     * @var \Laminas\Http\Client
     */
    private $httpClient;

    /**
     * @var \Laminas\Http\Response
     */
    private $httpResponse;

    /**
     * @var \Hermes\Resource\Resource
     */
    private $content;

    /**
     * Response constructor.
     * @param LaminasHttpClient $client
     * @param LaminasHttpResponse $response
     * @param int $depth
     * @throws RuntimeException
     */
    public function __construct(LaminasHttpClient $client, LaminasHttpResponse $response, $depth = 0)
    {
        $this->httpClient = $client;
        $this->httpResponse = $response;

        if (!$this->httpResponse->isSuccess()) {
            $error = json_decode($this->httpResponse->getBody());
            if (empty($error)) {
                $error = new \stdClass();
                $error->status = $this->httpResponse->getStatusCode();
                $error->title = $this->httpResponse->getReasonPhrase();
                $error->detail = '';
            }

            if (!isset($error->status)) {
                $error->status = 500;
            }
            if (!isset($error->detail)) {
                $error->detail = 'An error occurred.';
            }

            throw new RuntimeException(json_encode($error, null, 100), $error->status);
        }

        $contentType = '';

        if ($this->httpResponse->getHeaders()->has('Content-Type')) {
            $contentType = $this->httpResponse->getHeaders()
                ->get('Content-Type')
                ->getFieldValue();

            $pos = strpos($contentType, ';');
            if ($pos !== false) {
                $contentType = substr($contentType, 0, $pos);
            }
        }

        if (empty($contentType) || empty($this->httpResponse->getBody())) {
            $this->content = null;
        } elseif ($contentType == 'application/hal+json' || $contentType == 'application/json') {
            $this->content = new Resource(Hal::fromJson($this->httpResponse->getBody(), $depth));
        } elseif ($contentType == 'application/hal+xml' || $contentType == 'application/xml') {
            $this->content = new Resource(Hal::fromXml($this->httpResponse->getBody(), $depth));
        } else {
            throw new RuntimeException(
                "Unable to handle content type '$contentType' for response: '{$this->httpResponse->getBody()}'.",
                500
            );
        }
    }

    /**
     * Get the content.
     *
     * @return \Hermes\Resource\Resource
     */
    public function getContent()
    {
        return $this->content;
    }
}
