<?php

namespace Vox\CrudBundle\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;

class FormPatchSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => ['prepareForPatch']
        ];
    }

    public function prepareForPatch(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($form->getConfig()->getMethod() == Request::METHOD_PATCH) {
            $event->setData(array_filter($data, function ($value) {
                return '' !== $value || null !== $value;
            }));
        }
    }
}