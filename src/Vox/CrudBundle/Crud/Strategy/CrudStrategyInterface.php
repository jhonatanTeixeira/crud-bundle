<?php

namespace Vox\CrudBundle\Crud\Strategy;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface CrudStrategyInterface
{
    const EVENT_ADD_FILTERS = 'crud.event.add_list_filter';

    public function persistDataObject(FormInterface $form);
    
    public function createDataObjectForPost(Request $request);
    
    public function createDataObjectForPut(Request $request);
    
    public function createDataObjectForGet(Request $request);
}
