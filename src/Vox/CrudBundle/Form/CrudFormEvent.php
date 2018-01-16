<?php

namespace Vox\CrudBundle\Form;

use Symfony\Component\Form\FormInterface;
use Vox\PipelineBundle\Pipeline\PipelineContext;

class CrudFormEvent extends PipelineContext
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
