<?php

namespace Vox\CrudBundle\Crud;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class AddFilterEvent extends Event
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(QueryBuilder $queryBuilder, Request $request, EntityManager $entityManager)
    {
        $this->queryBuilder = $queryBuilder;
        $this->request = $request;
        $this->entityManager = $entityManager;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }
}