<?php
/**
 * Created by PhpStorm.
 * User: julio
 * Date: 20/11/17
 * Time: 09:24
 */

namespace Vox\CrudBundle\Crud\Strategy;


interface CrudPostFlushableInterface
{
    public function postFlush($object);
}