<?php

namespace App\Tests\Controller;
use App\Entity\City;
use App\Entity\Participant;
use App\Repository\CityRepository;
use App\Service\CityLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CityControllerTest extends WebTestCase
{
    private $client;
    private $cityRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->loginUser($this->getUserAdmin());
        $this->cityRepository = $this->createMock(CityRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
    }

    // INDEX route testing
    public function testIndexWithoutSearchWhenAdmin(): void
    {
        // Simuler la méthode findAll du repository
        $this->cityRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->client->getContainer()->set(CityRepository::class, $this->cityRepository);

        $this->client->request('GET', '/ville');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithoutSearchWhenUser(): void
    {

        $this->client->loginUser($this->getUser());

        $this->client->request('GET', '/ville');

        // Vérifier que l'utilisateur reçoit une réponse 403 (Accès interdit)
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexWithSearchWhenAdmin(): void
    {
        // Simuler la méthode searchByName du repository
        $this->cityRepository->expects($this->once())
            ->method('searchByName')
            ->with('Paris')
            ->willReturn([]);


        $this->client->getContainer()->set(CityRepository::class, $this->cityRepository);

        $this->client->request('GET', '/ville', ['search' => 'Paris']);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithSearchWhenUser(): void
    {
        $this->client->loginUser($this->getUser());
        $this->client->getContainer()->set(CityRepository::class, $this->cityRepository);

        $this->client->request('GET', '/ville', ['search' => 'Paris']);
        // Vérifier que l'utilisateur reçoit une réponse 403 (Accès interdit)
        $this->assertResponseStatusCodeSame(403);
    }

    // AJOUTER route testing
    public function testNewPageAsUnauthorizedUser(): void
    {
        $this->client->loginUser($this->getUser());
        // Simuler une requête GET sur la page d'ajout sans être connecté comme admin
        $this->client->request('GET', '/ville/ajouter');

        // Vérifier que la réponse est une redirection (vers la page de login ou accès refusé)
        $this->assertResponseStatusCodeSame(403);
    }

    public function testNewFormDisplayed(): void
    {

        // Mock du service pour charger les départements
        $cityLoaderService = $this->createMock(CityLoaderService::class);
        $cityLoaderService->method('loadDepartments')
            ->willReturn(['Department1', 'Department2']);

        // Simuler une requête GET sur la page d'ajout
        $crawler = $this->client->request('GET', '/ville/ajouter');

        // Vérifier que le formulaire est affiché et que la page se charge correctement
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form[name=city]');
    }

    public function testNewCityFormSubmissionSuccess(): void
    {

        $crawler = $this->client->request('GET', '/ville/ajouter');

        // Select the form and fill in the data
        $form = $crawler->selectButton('Créer')->form([
            'city' => [
                'name' => 'Quimper',
                'ZipCode' => '29232',
            ]
        ]);

        // Submit the form
        $this->client->submit($form);

        // Vérifier que la réponse est une redirection après succès
        $this->assertResponseRedirects('/ville');

        // Vérifier que le message flash de succès est défini
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }

    public function testNewCityFormSubmissionWithExistingCity(): void
    {
        $crawler = $this->client->request('GET', '/ville/ajouter');

        // Select the form and fill in the data
        $form = $crawler->selectButton('Créer')->form([
            'city' => [
                'name' => 'Quimper',
                'ZipCode' => '29232',
            ]
        ]);

        $this->client->submit($form);

        // Vérifier que l'utilisateur est redirigé et reçoit un message d'erreur
        $this->assertResponseRedirects('/ville/ajouter');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Ville existe déjà!');
    }

    public function testNewCityFormSubmissionWithEmptyZipCode(): void
    {

        $crawler = $this->client->request('GET', '/ville/ajouter');

        // Select the form and fill in the data
        $form = $crawler->selectButton('Créer')->form([
            'city' => [
                'name' => 'Quimper',
                'ZipCode' => '',
            ]
        ]);

        $this->client->submit($form);

        // Vérifier que l'utilisateur est redirigé et reçoit un message d'erreur
        $this->assertResponseRedirects('/ville/ajouter');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Sélectionner une ville');
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