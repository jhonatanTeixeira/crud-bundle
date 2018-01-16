<?php

namespace Vox\CrudBundle\Crud\Strategy;

use ReflectionClass;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultCrudStrategy implements CrudStrategyInterface
{
    /**
     * @var string
     */
    private $className;
    
    /**
     * @var RegistryInterface
     */
    private $doctrine;
    
    public function __construct(string $className, RegistryInterface $doctrine)
    {
        $this->className = $className;
        $this->doctrine  = $doctrine;
    }
    
    public function createDataObjectForGet(Request $request)
    {
        if (!$request->attributes->has('id')) {
            throw new BadRequestHttpException('no id on request');
        }
        
        $object = $this->doctrine
            ->getRepository($this->className)
            ->find($request->get('id'));

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
}
