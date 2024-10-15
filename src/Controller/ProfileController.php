<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

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
//        if ($this->isGranted("ROLE_ADMIN")) {
            if ($formProfile->isSubmitted() && $formProfile->isValid()) {

                $plainPassword = $formProfile->get('plainPassword')->getData();

                $hashedPassword = $passwordHasher->hashPassword($profile, $plainPassword);
                $profile->setPassword($hashedPassword);

                $entityManager->persist($profile);
                $entityManager->flush();

                return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
            }
//        }
//        else {
//            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
//        }


        return $this->render('profile/new.html.twig', [
            'profile' => $profile,
            'formProfile' => $formProfile,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Participant $profile): Response
    {
        $form = $this->createForm(ParticipantType::class, $profile);

        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participant $profile, EntityManagerInterface $entityManager): Response
    {
        $formProfileEdit = $this->createForm(ParticipantType::class, $profile, ['is_edit' => true]);
        $formProfileEdit->handleRequest($request);

        if ($formProfileEdit->isSubmitted() && $formProfileEdit->isValid()) {

                    $idProfile = $profile->getId();

                    $entityManager->flush();
//                    $this->addFlash("success","Profil mis à jour!");
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
