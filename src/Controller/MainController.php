<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\BaseRepository;
use App\Repository\TripRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main_index', methods: ["GET", "POST"])]
    public function index(TripRepository $tripRepository, BaseRepository $baseRepository, Request $request, PaginatorInterface $paginator): Response
    {
        if (empty($this->getUser())) {
            return $this->redirectToRoute('app_login');
        }

        // Retrieve filter criteria
        $filterCriteria = $this->getFilterCriteria($request);

        // Fetch filtered trips
        $trips = $tripRepository->findFilteredTrips(
            $filterCriteria['selectedBase'],
            $filterCriteria['searchNameTrip'],
            $filterCriteria['startDate'],
            $filterCriteria['endDate'],
            $this->getUser(),
            $filterCriteria['meOrganizer'],
            $filterCriteria['meRegistered'],
            $filterCriteria['meNotRegistered'],
            $filterCriteria['passedTrip']
        );

        // Paginate the results
        $pagination = $this->paginateResults($trips, $request, $paginator);

        // Handle AJAX request
        if ($request->isXmlHttpRequest()) {
            return $this->handleAjaxResponse($pagination);
        }

        // Handle profile picture logic
        $userProfilePicture = $this->getUserProfilePicture($this->getUser());

        return $this->render('main/index.html.twig', [
            'pagination' => $this->paginateResults($tripRepository->findBy(['isArchived' => false]), $request, $paginator),
            'bases' => $baseRepository->findAll(),
            'profilePicture' => $userProfilePicture
        ]);
    }

    private function getFilterCriteria(Request $request): array
    {
        return [
            'selectedBase' => $request->request->get('selectBase'),
            'searchNameTrip' => $request->request->get('searchNameTrip'),
            'startDate' => $request->request->get('startDate'),
            'endDate' => $request->request->get('endDate'),
            'meOrganizer' => $request->request->get('me-orga') !== null,
            'meRegistered' => $request->request->get('me-registered') !== null,
            'meNotRegistered' => $request->request->get('me-not-registered') !== null,
            'passedTrip' => $request->request->get('passed-trip') !== null
        ];
    }

    private function paginateResults($trips, Request $request, PaginatorInterface $paginator): PaginationInterface
    {
        return $paginator->paginate(
            $trips, 
            $request->query->getInt('page', 1), 
            10
        );
    }

    private function handleAjaxResponse($pagination): JsonResponse
    {
        $html = $this->renderView('main/_trips_tbody.html.twig', [
            'pagination' => $pagination,
        ]);

        return $this->json(['html' => $html]);
    }

    private function getUserProfilePicture(Participant $user): ?string
    {
        $userId = $user->getId();
        $profilePicturesDir = $this->getParameter('profile_pictures_directory');
        $pictureFilename = 'profilepic' . $userId;
        $fullPathJpg = $profilePicturesDir . '/' . $pictureFilename . '.jpg';
        $fullPathPng = $profilePicturesDir . '/' . $pictureFilename . '.png';

        if (file_exists($fullPathJpg)) {
            return $pictureFilename . '.jpg';
        } elseif (file_exists($fullPathPng)) {
            return $pictureFilename . '.png';
        }

        return null;
    }
}
