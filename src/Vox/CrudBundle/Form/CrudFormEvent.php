<?php

namespace Vox\CrudBundle\Form;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class CrudFormEvent extends Event
{
    /**
     * @var FormInterface
     */
    private $form;
    
    private $data;
    
    public function __construct(FormInterface $form, $data)
    {
        $this->form = $form;
        $this->data = $data;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
