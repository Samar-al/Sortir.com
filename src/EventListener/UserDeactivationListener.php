<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ParticipantRepository;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class UserDeactivationListener
{
    private $security;
    private $participantRepository;
    private $logoutUrlGenerator;
    private $router;
    private $requestStack;

    public function __construct(Security $security, ParticipantRepository $participantRepository, LogoutUrlGenerator $logoutUrlGenerator, RouterInterface $router, RequestStack $requestStack)
    {
        $this->security = $security;
        $this->participantRepository = $participantRepository;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $user = $this->security->getUser();
        $currentRoute = $this->requestStack->getCurrentRequest()->attributes->get('_route');

        // Do not perform the check if the user is already on the login page
        if ($currentRoute === 'app_login') {
            return;
        }

        if (!$user) {
            return;
        }

        // Check if the user is deactivated
        $participant = $this->participantRepository->findOneBy(['mail' => $user->getUserIdentifier()]);

        if ($participant && !$participant->isActive()) {

            // Programmatically log out the user
            $this->security->logout(false);
           
            // Redirect to the login page with a deactivation message
            $loginUrl = $this->router->generate('app_login', [
                'message' => 'Vous avez été déconnecté, vous êtes un utilisateur inactif. Contactez l\'admin pour plus d\'information.'
            ]);

            $event->setResponse(new RedirectResponse($loginUrl));
        }
    }
}
