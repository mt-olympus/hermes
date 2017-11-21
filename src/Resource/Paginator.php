<?php

namespace Hermes\Resource;

final class Paginator
{
    private $pageSize = 0;
    private $pageCount = 0;
    private $totalItems = 0;
    private $page = 1;

    public function __construct(array $data = null)
    {
        if (!empty($data)) {
            $this->pageCount = (int) ($data['_page_count'] ?? $data['page_count'] ?? 0);
            $this->pageSize = (int) ($data['_page_size'] ?? $data['page_size'] ?? 0);
            $this->totalItems = (int) ($data['_total_items'] ?? $data['total_items'] ?? 0);
            $this->page = (int) ($data['_page'] ?? $data['page'] ?? 1);
        }
    }

    /**
     * @param int $input
     * @return Paginator
     */
    public function setPageSize(int $input) : self
    {
        $this->pageSize = $input;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize() : int
    {
        return $this->pageSize;
    }

    /**
     * @param int $input
     * @return Paginator
     */
    public function setPageCount(int $input) : self
    {
        $this->pageCount = $input;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageCount() : int
    {
        return $this->pageCount;
    }

    /**
     * @param int $input
     * @return Paginator
     */
    public function setTotalItems(int $input) : self
    {
        $input = (int) $input;
        $this->totalItems = $input;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalItems() : int
    {
        return $this->totalItems;
    }

    /**
     * @return int
     */
    public function getPage() : int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return Paginator
     */
    public function setPage(int $page) : self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasMorePages() : bool
    {
        return $this->page < $this->pageCount;
    }

    /**
     * @return int
     */
    public function getNextPage() : int
    {
        return ++$this->page;
    }
}
