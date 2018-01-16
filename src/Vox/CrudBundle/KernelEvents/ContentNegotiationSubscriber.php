<?php

namespace Vox\CrudBundle\KernelEvents;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContentNegotiationSubscriber implements EventSubscriberInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @var Response
     */
    private $response;
    
    private $template;
    
    /**
     * @var \Twig_Environment
     */
    private $twig;
    
    public function __construct(SerializerInterface $serializer, \Twig_Environment $twig)
    {
        $this->serializer = $serializer;
        $this->twig       = $twig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['setTemplate']
            ],
            KernelEvents::VIEW    => [
                ['renderView', 11],
                ['serialize', 10],
                ['setResponse', 1],
            ],
            KernelEvents::REQUEST => [
                'deserialize',
            ]
        ];
    }
    
    public function setTemplate(\Symfony\Component\HttpKernel\Event\FilterControllerEvent $event)
    {
        list($controller, $action) = $event->getController();
        
        if (!$controller instanceof \Vox\CrudBundle\Controller\CrudController) {
            return;
        }
        
        $templates = $controller->getTemplates();
        
        if ($action == 'post' || $action == 'put') {
            $action = 'form';
        }
        
        $actions = [
            'post' => 'form',
            'put'  => 'form',
            'get'  => 'form',
            'form' => 'form',
        ];
        
        $action = $actions[str_replace('Action', '', $action)];
        
        $this->template = $templates["{$action}Template"] ?? null;
    }
    
    public function renderView(GetResponseForControllerResultEvent $event)
    {
        if (!$event->getRequest()->getRequestFormat() == 'html' || !$this->template) {
            return;
        }

        $result = $event->getControllerResult();

        $status = $result['status'] ?? 200;

        $data = $this->twig->render($this->template, $event->getControllerResult());
        
        $event->setResponse(new Response($data, $status));
    }
    
    public function serialize(GetResponseForControllerResultEvent $event)
    {
        if (in_array($event->getRequest()->getRequestFormat(), ['json', 'xml'])) {
            $result = $event->getControllerResult();

            if (!is_array($result) && !is_object($result)) {
                return;
            }
            
            $this->response = new Response(
                $this->serializer->serialize($result, $event->getRequest()->getRequestFormat())
            );
        }
    }
    
    public function setResponse(GetResponseForControllerResultEvent $event)
    {
        if ($this->response) {
            $event->setResponse($this->response);
        }
        
        $this->response = null;
    }

    public function deserialize(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (in_array($request->getRequestFormat(), ['json']) && $request->getMethod() == 'POST') {
            $content = json_decode($request->getContent(), true);
            
            $request->request->add($content);
        }
    }
}
