<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use App\Service\CityLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function PHPUnit\Framework\isEmpty;

#[Route('/ville')]
final class CityController extends AbstractController
{

    #[Route(name: 'app_city_index', methods: ['GET'])]
    public function index(CityRepository $cityRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        if ($search) {
            $cities = $cityRepository->searchByName($search);
        } else {
            $cities = $cityRepository->findAll();
        }
        return $this->render('city/index.html.twig', [
            'cities' => $cities,
        ]);
    }

    #[Route('/ajouter', name: 'app_city_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository, CityLoaderService $cityLoaderService): Response
    {
        $city = new City();

//        $departments = $this->getDepartments($request);

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

//    private function getCitiesByDepartment(string $departmentCode): array
//    {
//        // URL de l'API pour récupérer les communes par département
//        $url = "https://geo.api.gouv.fr/departements/" . $departmentCode . "/communes";
//
//        try {
//            // Appel à l'API
//            $response = file_get_contents($url);
//
//            // Si la réponse est vide ou non valide, renvoyer un tableau vide
//            if ($response === false) {
//                throw new \Exception('Erreur lors de la récupération des villes.');
//            }
//
//            // Décodage de la réponse JSON
//            $cities = json_decode($response, true);
//
//            // Vérifie que le format est correct
//            if (json_last_error() !== JSON_ERROR_NONE) {
//                throw new \Exception('Erreur de décodage du JSON.');
//            }
//
//            return $cities;
//
//        } catch (\Exception $e) {
//            // En cas d'erreur, renvoyer un tableau vide ou un message d'erreur dans le log
//            // error_log($e->getMessage()); // Facultatif pour loguer l'erreur
//            return [];
//        }
//    }

//    private function getDepartments()
//    {
//        // URL de l'API pour récupérer les communes par département
//        $url = "https://geo.api.gouv.fr/departements";
//        $response = file_get_contents($url);
//
//        return json_decode($response, true);
//    }

}
