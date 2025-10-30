<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RefreshFromCookieSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 33]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $req = $event->getRequest();
        if ($req->getPathInfo() !== '/api/token/refresh' || $req->getMethod() !== 'POST') return;

        if ($token = $req->cookies->get('refresh_token')) {
            $data = json_decode($req->getContent() ?: '{}', true);
            $data['refresh_token'] = $token;
            $req->initialize(
                $req->query->all(),
                $data,
                $req->attributes->all(),
                $req->cookies->all(),
                $req->files->all(),
                $req->server->all(),
                json_encode($data)
            );
        }
    }
}
