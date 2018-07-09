<?php

namespace Vox\CrudBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Vox\CrudBundle\Crud\FilterInterface;
use Vox\CrudBundle\Crud\Strategy\CrudExtraValuesInterface;
use Vox\CrudBundle\Crud\Strategy\CrudPostFlushableInterface;
use Vox\CrudBundle\Crud\Strategy\CrudStrategyInterface;
use Vox\CrudBundle\Crud\Strategy\DefaultCrudStrategy;
use Vox\CrudBundle\Form\CrudFormFactoryInterface;

class CrudController
{
    private $entityClassName;

    private $typeClassName;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var CrudFormFactoryInterface
     */
    private $formFactory;

    private $contextObjectName;

    /**
     * @var CrudStrategyInterface
     */
    private $strategy;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    
    /**
     * @var RouterInterface
     */
    private $router;
    
    /**
     * @var array
     */
    private $templates;

    /**
     * @var FilterInterface[]
     */
    private $filters;

    public function __construct(
        string $entityClassName,
        string $typeClassName,
        string $contextObjectName,
        RegistryInterface $doctrine,
        CrudFormFactoryInterface $formFactory,
        EventDispatcherInterface $eventDispatcher,
        RouterInterface $router,
        array $templates,
        CrudStrategyInterface $crudStrategy = null,
        array $filters = []
    ) {
        $this->entityClassName   = $entityClassName;
        $this->typeClassName     = $typeClassName;
        $this->contextObjectName = $contextObjectName;
        $this->doctrine          = $doctrine;
        $this->formFactory       = $formFactory;
        $this->eventDispatcher   = $eventDispatcher;
        $this->router            = $router;
        $this->templates         = $templates;
        $this->strategy          = $crudStrategy ?? new DefaultCrudStrategy($entityClassName, $doctrine);
        $this->filters           = $filters;
    }

    public function listAction(Request $request)
    {
        $page    = $request->get('page', 1);
        $limit   = $request->get('limit', 30);
        $order   = $request->get('order', []);

        /* @var $queryBuilder QueryBuilder */
        $queryBuilder = $this->doctrine->getRepository($this->entityClassName)
            ->createQueryBuilder('e');

        foreach ($this->filters as $filter) {
            $filter->applyFilter($queryBuilder, $request);
        }

        $countQb = clone $queryBuilder;

        $total = $countQb->select('COUNT(1)')->getQuery()->getScalarResult();

        $queryBuilder->setMaxResults($limit)->setFirstResult(($page - 1) * $limit);

        if ($order) {
            foreach ($order as $key => $value) {
                $queryBuilder->addOrderBy($key, $value);
            }
        }

        $list = $queryBuilder->getQuery()->execute();

        return $this->createParamList(['list' => $list, 'total' => $total]);
    }
    
    private function createParamList(array $params): array
    {
        if ($this->strategy instanceof CrudExtraValuesInterface) {
            return array_merge($params, $this->strategy->getExtraViewValues());
        }
        
        return $params;
    }

    public function formAction(Request $request)
    {
        $form = $this->createForm($request);

        return ['form' => $form->createView()];
    }

    public function postAction(Request $request)
    {
        $form = $this->createForm($request);

        $form->handleRequest($request);

        $status = 200;
        
        if ($form->isValid()) {
            $this->strategy->persistDataObject($form);
            $this->doctrine->getManager()->flush();

            $eventClassName = $this->contextObjectName;

            $this->eventDispatcher->dispatch(
                sprintf('%s.after_post_flush', $request->get('_route')),
                new $eventClassName($form, $request)
            );

            if ($this->strategy instanceof CrudPostFlushableInterface) {
                return $this->strategy->postFlush($form->getData());
            }
        } else {
            $status = 400;
        }

        return $this->createParamList(['form' => $form->createView(), 'status' => $status]);
    }

    public function putAction(Request $request)
    {
        if (!$request->get('id', false)) {
            throw new InvalidArgumentException('invalid id');
        }

        $form = $this->createForm($request);

        $form->handleRequest($request);

        $status = 200;

        if ($form->isValid()) {
            $this->strategy->persistDataObject($form);
            $this->doctrine->getManager()->flush();

            $eventClassName = $this->contextObjectName;

            $this->eventDispatcher->dispatch(
                sprintf('%s.after_put_flush', $request->get('_route')),
                new $eventClassName($form, $request)
            );

            if ($this->strategy instanceof CrudPostFlushableInterface) {
                $response = $this->strategy->postFlush($form->getData());

                if ($response instanceof Response) {
                    return $response;
                }
            }
        } else {
            $status = 400;
        }

        return $this->createParamList(['form' => $form->createView(), 'status' => $status]);
    }

    public function getAction(Request $request)
    {
        $form = $this->createForm($request);

        return $this->createParamList(['form' => $form->createView()]);
    }

    private function createForm(Request $request): FormInterface
    {
        if ($request->isMethod('PUT')) {
            $object = $this->strategy->createDataObjectForPut($request);
        } elseif ($request->isMethod('POST')) {
            $object = $this->strategy->createDataObjectForPost($request);
        } else {
            $object = $this->strategy->createDataObjectForGet($request);
        }

        $options = [];
        $options['action'] = $request->getUri();

        if ($request->isMethod('GET')) {
            $options['method'] = Request::METHOD_PUT;

            if (preg_match('/_formAction$/', $request->get('_route'))) {
                $options['method'] = Request::METHOD_POST;
                $options['action'] = $this->router
                    ->generate(
                        preg_replace('/_formAction$/', '_postAction', $request->get('_route')),
                        $request->query->all()
                    );
            }
        }

        $form = $this->formFactory
            ->create($this->typeClassName, $object, $options);

        $eventClassname = $this->contextObjectName;
        
        $this->eventDispatcher->dispatch(
            sprintf('%s.after_create_form', $request->get('_route')),
            new $eventClassname($form, $request)
        );

        return $form;
    }
    
    public function getTemplates(): array
    {
        return $this->templates;
    }
}