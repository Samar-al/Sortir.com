<?php

namespace App\Controller;


use App\Entity\City;
use App\Entity\Location;
use App\Entity\Trip;
use App\Event\TripUnregistrationEvent;
use App\Form\TripType;
use App\Repository\CityRepository;
use App\Event\TripRegistrationEvent;
use App\Repository\StateRepository;
use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
final class TripController extends AbstractController
{


    #[Route('/', name: 'app_trip_index', methods: ['GET'])]
    public function index(Request $request,TripRepository $tripRepository): Response
    {
        $search = $request->query->get('search');

        if ($search) {
            // Search for trips based on the name
            $trips = $tripRepository->searchByName($search);
        } else {
            // If no search query, return all trips
            $trips = $tripRepository->findAll();
        }

        return $this->render('trip/index.html.twig', [
            'trips' => $trips,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/ajouter', name: 'app_trip_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository, StateRepository $stateRepository): Response
    {

        $trip = new Trip();
        $formTrip = $this->createForm(TripType::class, $trip);
        $formTrip->handleRequest($request);
    
        $cities = $cityRepository->findAll();
    
        if ($formTrip->isSubmitted() && $formTrip->isValid()) {
            $now = new \DateTime();
    
            // Check if dateHourStart is in the future
            if ($trip->getDateHourStart() <= $now) {
                $this->addFlash('warning', 'La date de la sortie doit être dans le futur.');
                return $this->redirectToRoute('app_trip_new');
            }
    
            // Check if dateRegistrationLimit is in the future
            if ($trip->getDateRegistrationLimit() <= $now) {
                $this->addFlash('warning', 'La date limite d\'inscription doit être dans le futur.');
                return $this->redirectToRoute('app_trip_new');
            }
    
            // Check if dateHourStart is after dateRegistrationLimit
            if ($trip->getDateHourStart() <= $trip->getDateRegistrationLimit()) {
                $this->addFlash('warning', 'La date de la sortie doit être après la date limite d\'inscription.');
                return $this->redirectToRoute('app_trip_new');
            }
    
            // Process city
            $cityId = $request->request->get('city');
            $city = $cityRepository->find($cityId);
            if ($city) {
                $trip->getLocation()->setCity($city);
            }
    
            // Check which button was clicked (Publish or Save)
            $action = $request->request->get('action');
            if ($action === 'publish') {
                // Set state to 'open' if "Publier" was clicked
                $openState = $stateRepository->findOneBy(['label' => 'open']);
                if ($openState) {
                    $trip->setState($openState);
                }
            } else {
                // Set state to 'created' if "Enregistrer" was clicked
                $defaultState = $stateRepository->findOneBy(['label' => 'created']);
                if ($defaultState) {
                    $trip->setState($defaultState);
                }
            }
    
            $trip->setOrganiser($this->getUser());
            $entityManager->persist($trip);
            $entityManager->flush();
    
            $this->addFlash('success', 'Vous avez ajouté une sortie avec succès !');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trip/new.html.twig', [
            'trip' => $trip,
            'cities' => $cities,
            'formTrip' => $formTrip->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_trip_show', methods: ['GET'])]
    public function show(Trip $trip): Response
    {
        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
        ]);
    }

    #[Route('/modifier/{id}', name: 'app_trip_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trip $trip, EntityManagerInterface $entityManager, CityRepository $cityRepository, StateRepository $stateRepository): Response
    {
        // Check if the user is the organizer of the trip
        if ($this->getUser() !== $trip->getOrganiser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cette sortie, vous n\'en êtes pas l\'auteur!');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }

        $formTrip = $this->createForm(TripType::class, $trip);
        $formTrip->handleRequest($request);

        $cities = $cityRepository->findAll();

        if ($formTrip->isSubmitted() && $formTrip->isValid()) {
            $now = new \DateTime();

            // Check if dateHourStart is in the future
            if ($trip->getDateHourStart() <= $now) {
                $this->addFlash('warning', 'La date de la sortie doit être dans le futur.');
                return $this->redirectToRoute('app_trip_edit', ['id' => $trip->getId()]);
            }

            // Check if dateRegistrationLimit is in the future
            if ($trip->getDateRegistrationLimit() <= $now) {
                $this->addFlash('warning', 'La date limite d\'inscription doit être dans le futur.');
                return $this->redirectToRoute('app_trip_edit', ['id' => $trip->getId()]);
            }

            // Check if dateHourStart is after dateRegistrationLimit
            if ($trip->getDateHourStart() <= $trip->getDateRegistrationLimit()) {
                $this->addFlash('warning', 'La date de la sortie doit être après la date limite d\'inscription.');
                return $this->redirectToRoute('app_trip_edit', ['id' => $trip->getId()]);
            }

            // Handle city selection
            $cityId = $request->request->get('city');
            $city = $cityRepository->find($cityId);
            if ($city) {
                $trip->getLocation()->setCity($city);
            }

            // Check which button was clicked (Save or Publish)
            $action = $request->request->get('action');
            if ($action === 'publish') {
                // Set state to 'open' if "Publier" was clicked
                $openState = $stateRepository->findOneBy(['label' => 'open']);
                if ($openState) {
                    $trip->setState($openState);
                }
            } else {
                // Set state to 'created' if "Enregistrer" was clicked
                $defaultState = $stateRepository->findOneBy(['label' => 'created']);
                if ($defaultState) {
                    $trip->setState($defaultState);
                }
            }

            // Update the trip details
            $trip->setOrganiser($this->getUser());
            $entityManager->flush();
            
            $this->addFlash('success', 'Vous avez modifié la sortie avec succès !');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }

    return $this->render('trip/edit.html.twig', [
        'trip' => $trip,
        'cities' => $cities,
        'formTrip' => $formTrip->createView(),
    ]);
}

    #[Route('/supprimer/{id}', name: 'app_trip_delete', methods: ['POST'])]
    public function delete(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {

        if($this->getUser()!=$trip->getOrganiser() && !$this->isGranted("ROLE_MODERATOR") ){
            $this->addFlash("danger", "Vous ne pouvez pas supprimer cette sortie, vous n'en êtes pas l'auteur!");
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }

        if (!$this->isCsrfTokenValid('delete'.$trip->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash("danger", "CSRF token n'est pas valide!");
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }

        $entityManager->remove($trip);
        $entityManager->flush();
        $this->addFlash("success", "Vous avez supprimé une sortie avec succès !");
        return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);

    }

   

    #[Route('/{id}/inscription', name: 'app_trip_register', methods: ['POST'])]
    public function registerToTrip(Trip $trip, TripRepository $tripRepository, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher): Response
    {
        $user = $this->getUser();
    
        // Check if the user is already registered for the trip
        if ($trip->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'You are already registered for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Check if the registration deadline has passed
        if ($trip->getDateRegistrationLimit() < new \DateTimeImmutable()) {
            $this->addFlash('warning', 'The registration deadline has passed.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Check if the number of registrations has reached the maximum
        if ($trip->getParticipants()->count() >= $trip->getNumMaxRegistration()) {
            $this->addFlash('warning', 'The trip has reached its maximum number of participants.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Check if the trip is open (assuming "open" is a state id or label)
        if ($trip->getState()->getLabel() !== 'open') {
            $this->addFlash('warning', 'Registration is not allowed for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Register the user for the trip
        $trip->addParticipant($user);
        $entityManager->persist($trip);
        $entityManager->flush();
        // Dispatch the TripRegistrationEvent
        $eventDispatcher->dispatch(new TripRegistrationEvent($trip), TripRegistrationEvent::NAME);
        $this->addFlash('success', 'Vous vous êtes inscrit à la sortie avec succès !');
        return $this->redirectToRoute('app_main_index');
    }

    #[Route('/{id}/desisstement', name: 'app_trip_unregister', methods: ['POST'])]
    public function unregisterToTrip(Trip $trip, TripRepository $tripRepository, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher): Response
    {
        $user = $this->getUser();

        // Check if the user is already registered for the trip
        if (!$trip->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'You are not registered for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
       
        if ($trip->getState()->getLabel() !== 'open' && $trip->getState()->getLabel() !== 'closed') {
            $this->addFlash('warning', 'Unregistration is not allowed for this trip.');
            return $this->redirectToRoute('app_main_index');
        }

         // Register the user for the trip
         $trip->removeParticipant($user);
         $entityManager->persist($trip);
         $entityManager->flush();

         // Dispatch the TripUnregistrationEvent
         $eventDispatcher->dispatch(new TripUnregistrationEvent($trip), TripUnregistrationEvent::NAME);
         $this->addFlash('success', 'Vous êtes bien désinscrit de la sortie');
         return $this->redirectToRoute('app_main_index');

    }

    #[Route('/{id}/publier', name: 'app_trip_publish', methods: ['GET'])]
    public function publishTrip(Trip $trip, StateRepository $stateRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        // Check if the user is the organizer
        if ($trip->getOrganiser() !== $user) {
            $this->addFlash('warning', 'Vous n\'êtes pas l\'organisateur de cette sortie');
            return $this->redirectToRoute('app_main_index');
        }

        // Check if the trip is still in the 'created' state
        if ($trip->getState()->getLabel() !== 'created') {
            $this->addFlash('warning', 'Vous ne pouvez pas publier cette sortie');
            return $this->redirectToRoute('app_main_index');
        }

        // Check if the trip's dateHourStart or dateRegistrationLimit is in the past
        $now = new \DateTime();
        if ($trip->getDateHourStart() <= $now || $trip->getDateRegistrationLimit() <= $now) {
            $this->addFlash('warning', 'Vous ne pouvez pas publier une sortie dont la date est déjà passée ou pour laquelle la limite d\'inscription est dépassée.');
            return $this->redirectToRoute('app_main_index');
        }

        // Set the trip state to 'open'
        $openState = $stateRepository->findOneBy(['label' => 'open']);
        $trip->setState($openState);
        
        // Persist changes to the database
        $entityManager->flush();

        $this->addFlash('success', 'Votre sortie a été publiée avec succès');
        return $this->redirectToRoute('app_main_index');

    }

    #[Route('/{id}/annuler', name: 'app_trip_cancel', methods: ['GET', 'POST'])]
    public function cancelTrip(Trip $trip, Request $request, StateRepository $stateRepository, EntityManagerInterface $entityManager): Response
    {
        // Get the current user
        $user = $this->getUser();

        // Check if the user is the organizer or an admin
        if ($trip->getOrganiser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à annuler cette sortie.');
            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        // Handle the POST request (form submission)
        if ($request->isMethod('POST')) {
            // Get the cancellation reason from the form
            $cancellationReason = $request->request->get('cancellation_reason');

            // Check if the reason is provided
            if (empty($cancellationReason)) {
                $this->addFlash('warning', 'Veuillez fournir une raison pour l\'annulation.');
                return $this->redirectToRoute('app_trip_cancel', ['id' => $trip->getId()]);
            }

            // Find the "cancelled" state from the State repository
            $cancelledState = $stateRepository->findOneBy(['label' => 'cancelled']);

            // Check if the cancelled state exists
            if (!$cancelledState) {
                $this->addFlash('danger', 'L\'état annulé est introuvable.');
                return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
            }

            // Update the trip's state and cancellation reason
            $trip->setState($cancelledState);
            $trip->setReasonCancel($cancellationReason);

            // Persist the changes to the database
            $entityManager->persist($trip);
            $entityManager->flush();

            // Add a success flash message
            $this->addFlash('success', 'La sortie a été annulée avec succès.');

            // Redirect to the trip listing or trip details
            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }
        return $this->render('trip/cancel.html.twig', [
            'trip' => $trip,
        ]);
    }
    

}
