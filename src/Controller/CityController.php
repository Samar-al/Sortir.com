<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use App\Service\CityLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ville')]
final class CityController extends AbstractController
{

    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'app_city_index', methods: ['GET'])]
    public function index(CityRepository $cityRepository, PaginatorInterface $paginator, Request $request): Response
    {
    
        $search = $request->query->get('search');
        if ($search) {
            $cities = $cityRepository->searchByName($search);
        } else {
            $cities = $cityRepository->findAll();
        }

        // Paginate the results of the query
        $pagination = $paginator->paginate(
            $cities, // The query or query builder to paginate
            $request->query->getInt('page', 1), // Current page number, default is 1
            10 // Limit the number of entries per page
        );

        return $this->render('city/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajouter', name: 'app_city_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository, CityLoaderService $cityLoaderService): Response
    {
        $city = new City();

        $departments = $cityLoaderService->loadDepartments();

        $formCity = $this->createForm(CityType::class, $city);
        $formCity->handleRequest($request);

        if ($formCity->isSubmitted() && $formCity->isValid()) {

            if (empty($city->getZipCode())) {
                $this->addFlash("danger", "Sélectionner une ville");
                return $this->redirectToRoute('app_city_new');
            }

            $cityExist = $cityRepository->findOneBy(['ZipCode' => $city->getZipCode()]);

            if ($cityExist) {
                $this->addFlash("danger", "Ville existe déjà!");
                return $this->redirectToRoute('app_city_new');
            }

            $entityManager->persist($city);
            $entityManager->flush();

            $this->addFlash("success", "Vous avez ajouté une ville avec succès !");
            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('city/new.html.twig', [
            'departments' => $departments,
            'city' => $city,
            'formCity' => $formCity,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/get-cities/{id}', name: 'app_get_cities', methods: ['GET'])]
    public function getCities(string $id, CityLoaderService $cityLoaderService): JsonResponse
    {
        $departmentCode = $id;

        if (!$departmentCode) {
            return new JsonResponse(['error' => 'Department code not provided'], Response::HTTP_BAD_REQUEST);
        }

        $cities = $cityLoaderService->loadCitiesByDepartment($departmentCode);

        return new JsonResponse($cities);
    }

}
