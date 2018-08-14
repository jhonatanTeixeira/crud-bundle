<?php

namespace Vox\CrudBundle\Crud\Strategy;

use ReflectionClass;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultCrudStrategy implements CrudStrategyInterface, CrudListableInterface
{
    use DefaultListTrait;

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

    public function __construct(string $className, RegistryInterface $doctrine, EventDispatcherInterface $dispatcher)
    {
        $this->className       = $className;
        $this->doctrine        = $doctrine;
        $this->eventDispatcher = $dispatcher;
    }

    public function createDataObjectForGet(Request $request)
    {
        if ($request->attributes->has('id')) {
            $object = $this->doctrine
                ->getRepository($this->className)
                ->find($request->get('id'));
        } else {
            $object = $this->createDataObjectForPost($request);
        }

        if (!$object) {
            throw new NotFoundHttpException('object not found');
        }

        return $object;
    }

    public function createDataObjectForPost(Request $request)
    {
        return (new ReflectionClass($this->className))->newInstance();
    }

    public function createDataObjectForPut(Request $request)
    {
        return $this->createDataObjectForGet($request);
    }

    public function persistDataObject(FormInterface $form)
    {
        $object = $form->getData();

        $this->doctrine->getManager()->persist($object);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getDoctrine(): RegistryInterface
    {
        return $this->doctrine;
    }
}
