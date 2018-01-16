<?php

namespace Vox\CrudBundle\Form;

use Symfony\Component\Form\FormInterface;

interface CrudFormFactoryInterface
{
    public function create($type = 'Symfony\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = array()): FormInterface;
}
