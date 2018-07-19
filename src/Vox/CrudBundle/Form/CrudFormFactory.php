<?php

namespace Vox\CrudBundle\Form;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;

class CrudFormFactory extends FormFactory implements CrudFormFactoryInterface
{
    const EVENT_PRE_BUILD = 'crud.form.pre_build';
    
    const EVENT_POST_BUILD = 'crud.form.post_build';
    
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    
    public function __construct(FormRegistryInterface $registry, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($registry);
        
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function create($type = 'Symfony\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = []): FormInterface
    {
        $form = parent::create($type, $data, $options);
        
        $formEvent = new FormEvent($form, $data);
        
        $this->eventDispatcher->dispatch(self::EVENT_POST_BUILD, $formEvent);
        $this->eventDispatcher->dispatch(sprintf('%s.%s', self::EVENT_POST_BUILD, $type), $formEvent);
        
        return $form;
    }
    
    public function createNamedBuilder($name, $type = 'Symfony\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = array())
    {
        $builder = parent::createNamedBuilder($name, $type, $data, $options);
        
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $formEvent) use ($type) {
            $this->eventDispatcher->dispatch(FormEvents::PRE_SUBMIT, $formEvent);
            $this->eventDispatcher->dispatch('crud.form.pre_submit.' . $type, $formEvent);
        });
        
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($type) {
            $this->eventDispatcher->dispatch(FormEvents::POST_SUBMIT, $event);
            $this->eventDispatcher->dispatch('crud.form.post_submit.' . $type, $event);
        });
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($type) {
            $this->eventDispatcher->dispatch(FormEvents::PRE_SET_DATA, $event);
            $this->eventDispatcher->dispatch('crud.form.pre_set_data.' . $type, $event);
        });
        
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($type) {
            $this->eventDispatcher->dispatch(FormEvents::POST_SET_DATA, $event);
            $this->eventDispatcher->dispatch('crud.form.post_set_data.' . $type, $event);
        });
        
        return $builder;
    }
}
