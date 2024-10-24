<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Event\TripUnregistrationEvent;
use App\Form\TripType;
use App\Repository\CityRepository;
use App\Event\TripRegistrationEvent;
use App\Repository\StateRepository;
use App\Repository\TripRepository;
use App\Service\TripCancellationService;
use App\Service\TripCityService;
use App\Service\TripPublishService;
use App\Service\TripRegistrationService;
use App\Service\TripStateService;
use App\Service\TripUnregisterService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
final class TripController extends AbstractController
{


    #[Route('/', name: 'app_trip_index', methods: ['GET'])]
    public function index(Request $request,TripRepository $tripRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');

        if ($search) {
            // Search for trips based on the name
            $trips = $tripRepository->searchByName($search);
        } else {
            // If no search query, return all trips
            $trips = $tripRepository->findAll();
        }
        // Paginate the results (trips can be an array)
        $pagination = $paginator->paginate(
            $trips, // The array or query to paginate
            $request->query->getInt('page', 1), // Current page number
            10 // Limit the number of entries per page to 10
        );

        return $this->render('trip/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/ajouter', name: 'app_trip_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TripCityService $tripCityService, TripStateService $tripStateService): Response
    {
        $trip = new Trip();
        $formTrip = $this->createForm(TripType::class, $trip);
        $formTrip->handleRequest($request);
    
        if ($formTrip->isSubmitted() && $formTrip->isValid()) {
            $tripCityService->updateCity($trip, $request->request->get('city'));
    
            $tripStateService->updateStateBasedOnAction($trip, $request->request->get('action'));
    
            $trip->setOrganiser($this->getUser());
            $entityManager->persist($trip);
            $entityManager->flush();
    
            $this->addFlash('success', 'Vous avez ajouté une sortie avec succès !');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('trip/new.html.twig', [
            'trip' => $trip,
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

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/modifier', name: 'app_trip_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Trip $trip,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        TripStateService $tripStateService,
        TripCityService $tripCityService // New service for city selection
    ): Response
    {
        // Block edition if the trip state is not 'created'
        if ($tripStateService->cannotEdit($trip)) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cette sortie car elle n\'est plus à l\'état "créée".');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }
    
        // Check if the user is the organizer of the trip
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $trip->getOrganiser()) {
            throw new ExceptionAccessDeniedException();
        }
    
        $formTrip = $this->createForm(TripType::class, $trip);
        $formTrip->handleRequest($request);
    
        $cities = $cityRepository->findAll();
    
        if ($formTrip->isSubmitted() && $formTrip->isValid()) {
            // Use the city service to handle city selection
            $tripCityService->updateCity($trip, $request->request->get('city'));
    
            // Check which button was clicked (Save or Publish)
            $tripStateService->updateStateBasedOnAction($trip, $request->request->get('action'));
    
            // Update the trip details
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

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/supprimer', name: 'app_trip_delete', methods: ['POST'])]
    public function delete(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {
        $referer = $request->headers->get('referer');

        if($this->getUser()!=$trip->getOrganiser() && !$this->isGranted("ROLE_ADMIN") ){
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

        if (!$referer || str_contains($referer, `sortie/\d+`) ) {
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }
         // Redirect back to the referer
        return $this->redirect($referer);

    }


    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/inscription', name: 'app_trip_register', methods: ['POST'])]
    public function registerToTrip(Trip $trip, TripRegistrationService $registrationService, EventDispatcherInterface $eventDispatcher): Response
    {
        $user = $this->getUser();

        if (!$registrationService->canRegister($trip, $user)) {
            $this->addFlash('warning', 'You cannot register for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
    
        $registrationService->register($trip, $user);
        
        // Dispatch the TripRegistrationEvent
        $eventDispatcher->dispatch(new TripRegistrationEvent($trip), TripRegistrationEvent::NAME);
        $this->addFlash('success', 'Vous vous êtes inscrit à la sortie avec succès !');
        return $this->redirectToRoute('app_main_index');
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/desistement', name: 'app_trip_unregister', methods: ['POST'])]
    public function unregisterToTrip(
        Trip $trip, 
        TripUnregisterService $tripUnregisterService
    ): Response
    {
        $user = $this->getUser();
        
        // Call the service and get the result
        $result = $tripUnregisterService->unregister($trip, $user);
    
        // Add flash message
        $this->addFlash($result['type'], $result['message']);
    
        // Redirect to the appropriate route
        return $this->redirectToRoute($result['redirect']);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/publier', name: 'app_trip_publish', methods: ['GET'])]
    public function publishTrip(Trip $trip, StateRepository $stateRepository, TripPublishService $tripPublishService): Response
    {
        $user = $this->getUser();
    
       
        $result = $tripPublishService->handleTripPublication($trip, $user, $stateRepository);
    
        
        $this->addFlash($result['type'], $result['message']);
    
       
        return $result['redirect'];
    }
    

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/annuler', name: 'app_trip_cancel', methods: ['GET', 'POST'])]
    public function cancelTrip(Trip $trip, Request $request, TripCancellationService $tripCancellationService): Response
    {
        $user = $this->getUser();

        // Check if the user is authorized to cancel
        $canCancelMessage = $tripCancellationService->canCancel($trip, $user);
        if ($canCancelMessage !== '') {
            $this->addFlash('danger', $canCancelMessage);
            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        
        if ($request->isMethod('POST')) {
            $cancellationReason = $request->request->get('cancellation_reason');

           
            $cancelMessage = $tripCancellationService->cancel($trip, $cancellationReason);
            if ($cancelMessage === 'La sortie a été annulée avec succès.') {
                $this->addFlash('success', $cancelMessage);
                return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
            }

        
            $this->addFlash('warning', $cancelMessage);
            return $this->redirectToRoute('app_trip_cancel', ['id' => $trip->getId()]);
        }

        return $this->render('trip/cancel.html.twig', [
            'trip' => $trip,
        ]);
    }


}
