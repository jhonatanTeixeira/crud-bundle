<?php

namespace Vox\CrudBundle\Crud;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

interface FilterInterface
{
    public function applyFilter(QueryBuilder $queryBuilder, Request $request): void;
}