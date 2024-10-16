<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        // Variable to hold the filename
        $newFilename = null;

        if ($this->isGranted("ROLE_ADMIN")) {
            if ($formProfile->isSubmitted() && $formProfile->isValid()) {

                $plainPassword = $formProfile->get('plainPassword')->getData();

                $hashedPassword = $passwordHasher->hashPassword($profile, $plainPassword);
                $profile->setPassword($hashedPassword);

                // Persist the profile first to get the ID
                $entityManager->persist($profile);
                $entityManager->flush(); // This will generate the ID for the participant

                $photoFile = $formProfile->get('photo')->getData();
                if ($photoFile) {
                    // Guess the extension dynamically
                    $extension = $photoFile->guessExtension(); // e.g., 'jpg', 'png'
                
                    // Generate a new filename using 'profilepic' + participant ID + the guessed extension
                    $newFilename = 'profilepic' . $profile->getId() . '.' . $extension;
                
                    // Move the file to the directory where profile images are stored
                    $photoFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                
                    // Store the filename in the session or pass it to the template
                }
                 
                // Success flash message for file upload
                $this->addFlash('success', 'Le profil a été créé avec succès et la photo a été téléchargée.');
                return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
            }
        }
       else {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à créer des participants');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }


        return $this->render('profile/new.html.twig', [
            'profile' => $profile,
            'formProfile' => $formProfile,
            'profilePicture' => $newFilename,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Participant $profile): Response
    {
        $profilePicturesDir = $this->getParameter('profile_pictures_directory');
        $profilePicPath = $profilePicturesDir . '/profilepic' . $profile->getId();

        // Use glob to find the file with any extension (e.g., .jpg, .png, .jpeg)
        $profilePicFiles = glob($profilePicPath . '.*'); // Looks for any extension

        // Set the profile picture variable based on the found file
        if (!empty($profilePicFiles)) {
            $profilePicture = basename($profilePicFiles[0]); // Retrieves the full filename with extension
        } else {
            $profilePicture = null; // No picture found, use default
        }

        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
            'profilePicture' => $profilePicture, // Pass the profile picture to the template
        ]);
    }

  
    #[Route('/edit/{id}', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, Participant $profile, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // Check if the user is either the owner of the profile or has ROLE_ADMIN
        if ($user !== $profile && !$this->isGranted('ROLE_ADMIN')) {
            // If not, deny access
            throw new ExceptionAccessDeniedException('You do not have permission to edit this profile.');
        }
        $formProfileEdit = $this->createForm(ParticipantType::class, $profile, ['is_edit' => true]);
        $formProfileEdit->handleRequest($request);

        // Variable to hold the filename
        $newFilename = null;

        if ($formProfileEdit->isSubmitted() && $formProfileEdit->isValid()) {
            // Handle the photo upload
            $photoFile = $formProfileEdit->get('photo')->getData();
            if ($photoFile) {
                // Guess the extension dynamically
                $extension = $photoFile->guessExtension();

                // Generate a new filename using 'profilepic' + participant ID + the guessed extension
                $newFilename = 'profilepic' . $profile->getId() . '.' . $extension;

                // Move the file to the directory where profile images are stored
                $photoFile->move(
                    $this->getParameter('profile_pictures_directory'),
                    $newFilename
                );
            }

            $idProfile = $profile->getId();

            $entityManager->flush();
            $this->addFlash("success","Profil mis à jour!");
            return $this->redirectToRoute('app_profile_show', ["id"=>$idProfile], Response::HTTP_SEE_OTHER);

        }

        return $this->render('profile/edit.html.twig', [
            'participant' => $profile,
            'formProfile' => $formProfileEdit,
            'profilePicture' => $newFilename,
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
