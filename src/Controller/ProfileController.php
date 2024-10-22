<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\BaseRepository;
use App\Repository\ParticipantRepository;
use App\Repository\TripRepository;
use App\Service\PasswordManager;
use App\Service\ProfileLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profil')]
final class ProfileController extends AbstractController
{
    private $passwordManager;
    public function __construct(PasswordManager $passwordManager)
    {
        $this->passwordManager = $passwordManager;
       
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route(name: 'app_profile_index', methods: ['GET'])]
    public function index(Request $request, ParticipantRepository $participantRepository, PaginatorInterface $paginator): Response
    {

        // Get the current page number (default is 1)
        $page = $request->query->getInt('page', 1);
        $limit = 10;


        $query = $request->query->get('q', '');

        if ($query) {
            // Assuming the `findByQuery` method in ParticipantRepository searches by lastname, firstname, and username
            $participants = $participantRepository->findByQuery($query);
        } else {
            // Retrieve all participants if no search query is present
            $participants = $participantRepository->findAll();
        }

        // Paginate the results of the query
        $pagination = $paginator->paginate(
            $participants, // The query to paginate
            $page,              // Current page number, passed as a GET parameter
            $limit              // Limit of participants per page
        );

    
        return $this->render('profile/index.html.twig', [
            'pagination' => $pagination,
          
        ]);
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/ajouter', name: 'app_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $profile = new Participant();
        $formProfile = $this->createForm(ParticipantType::class, $profile);
        $formProfile->handleRequest($request);

        // Variable to hold the filename
        $newFilename = null;

        if ($formProfile->isSubmitted() && $formProfile->isValid()) {

            $plainPassword = $formProfile->get('plainPassword')->getData();
            $confirmPassword = $formProfile->get('confirmPassword')->getData();

            // Validate passwords using the PasswordManager service
            $error = $this->passwordManager->validatePasswords($plainPassword, $confirmPassword);

            // Hash the password using the PasswordManager service
            $hashedPassword = $this->passwordManager->hashPassword($profile, $plainPassword);
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

                // Add a success flash message for the picture upload
                $this->addFlash('success', 'Photo de profil téléchargée avec succès !');
            }

            // Success flash message for file upload
          $this->addFlash('success', 'Le profil a été créé avec succès !');
          
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
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
        if (!$this->isGranted("ROLE_ADMIN") && $profile->getMail() == 'anonym@anonym.com') {
            throw new ExceptionAccessDeniedException();
        }

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

  
    #[Route('/{id}/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, Participant $profile, EntityManagerInterface $entityManager): Response
    {     
        /** @var Participant $user */
        $user = $this->getUser();
        // Check if the user is either the owner of the profile or has ROLE_ADMIN
        if ($user !== $profile && !$this->isGranted('ROLE_ADMIN')) {
            // If not, deny access
            throw new ExceptionAccessDeniedException();
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

            $currentPassword = $formProfileEdit->get('currentPassword')->getData();
            $plainPassword = $formProfileEdit->get('plainPassword')->getData();
            $confirmPassword = $formProfileEdit->get('confirmPassword')->getData();


            // Handle password using the PasswordManager service
            if (!empty($plainPassword)) {
                $error = $this->passwordManager->validatePasswords($plainPassword, $confirmPassword);

                if ($error) {
                    $this->addFlash("danger", $error);
                    return $this->redirectToRoute('app_profile_edit', ['id' => $profile->getId()], Response::HTTP_SEE_OTHER);
                }

                if (!$this->passwordManager->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash("danger", "Mot de passe incorrect.");
                    return $this->redirectToRoute('app_profile_edit', ['id' => $profile->getId()], Response::HTTP_SEE_OTHER);
                }

                $hashedPassword = $this->passwordManager->hashPassword($profile, $plainPassword);
                $profile->setPassword($hashedPassword);
            }
           


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

    #[Route('/{id}/supprimer', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant, EntityManagerInterface $entityManager,
                           TripRepository $tripRepository, ParticipantRepository $participantRepository): Response
    {
        /** @var Participant $loggedInUser */
        $loggedInUser = $this->getUser();
         // Block deletion if the participant has the email anonym@anonym.com
        if ($participant->getMail() === 'anonym@anonym.com') {
            $this->addFlash("danger", "Vous ne pouvez pas supprimer cet utilisateur.");
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

       // Check if the user is either ROLE_ADMIN or the owner of the account
        if (!$this->isGranted('ROLE_ADMIN') && $loggedInUser->getId() !== $participant->getId()) {
            throw new ExceptionAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete'.$participant->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash("danger", "CSRF token n'est pas valide!");
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        $tripsExist = $tripRepository->findBy(['organiser' => $participant->getId()]);

        if (!empty($tripsExist)) {
            $anonymParticipant = $participantRepository->findOneBy(['mail' => 'anonym@anonym.com']);

            if (!empty($anonymParticipant)) {
                foreach ($tripsExist as $trip) {
                    $trip->setOrganiser($anonymParticipant);
                }
            }
        }

        $entityManager->remove($participant);
        $entityManager->flush();
        $this->addFlash("success", "Vous avez supprimé un profil avec succès !");
        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);

    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/charger', name: 'app_profile_upload', methods: ['POST'])]
    public function uploadParticipants(Request $request, EntityManagerInterface $entityManager, BaseRepository $baseRepository, ProfileLoaderService $profileLoaderService): Response
    {

        $file = $request->files->get('file');

        if (!$file) {
            $this->addFlash("danger", "Veuillez charger un fichier !");
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($file->getClientOriginalExtension() !== 'csv') {
            $this->addFlash("danger", "Le fichier doit être au format .csv.");
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        $fileName = $profileLoaderService->changeFileName($file);

        try {
            $file->move(
                $this->getParameter('csv_directory'),
                $fileName
            );
        } catch (FileException $e) {
            throw new FileException("Erreur lors du téléchargement du fichier.");
        }

        $filePath = $this->getParameter('csv_directory') . '/' . $fileName;

        $lines = $profileLoaderService->loadData($filePath);

        foreach ($lines as $line) {

            $username = $line[0];
            $firstname = $line[1];
            $lastname = $line[2];
            $phoneNumber = $line[3];
            $mail = $line[4];
            $password = $line[5];

            $base = $baseRepository->findOneBy(['name' => $line[6]]);

            if (!$base) {
                $this->addFlash("danger", "Le site $line[6] n'existe pas.");
                return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
            }

            // Vérifier si l'utilisateur existe déjà dans la base de données
            $existingParticipant = $entityManager->getRepository(Participant::class)->findOneBy(['mail' => $mail]);

            if (!$existingParticipant) {
                // Créer un nouvel utilisateur
                $participant = new Participant();
                $participant->setUsername($username);
                $participant->setMail($mail);
                $participant->setFirstname($firstname);
                $participant->setLastname($lastname);
                $participant->setPassword(password_hash($password, PASSWORD_BCRYPT)); // Hash du mot de passe
                $participant->setPhoneNumber($phoneNumber);
                $participant->setBase($base);
                $participant->setActive(true);

                $participant->setRoles(["ROLE_USER"]);

                // Persister les données dans la base de données
                $entityManager->persist($participant);
            }
        }

//        fclose($handle);
        $entityManager->flush();

        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $this->addFlash("success", "Les utilisateurs ont bien été chargés!");
        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);

    }    

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/participant-action', name: 'app_profile_deactivate', methods: ['POST'])]
    public function deactivateParticipants(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository): Response
    {
         // CSRF token validation for security
        if (!$this->isCsrfTokenValid('participant_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile_index');
        }

        // Get the selected participants from the form
        $selectedParticipants = $request->request->all('participants');
       // dd($selectedParticipants);
        if (empty($selectedParticipants)) {
            $this->addFlash('error', 'No participants selected for deactivation.');
            return $this->redirectToRoute('app_profile_index');
        }

        $action = $request->request->get('action');

        // Deactivate the selected participants
        foreach ($selectedParticipants as $participantId) {
            $participant = $participantRepository->find($participantId);
            if ($action === 'deactivate') {
                $participant->setActive(false); // Deactivate the participant
            } elseif ($action === 'reactivate') {
                $participant->setActive(true); // Reactivate the participant
            }
           
            $entityManager->persist($participant);
            
        }

        $entityManager->flush();

        // Add flash messages based on the action performed
        if ($action === 'deactivate') {
            $this->addFlash('success', 'Les participants sélectionnés ont été désactivés.');
        } elseif ($action === 'reactivate') {
            $this->addFlash('success', 'Les participants sélectionnés ont été réactivés.');
        }
        // Redirect back to the profile index
        return $this->redirectToRoute('app_profile_index');
    

    }
}
