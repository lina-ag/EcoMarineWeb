<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{

    private const SESSION_KEY = '_locale';
    private const ALLOWED_LOCALES = ['fr', 'en'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->query->get('_locale');

        if (\is_string($locale) && \in_array($locale, self::ALLOWED_LOCALES, true)) {
            $request->setLocale($locale);

            if ($request->hasSession()) {
                $request->getSession()->set(self::SESSION_KEY, $locale);
            }

            return;
        }

        if ($request->hasPreviousSession()) {
            $sessionLocale = $request->getSession()->get(self::SESSION_KEY);

            if (\is_string($sessionLocale) && \in_array($sessionLocale, self::ALLOWED_LOCALES, true)) {

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->hasSession()) {
            $sessionLocale = $request->getSession()->get('_locale');

            if ($sessionLocale) {

                $request->setLocale($sessionLocale);
            }
        }
    }

}


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}


