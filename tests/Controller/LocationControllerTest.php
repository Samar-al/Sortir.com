<?php

namespace App\Tests\Controller;

use App\Entity\City;
use App\Entity\Location;
use App\Entity\Participant;
use App\Repository\LocationRepository;
use App\Service\CityLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LocationControllerTest extends WebTestCase
{


    private $client;
    private $locationRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->loginUser($this->getUserAdmin());
        $this->locationRepository = $this->createMock(LocationRepository::class);
    }

    // Test for successful retrieval of location data
    public function testGetLocationSuccess()
    {
        // Mock a Location entity
        $location = new Location();
        $location->setStreetName('123 Main St');
        $location->setLatitude(40.7128);
        $location->setLongitude(-74.0060);

        // Mock the associated City entity
        $city = $this->createMock(City::class);
        $city->method('getZipCode')->willReturn('76902');
        $city->method('getId')->willReturn(1);
        $city->method('getName')->willReturn('Leger');

        // Set the mocked city to the location
        $location->setCity($city);

        // Use the entity manager to persist the location
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturn($location);
        $this->locationRepository->method('find')->willReturn($location);

        // Simulate a request to the controller
        $this->client->getContainer()->set(LocationRepository::class, $this->locationRepository);
        $this->client->request('GET', '/lieu/get-location/1');

        // Assert the response status code and content
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'streetName' => '123 Main St',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'zipCode' => '76902',
                'cityId' => 1,
                'cityName' => 'Leger',
            ]),
            $this->client->getResponse()->getContent()
        );
    }

    // Test for unauthorized access
    public function testGetLocationUnauthorized()
    {
        $this->client->request('GET', '/logout');

        $this->client->request('GET', '/lieu/get-location/1');

        $this->assertResponseRedirects('/login');
    }

    // Test for location not found (404)
    public function testGetLocationNotFound()
    {
        $this->locationRepository->method('find')->willReturn(null);

        $this->client->getContainer()->set(LocationRepository::class, $this->locationRepository);
        $this->client->request('GET', '/get-location/999'); // Use an ID that does not exist

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // Test pour l'ajout d'une localisation par un admin
    public function testNewLocationByAdmin()
    {
        $crawler = $this->client->request('GET', '/lieu/ajouter');

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['location[name]'] = 'Barrett Huber';
        $form['location[streetNumber]'] = '123';
        $form['location[streetName]'] = 'Main St';
        $form['location[latitude]'] = '76.0';
        $form['location[longitude]'] = '90.0';


        $form['location[city]'] = '1';


        $this->client->submit($form);

        $this->assertResponseRedirects('/lieu'); // Redirection vers la page après succès
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Vous avez ajouté un lieu avec succès !');
    }

    // Test pour l'ajout d'une localisation sans sélection de ville
    public function testNewLocationWithoutCity()
    {
        $this->client->request('GET', '/lieu/ajouter');

        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Enregistrer')->form();

        $form['location[name]'] = 'Barrett Huber';
        $form['location[streetNumber]'] = '123';
        $form['location[streetName]'] = 'Main St';
        $form['location[latitude]'] = '76.0';
        $form['location[longitude]'] = '90.0';

        $form['location[city]'] = ''; // Aucun code postal

        $this->client->submit($form);

        $this->assertSelectorTextContains('.invalid-feedback', 'La ville doit être sélectionnée.');
    }

    // Test pour l'ajout d'une localisation sans soumission du formulaire
    public function testNewLocationFormNotSubmitted()
    {
        $this->client->request('POST', '/lieu/ajouter');

        $this->assertResponseIsSuccessful(); // Vérifie que la réponse est réussie
        $this->assertSelectorExists('form'); // Vérifie qu'il y a un formulaire
    }


    // USERS
    private function getUserAdmin()
    {
        // Load a user for login (depends on how your User is set up)
        // For example, use the UserRepository to find a user by role or ID
        $userRepository = static::getContainer()->get('doctrine')->getRepository(Participant::class);
        return $userRepository->findOneBy(['mail'=>'admin@example.com']); // Adjust this to your needs
    }

    private function getUser()
    {
        // Load a user for login (depends on how your User is set up)
        // For example, use the UserRepository to find a user by role or ID
        $userRepository = static::getContainer()->get('doctrine')->getRepository(Participant::class);
        return $userRepository->findOneBy(['mail'=>'michel.lambert@yahoo.fr']); // Adjust this to your needs
    }
}