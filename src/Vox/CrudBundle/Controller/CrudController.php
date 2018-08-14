<?php

namespace Vox\CrudBundle\Controller;

use InvalidArgumentException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Vox\CrudBundle\Crud\AddFilterEvent;
use Vox\CrudBundle\Crud\Strategy\CrudExtraValuesInterface;
use Vox\CrudBundle\Crud\Strategy\CrudListableInterface;
use Vox\CrudBundle\Crud\Strategy\CrudPostFlushableInterface;
use Vox\CrudBundle\Crud\Strategy\CrudStrategyInterface;
use Vox\CrudBundle\Crud\Strategy\DefaultCrudStrategy;
use Vox\CrudBundle\Form\CrudFormFactoryInterface;

class CrudController
{
    private $className;

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
     * @var array
     */
    private $listConfigs = [];

    /**
     * @var string
     */
    private $crudName;

    public function __construct(
        string $className,
        string $typeClassName,
        string $contextObjectName,
        RegistryInterface $doctrine,
        CrudFormFactoryInterface $formFactory,
        EventDispatcherInterface $eventDispatcher,
        RouterInterface $router,
        array $templates,
        CrudStrategyInterface $crudStrategy = null,
        array $listConfigs = [],
        string $crudName
    ) {
        $this->className         = $className;
        $this->typeClassName     = $typeClassName;
        $this->contextObjectName = $contextObjectName;
        $this->doctrine          = $doctrine;
        $this->formFactory       = $formFactory;
        $this->eventDispatcher   = $eventDispatcher;
        $this->router            = $router;
        $this->templates         = $templates;
        $this->strategy          = $crudStrategy ?? new DefaultCrudStrategy($className, $doctrine, $eventDispatcher);
        $this->listConfigs       = $listConfigs;
        $this->crudName          = $crudName;
    }

    public function listAction(Request $request)
    {
        if (!$this->strategy instanceof CrudListableInterface) {
            throw new \HttpException('uninplemented method, your strategy must implement CrudListableInterface');
        }

        $results = $this->strategy->getListResults($request)
            ->setLimit($request->get('limit', 30))
            ->setPage($request->get('page', 1))
        ;

        try {
            $eventName = sprintf('%s.%s', CrudStrategyInterface::EVENT_ADD_FILTERS, $request->get('_route'));
            $event = new AddFilterEvent($results->getQueryBuilder(), $request, $this->doctrine->getManager());

            $this->eventDispatcher->dispatch($eventName, $event);
            $this->eventDispatcher->dispatch(CrudStrategyInterface::EVENT_ADD_FILTERS, $event);
        } catch (\TypeError $exception) {
            // prevent errors if returned paginable collection isn't for a query builder
            dump($exception);
        }

        return $this->createParamList([
            'list'  => $results,
            'total' => $results->count(),
            'actions' => $this->listConfigs['actions'],
            'listFields' => $this->listConfigs['list_fields'] ?: $this->getListFields(),
            'listTitle' => $this->listConfigs['title'] ?? 'List data',
            'filterType' => $this->listConfigs['filter_type'] ?? null,
            'hasNewButton' => $this->listConfigs['has_new_button'],
        ]);
    }

    protected function getListFields(): iterable
    {
        $metadata = $this->doctrine->getManager()->getClassMetadata($this->className);

        $fields = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $fields[$fieldName] = $fieldName;
        }

        return $fields;
    }

    private function createParamList(array $params): array
    {
        $params = array_merge($params, [
            'strategy' => $this->strategy,
            'crudName' => $this->crudName
        ]);

        if ($this->strategy instanceof CrudExtraValuesInterface) {
            return array_merge($params, $this->strategy->getExtraViewValues());
        }

        return $params;
    }

    public function receiveDataAction(Request $request)
    {
        if ($request->isMethod('PUT') && !$request->get('id', false)) {
            throw new InvalidArgumentException('invalid id');
        }

        $form = $this->createForm($request);

        $form->handleRequest($request);

        $status = 200;

        if (!$request->isMethod('PATCH') && $form->isValid()) {
            $this->strategy->persistDataObject($form);
            $this->doctrine->getManager()->flush();

            $eventClassName = $this->contextObjectName;

            if ($request->isMethod('POST')) {
                $this->eventDispatcher->dispatch(
                    sprintf('%s.after_post_flush', $request->get('_route')),
                    new $eventClassName($form, $request)
                );
            }

            if ($request->isMethod('PUT')) {
                $this->eventDispatcher->dispatch(
                    sprintf('%s.after_put_flush', $request->get('_route')),
                    new $eventClassName($form, $request)
                );
            }

            if ($this->strategy instanceof CrudPostFlushableInterface) {
                $response = $this->strategy->postFlush($form->getData());

                if ($response instanceof Response) {
                    return $response;
                }
            }
        } else {
            $status = $request->isMethod('PATCH') ? 200 : 400;
        }

        return $this->createParamList(['form' => $form->createView(), 'status' => $status]);
    }

    public function formAction(Request $request)
    {
        $form = $this->createForm($request);

        return ['form' => $form->createView()];
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

        if ($request->isMethod('GET') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $options['method'] = $request->isMethod('GET') ? Request::METHOD_PUT : $request->getMethod();

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
