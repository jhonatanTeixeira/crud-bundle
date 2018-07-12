<?php

namespace Vox\CrudBundle\Crud\Strategy;

use Symfony\Component\HttpFoundation\Request;

interface CrudListableInterface
{
    public function getResults(Request $request): iterable;

    public function getTotals(Request $request): int;

    public function renderActions(): iterable;

    public function getListFields(): iterable;

    public function getRouteCreateRouteName(): string;
}