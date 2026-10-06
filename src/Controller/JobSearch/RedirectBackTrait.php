<?php

namespace App\Controller\JobSearch;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Les actions rapides (Postuler, Relancer...) sont déclenchées depuis la liste ou le tableau de bord :
 * on renvoie l'utilisateur là où il était.
 */
trait RedirectBackTrait
{
    private function redirectBack(AdminContext $context): RedirectResponse
    {
        $referer = $context->getRequest()->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirect($this->container->get(AdminUrlGeneratorInterface::class)
            ->setController(static::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }
}
