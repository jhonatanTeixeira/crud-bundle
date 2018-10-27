<?php

namespace Vox\CrudBundle\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginableCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var QueryBuilder|PersistentCollection
     */
    private $dataSource;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $page = 1;

    private $iterator;

    public function __construct($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTotalPages()
    {
        return ceil($this->count() / ($this->limit ?? 1));
    }

    private function getIteratorForQueryBuilder(QueryBuilder $queryBuilder)
    {
        if (isset($this->limit)) {
            $queryBuilder->setMaxResults($this->limit)
                ->setFirstResult($this->calculateOffset());

            return new Paginator($queryBuilder, $queryBuilder->getQuery()->contains('join'));
        }

        return new ArrayCollection($queryBuilder->getQuery()->execute() ?: []);
    }

    private function getIteratorForPersistentCollection(PersistentCollection $collection)
    {
        if (isset($this->limit)) {
            return new ArrayCollection($collection->slice($this->calculateOffset(), $this->limit));
        }

        return $collection;
    }

    private function calculateOffset(): int
    {
        return $this->page > 1 ? ($this->page * $this->limit) - $this->limit : 0;
    }

    public function getIterator()
    {
        if ($this->dataSource instanceof QueryBuilder) {
            return $this->iterator ?? $this->iterator = $this->getIteratorForQueryBuilder($this->dataSource);
        }

        if ($this->dataSource instanceof PersistentCollection) {
            return $this->iterator ?? $this->iterator = $this->getIteratorForPersistentCollection($this->dataSource);
        }

        throw new \InvalidArgumentException(
            "invalid type for paginator, only QueryBuilder or PersistentCollection allowed"
        );
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->dataSource;
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    public function count()
    {
        return $this->getIterator()->count();
    }
}