<?php

namespace Vox\CrudBundle\Crud\Strategy;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Vox\CrudBundle\Crud\AddFilterEvent;
use Vox\CrudBundle\Crud\FilterInterface;
use Vox\CrudBundle\Doctrine\PaginableCollection;

trait DefaultListTrait
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    private function createQueryBuilder(Request $request): QueryBuilder
    {
        /* @var $queryBuilder QueryBuilder */
        $queryBuilder = $this->doctrine->getRepository($this->className)
            ->createQueryBuilder('e');

        $eventName = sprintf('%s.%s', DefaultCrudStrategy::EVENT_ADD_FILTERS, $request->get('_route'));

        $this->eventDispatcher
            ->dispatch($eventName, new AddFilterEvent($queryBuilder, $request, $this->doctrine->getManager()));

        return $queryBuilder;
    }

    public function getListResults(Request $request): PaginableCollection
    {
        $queryBuilder = $this->createQueryBuilder($request);

        return new PaginableCollection($queryBuilder);
    }
}