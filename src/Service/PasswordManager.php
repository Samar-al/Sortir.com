<?php

namespace App\Service;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Participant;

class PasswordManager
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function validatePasswords(?string $plainPassword, ?string $confirmPassword): ?string
    {
        if (empty($plainPassword) || empty($confirmPassword)) {
            return "Vous devez entrer un mot de passe";
        }

        if ($plainPassword !== $confirmPassword) {
            return "Les mots de passe ne sont pas identiques.";
        }

        return null; // No error
    }

    public function isPasswordValid(Participant $user, string $currentPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $currentPassword);
    }


    public function hashPassword(Participant $profile, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($profile, $plainPassword);
    }
}
