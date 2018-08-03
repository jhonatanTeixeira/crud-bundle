<?php

namespace Vox\CrudBundle\Crud\Strategy;

use Symfony\Component\HttpFoundation\Request;
use Vox\CrudBundle\Doctrine\PaginableCollection;

interface CrudListableInterface
{
    public function getListResults(Request $request): PaginableCollection;
}