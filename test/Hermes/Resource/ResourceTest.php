<?php

namespace Hermes\Resource;

use Nocarrier\Hal;
use Hermes\Exception\InvalidArgumentException;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-11-10 at 18:40:10.
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Resource
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $sample = <<<JSON
        {
            "id": 1,
            "name": "test",
            "_links":{
                "self":{"href":"http:\/\/127.0.0.1\/2"},
                "prev":{"href":"http:\/\/127.0.0.1\/1"},
                "next":{"href":"http:\/\/127.0.0.1\/3"}
            },
            "_embedded":{
                "item":[
                    {
                        "_links":{
                            "self":{"href":"http:\/\/127.0.0.1\/"},
                            "next":{"href":"http:\/\/127.0.0.1\/3"}
                        },
                        "key": "value1"
                    },
                    {
                        "_links":{
                            "self":{"href":"http:\/\/127.0.0.1\/"},
                            "next":{"href":"http:\/\/127.0.0.1\/3"}
                        },
                        "key": "value2"
                    }
                ],
                "item2":[
                    {
                        "_links":{
                            "self":{"href":"http:\/\/127.0.0.1\/"},
                            "next":{"href":"http:\/\/127.0.0.1\/3"}
                        },
                        "key": "value1"
                    }
                ]
            }
        }
JSON;
        $this->object = new Resource(Hal::fromJson($sample, 2));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Hermes\Resource\Resource::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(Paginator::class, $this->object->getPaginator());
    }

    /**
     * @covers Hermes\Resource\Resource::setUri
     * @covers Hermes\Resource\Resource::getUri
     */
    public function testSetGetUri()
    {
        $this->assertSame($this->object, $this->object->setUri('http://127.0.0.1'));
        $this->assertSame('http://127.0.0.1', $this->object->getUri());
    }

    /**
     * @covers Hermes\Resource\Resource::getLinks
     */
    public function testGetLinks()
    {
        $this->assertCount(2, $this->object->getLinks());
    }

    /**
     * @covers Hermes\Resource\Resource::getLink
     */
    public function testGetLink()
    {
        $this->assertSame('http://127.0.0.1/3', $this->object->getLink('next')[0]->getUri());
    }

    /**
     * @covers Hermes\Resource\Resource::setPaginator
     * @covers Hermes\Resource\Resource::getPaginator
     */
    public function testSetGetPaginator()
    {
        $this->assertSame($this->object, $this->object->setPaginator([]));
        $this->assertInstanceOf(Paginator::class, $this->object->getPaginator());
    }

    /**
     * @covers Hermes\Resource\Resource::isCollection
     */
    public function testIsCollection()
    {
        $this->object->setPaginator([
            Paginator::PAGE_SIZE => 3,
        ]);
        $this->assertTrue($this->object->isCollection());
    }

    /**
     * @covers Hermes\Resource\Resource::getData
     */
    public function testGetData()
    {
        $data = $this->object->getData(true);
        $this->assertCount(4, $data);
        $this->assertSame(1, $data['id']);
        $this->assertSame('test', $data['name']);
        $this->assertArrayHasKey('item', $data);
    }

    /**
     * @covers Hermes\Resource\Resource::getFirstResource
     */
    public function testGetFirstResource()
    {
        $resource = $this->object->getFirstResource('item');
        $this->assertCount(1, $resource);
        $this->assertSame('value1', $resource['key']);
    }

    /**
     * @covers Hermes\Resource\Resource::getFirstResource
     */
    public function testGetInvalidFirstResource()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->object->getFirstResource('');
    }

    /**
     * @covers Hermes\Resource\Resource::getResources
     */
    public function testGetResources()
    {
        $resources = $this->object->getResources();
        $this->assertCount(2, $resources);
        $this->assertCount(2, $resources['item']);
        $this->assertSame('value2', $resources['item'][1]['key']);
    }

    /**
     * @covers Hermes\Resource\Resource::getResources
     */
    public function testGetFilteredResources()
    {
        $resources = $this->object->getResources('item');
        $this->assertCount(2, $resources);
        $this->assertSame('value2', $resources[1]['key']);
    }
}
