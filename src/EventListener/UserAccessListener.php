<?php
// src/EventListener/UserAccessListener.php
namespace App\EventListener;


use App\Repository\ParticipantRepository;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAccessListener
{
    private $participantRepository;

    // Inject the repository via the constructor
    public function __construct(ParticipantRepository $participantRepository)
    {
        $this->participantRepository = $participantRepository;
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        // Fetch the participant using the injected repository
        $participant = $this->participantRepository->findOneBy(['mail' => $user->getUserIdentifier()]);
   
        if ($user instanceof UserInterface && !$participant->isActive()) {
            // Throwing an exception to prevent the login
            throw new CustomUserMessageAuthenticationException('Votre compte à été désactivé, contactez l\'administarteur pour plus d\'information');
        }
    }
}

