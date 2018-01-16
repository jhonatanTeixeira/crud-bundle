# Crud Bundle

## Configure Routes:

```yaml
vox_crud:
    routes:
        firstStep:
            type: Presentation\Form\FirstStepType                   # the symfony form type to use
            class: Domain\Model\ProccessData                        # the object to be persisted, usualy a entity
            contextObject: Vox\CrudBundle\Form\CrudFormEvent        # a context object for the events to be dispatched
            strategy: Infrastructure\CrudStrategy\FirstStepStrategy # the persistence strategy class, must implement the strategy interface
            operations: ['get', 'put']                              # operations
            formTemplate: 'first-step/form.html.twig'               # form twig template

        secondStep:
            type: Presentation\Form\FirstStepType
            class: Domain\Model\ProccessData
            contextObject: Vox\CrudBundle\Form\CrudFormEvent
            strategy: Infrastructure\CrudStrategy\FirstStepStrategy
            operations: ['get', 'put']
            formTemplate: 'first-step/form.html.twig'
```
## Custom Persistence Strategy

Register this class using the strategy option on your route configuration

```php
namespace Infrastructure\CrudStrategy;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Vox\CrudBundle\Crud\Strategy\CrudPostFlushableInterface;
use Vox\CrudBundle\Crud\Strategy\CrudStrategyInterface;

class FirstStepStrategy implements CrudStrategyInterface, CrudPostFlushableInterface
{

    public function persistDataObject(FormInterface $form)
    {
        //persist data using your database mechanism
    }

    public function createDataObjectForPost(Request $request)
    {
        //create the data object for the form when its a post route
    }

    public function createDataObjectForPut(Request $request)
    {
        //create the data object for the form when its a put route
    }

    public function createDataObjectForGet(Request $request)
    {
        //create the data object for the form when its a get route
    }

    public function postFlush($object)
    {
        //do something after the flushing of doctrine or your database mechanism
    }
}
```

## Events

This bundle exposes some events to help manipulate the forms