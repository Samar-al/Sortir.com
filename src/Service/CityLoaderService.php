<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CityLoaderService
{

    public function loadDepartments() {

        // URL de l'API pour récupérer les communes par département
        $url = "https://geo.api.gouv.fr/departements";
        $response = file_get_contents($url);

        return json_decode($response, true);
    }


    public function loadCitiesByDepartment(string $departmentCode) {
        // URL de l'API pour récupérer les communes par département
        $url = "https://geo.api.gouv.fr/departements/" . $departmentCode . "/communes";

        try {
            // Appel à l'API
            $response = file_get_contents($url);

            // Si la réponse est vide ou non valide, renvoyer un tableau vide
            if ($response === false) {
                throw new \Exception('Erreur lors de la récupération des villes.');
            }

            // Décodage de la réponse JSON
            $cities = json_decode($response, true);

            // Vérifie que le format est correct
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erreur de décodage du JSON.');
            }

            return $cities;

        } catch (\Exception $e) {
            // En cas d'erreur, renvoyer un tableau vide ou un message d'erreur dans le log
            // error_log($e->getMessage()); // Facultatif pour loguer l'erreur
            return [];
        }
    }

}