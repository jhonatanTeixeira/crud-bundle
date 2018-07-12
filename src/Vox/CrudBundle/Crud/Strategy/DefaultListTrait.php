<?php

namespace Vox\CrudBundle\Crud\Strategy;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Vox\CrudBundle\Crud\FilterInterface;

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
     * @var FilterInterface[]
     */
    private $filters = [];

    private function createQueryBuilder(Request $request): QueryBuilder
    {
        /* @var $queryBuilder QueryBuilder */
        $queryBuilder = $this->doctrine->getRepository($this->className)
            ->createQueryBuilder('e');

        foreach ($this->filters as $filter) {
            $filter->applyFilter($queryBuilder, $request);
        }

        return $queryBuilder;
    }

    public function getResults(Request $request): iterable
    {
        $page    = $request->get('page', 1);
        $limit   = $request->get('limit', 30);
        $order   = $request->get('order', []);

        $queryBuilder = $this->createQueryBuilder($request, $entityClassName);

        $queryBuilder->setMaxResults($limit)->setFirstResult(($page - 1) * $limit);

        if ($order) {
            foreach ($order as $key => $value) {
                $queryBuilder->addOrderBy($key, $value);
            }
        }

        return $queryBuilder->getQuery()->execute();
    }

    public function getTotals(Request $request, string $entityClassName): int
    {
        return (int) $this->createQueryBuilder($request, $entityClassName)
            ->select('COUNT(1)')
            ->getQuery()
            ->getScalarResult();
    }
}