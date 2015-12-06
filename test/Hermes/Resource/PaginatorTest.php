<?php

namespace Hermes\Resource;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-11-10 at 18:27:24.
 */
class PaginatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Paginator
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Paginator([
            Paginator::PAGE => 1,
            Paginator::PAGE_COUNT => 2,
            Paginator::PAGE_SIZE => 10,
            Paginator::TOTAL_ITEMS => 18,
        ]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Hermes\Resource\Paginator::__construct
     */
    public function testConstructor()
    {
        $this->assertSame(1, $this->object->getPage());
        $this->assertSame(2, $this->object->getPageCount());
        $this->assertSame(10, $this->object->getPageSize());
        $this->assertSame(18, $this->object->getTotalItems());
    }

    /**
     * @covers Hermes\Resource\Paginator::setPageSize
     * @covers Hermes\Resource\Paginator::getPageSize
     */
    public function testSetGetPageSize()
    {
        $this->assertSame($this->object, $this->object->setPageSize(15));
        $this->assertSame(15, $this->object->getPageSize());
    }

    /**
     * @covers Hermes\Resource\Paginator::setPageCount
     * @covers Hermes\Resource\Paginator::getPageCount
     */
    public function testSetGetPageCount()
    {
        $this->assertSame($this->object, $this->object->setPageCount(15));
        $this->assertSame(15, $this->object->getPageCount());
    }

    /**
     * @covers Hermes\Resource\Paginator::setTotalItems
     * @covers Hermes\Resource\Paginator::getTotalItems
     */
    public function testSetGetTotalItems()
    {
        $this->assertSame($this->object, $this->object->setTotalItems(15));
        $this->assertSame(15, $this->object->getTotalItems());
    }

    /**
     * @covers Hermes\Resource\Paginator::getPage
     * @covers Hermes\Resource\Paginator::setPage
     */
    public function testGetPage()
    {
        $this->assertSame($this->object, $this->object->setPage(15));
        $this->assertSame(15, $this->object->getPage());
    }

    /**
     * @covers Hermes\Resource\Paginator::hasMorePages
     */
    public function testHasMorePages()
    {
        $this->object->setPageCount(10);
        $this->object->setPage(1);
        $this->assertTrue($this->object->hasMorePages());
    }

    /**
     * @covers Hermes\Resource\Paginator::getNextPage
     */
    public function testGetNextPage()
    {
        $this->object->setPage(1);
        $this->assertSame(2, $this->object->getNextPage());
    }
}
