<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'app_change_locale', methods: ['GET'])]
    public function changeLocale(string $locale, Request $request): Response
    {
        $availableLocales = ['fr', 'en'];

        if (!\in_array($locale, $availableLocales, true)) {
            $locale = 'fr';
        }

        $request->getSession()->set('_locale', $locale);

        $referer = $request->headers->get('referer');

        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}

