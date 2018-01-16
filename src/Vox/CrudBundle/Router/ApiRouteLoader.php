<?php

namespace Vox\CrudBundle\Router;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class ApiRouteLoader extends Loader
{
    /**
     * @var RouteCollection
     */
    private $routeCollection;
    
    public function __construct(RouteCollection $routeCollection)
    {
        $this->routeCollection = $routeCollection;
    }
    
    public function load($resource, $type = null)
    {
        return $this->routeCollection;
    }

    public function supports($resource, $type = null): bool
    {
        return $type == 'crud';
    }
}
