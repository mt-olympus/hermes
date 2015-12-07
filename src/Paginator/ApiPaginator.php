<?php
namespace Hermes\Paginator;

use Zend\Paginator\Adapter\AdapterInterface;
use Hermes\Api\Client;

class ApiPaginator implements AdapterInterface
{
    private $client;
    private $url;
    private $query;
    private $collectionName;

    private $data;

    public function __construct(Client $apiClient, $url, $collectionName, $query)
    {
        $this->client = $apiClient;
        $this->url = $url;
        $this->query = $query;
        $this->collectionName = $collectionName;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @see \Zend\Paginator\Adapter\AdapterInterface::getItems()
     */
    public function getItems($offset, $itemCountPerPage)
    {
        if ($this->data == null) {
            $ret = $this->client->get($this->url,$this->query);
            $this->data = $ret->getData();
        }

        return $this->data[$this->collectionName];
    }
    /**
     * {@inheritDoc}
     * @see Countable::count()
     */
    public function count($mode = null)
    {
        if ($this->data == null) {
            $ret = $this->client->get($this->url,$this->query);
            $this->data = $ret->getData();
        }
        return $this->data['total_items'];
    }

}
