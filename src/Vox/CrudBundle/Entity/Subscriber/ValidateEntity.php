<?php

namespace Vox\CrudBundle\Entity\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateEntity implements EventSubscriber
{
    /**
     * @var ValidatorInterface
     */
    private $validator;
    
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
    
    public function getSubscribedEvents(): array
    {
        return ['onFlush'];
    }
    
    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        $entities = array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates());
        
        foreach ($entities as $entity) {
            $errors = $this->validator->validate($entity);
            
            if ($errors->count() > 0) {
                throw new ValidatorException((string) $errors);
            }
        }
    }
}
