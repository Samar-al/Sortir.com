<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Service\ProfileManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function PHPUnit\Framework\throwException;

#[Route('/profile')]
final class ProfileController extends AbstractController
{


    #[Route(name: 'app_profile_index', methods: ['GET'])]
    public function index(ParticipantRepository $participantRepository): Response
    {
        return $this->render('profile/index.html.twig', [
            'profiles' => $participantRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'app_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $profile = new Participant();
        $formProfile = $this->createForm(ParticipantType::class, $profile);
        $formProfile->handleRequest($request);
        if ($this->isGranted("ROLE_ADMIN")) {
            if ($formProfile->isSubmitted() && $formProfile->isValid()) {

                $plainPassword = $formProfile->get('plainPassword')->getData();
                $confirmPassword = $formProfile->get('confirmPassword')->getData();

                if ($plainPassword !== $confirmPassword) {
                    $this->addFlash('danger', "Les mots de passe ne sont pas identiques.");
                    return $this->redirectToRoute('app_profile_new', [], Response::HTTP_SEE_OTHER);
                }

                $hashedPassword = $passwordHasher->hashPassword($profile, $plainPassword);
                $profile->setPassword($hashedPassword);

                $entityManager->persist($profile);
                $entityManager->flush();

                return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
            }
        }
       else {
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }


        return $this->render('profile/new.html.twig', [
            'profile' => $profile,
            'formProfile' => $formProfile,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Participant $profile): Response
    {
        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
        ]);
    }

  
    #[Route('/modifier/{id}', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, Participant $profile, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        // Check if the user is either the owner of the profile or has ROLE_ADMIN
        if ($user !== $profile && !$this->isGranted('ROLE_ADMIN')) {
            // If not, deny access
            throw new ExceptionAccessDeniedException('Vous n\'avez pas le droit de modifier ce profil!');
        }
        $formProfileEdit = $this->createForm(ParticipantType::class, $profile, ['is_edit' => true]);
        $formProfileEdit->handleRequest($request);

        if ($formProfileEdit->isSubmitted() && $formProfileEdit->isValid()) {

            $idProfile = $profile->getId();

            $currentPassword = $formProfileEdit->get('currentPassword')->getData();
            $plainPassword = $formProfileEdit->get('plainPassword')->getData();
            $confirmPassword = $formProfileEdit->get('confirmPassword')->getData();

            if ($plainPassword !== $confirmPassword)
            {
               $this->addFlash("danger", "Les mots de passe ne sont pas identiques.");
                return $this->redirectToRoute('app_profile_edit', ["id"=>$idProfile], Response::HTTP_SEE_OTHER);
            }

            if (!$passwordHasher->isPasswordValid($this->getUser(), $currentPassword))
            {
                $this->addFlash("danger", "Mot de passe incorrect.");
                return $this->redirectToRoute('app_profile_edit', ["id"=>$idProfile], Response::HTTP_SEE_OTHER);
            }

            $plainPassword = $formProfileEdit->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($profile, $plainPassword);
            $profile->setPassword($hashedPassword);

            $entityManager->flush();
            $this->addFlash("success","Profil mis à jour!");
            return $this->redirectToRoute('app_profile_show', ["id"=>$idProfile], Response::HTTP_SEE_OTHER);

        }

        return $this->render('profile/edit.html.twig', [
            'participant' => $profile,
            'formProfile' => $formProfileEdit,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant, EntityManagerInterface $entityManager): Response
    {

        if ($this->isCsrfTokenValid('delete'.$participant->getId(), $request->getPayload()->getString('_token'))) {
            if ($this->isGranted("ROLE_ADMIN")) {
                $entityManager->remove($participant);
                $entityManager->flush();
                $this->addFlash("success","Profil a bien été supprimé!");
            }

        }

        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
