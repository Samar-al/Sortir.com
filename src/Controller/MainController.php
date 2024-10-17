<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Form\TripSearchType;
use App\Repository\BaseRepository;
use App\Repository\TripRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main_index', methods:["GET", "POST"])]
    public function index(TripRepository $tripRepository, BaseRepository $baseRepository, Request $request): Response
    {
        // Retrieve filter criteria
        $selectedBase = $request->request->get('selectBase');
        $searchNameTrip = $request->request->get('searchNameTrip');
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');
        $meOrganizer = $request->request->get('me-orga') !== null;
        $meRegistered = $request->request->get('me-registered') !== null;
        $meNotRegistered = $request->request->get('me-not-registered') !== null;
        $passedTrip = $request->request->get('passed-trip') !== null;

        // Fetch filtered trips
        $trips = $tripRepository->findFilteredTrips(
            $selectedBase,
            $searchNameTrip,
            $startDate,
            $endDate,
            $this->getUser(),
            $meOrganizer,
            $meRegistered,
            $meNotRegistered,
            $passedTrip
        );

        // Handle AJAX request
        if ($request->isXmlHttpRequest()) {
            // Render only the tbody part
            $html = $this->renderView('main/_trips_tbody.html.twig', [
                'trips' => $trips,
            ]);

            return $this->json(['html' => $html]);
        }
    
        return $this->render('main/index.html.twig', [
            'trips' => $tripRepository->findBy(['isArchived' => false]),
            'bases' => $baseRepository->findAll(),  // Assuming bases are available via a repository method
        ]);
    }
}
