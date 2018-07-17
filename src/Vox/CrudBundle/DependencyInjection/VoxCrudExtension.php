<?php

namespace Vox\CrudBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Vox\CrudBundle\Controller\CrudController;
use Vox\CrudBundle\Crud\Filter\SimpleAndFilter;
use Vox\CrudBundle\Form\CrudFormEvent;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class VoxCrudExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        $this->createApiRoutes($config, $container);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    private function createApiRoutes(array $configs, ContainerBuilder $container)
    {
        $routes = new Definition(RouteCollection::class);
        $routes->setPublic(false);

        foreach ($configs['routes'] as $name => $routeParams) {
            $entityName      = $routeParams['class'];
            $operations      = $routeParams['operations'];
            $type            = $routeParams['type'];
            $contextClass    = $routeParams['contextObject'] ?? CrudFormEvent::class;
            $controllerClass = $routeParams['controllerClass'] ?? CrudController::class;
            $strategy        = $routeParams['strategy'] ?? null;
            $defaults        = ['_format' => 'html'];
            $requirements    = [];
            $templates       = [
                'formTemplate' => $routeParams['formTemplate'] ?? "/default/form.html.twig",
                'listTemplate' => $routeParams['formTemplate'] ?? "/default/list.html.twig",
                'viewTemplate' => $routeParams['formTemplate'] ?? "/default/view.html.twig",
            ];

            $filters = [];

            if (isset($routeParams['queriable_fields'])) {
                $container->register('crud.simple_filter', SimpleAndFilter::class)
                    ->addArgument($routeParams['queriable_fields']);
            }

            if (isset($routeParams['filters'])) {
                foreach ($routeParams['filters'] as $filter) {
                    $filters[] = new Reference($filter);
                }
            }

            foreach ($operations as $operation) {
                switch ($operation) {
                    case 'list':
                        $path    = sprintf('/%s', $name);
                        $methods = ['GET'];
                        $action  = 'listAction';
                        break;
                    case 'get':
                        $path    = sprintf('/%s/{id}', $name);
                        $methods = ['GET'];
                        $action  = 'getAction';
                        $requirements   = ["id" => "\d+"];
                        break;
                    case 'form':
                        $path    = sprintf('/%s/form', $name);
                        $methods = ['GET'];
                        $action  = 'formAction';
                        break;
                    case 'post':
                        $path    = sprintf('/%s', $name);
                        $methods = ['POST', 'PATCH'];
                        $action  = 'postAction';
                        break;
                    case 'put':
                        $path    = sprintf('/%s/{id}', $name);
                        $methods = ['PUT', 'PATCH'];
                        $action  = 'putAction';
                        $requirements   = ["id" => "\d+"];
                        break;
                    case 'delete':
                        $path    = sprintf('/%s/{id}', $name);
                        $methods = ['DELETE'];
                        $action  = 'deleteAction';
                        $requirements = ["id" => "\d+"];
                        break;
                }

                $controller = new Definition(
                    $controllerClass,
                    [
                        $entityName,
                        $type,
                        $contextClass,
                        new Reference('doctrine'),
                        new Reference('app.form.factory'),
                        new Reference('event_dispatcher'),
                        new Reference('router'),
                        $templates,
                        $strategy ? new Reference($strategy) : null,
                        $filters
                    ]
                );
                $controller->setPublic(true);

                $controllerService = sprintf('controler.%s', $entityName);

                $container->setDefinition($controllerService, $controller);

                $actionCallable = sprintf(
                    '%s:%s',
                    $controllerService,
                    in_array($action, ['postAction', 'putAction']) ? 'receiveDataAction' : $action
                );

                $defaults['_controller'] = $actionCallable;

                $route = new Definition(Route::class, [$path]);
                $route->addMethodCall('setMethods', [$methods]);
                $route->addMethodCall('setDefaults', [$defaults]);
                $route->addMethodCall('setRequirements', [$requirements]);
                $route->setPublic(false);

                $routeService = sprintf('route.%s.%s', $entityName, $action);

                $container->setDefinition($routeService, $route);

                $routes->addMethodCall('add', [sprintf('%s_%s', $name, $action), new Reference($routeService)]);
            }

            $container->setDefinition('api.routes', $routes);
        }
    }
}
