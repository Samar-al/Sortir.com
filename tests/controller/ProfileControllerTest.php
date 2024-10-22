<?php
namespace App\Tests\Controller;

use App\Entity\Participant;
use App\Repository\BaseRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testProfileIndex(): void
    {
        // Simulate a client request
        $client = static::createClient();

        // Simulate login (replace with correct user credentials)
        $client->loginUser($this->getUser());

        // Request the index page of the profile
        $crawler = $client->request('GET', '/profil');

        // Check if the response is successful
        $this->assertResponseIsSuccessful();

        // Check for specific content on the page
        $this->assertSelectorTextContains('h1', 'Gérer les Participants');
    }

    public function testNewParticipant(): void
    {
        $client = static::createClient();

        $client->loginUser($this->getUser());

        $crawler = $client->request('GET', '/profil/ajouter');
        
        $this->assertResponseIsSuccessful();

        $baseRepository = static::getContainer()->get(BaseRepository::class);
        $base = $baseRepository->find(1); // Assuming the Base with ID 1 exists

        // Select the form and fill in the data
        $form = $crawler->selectButton('Enregistrer')->form([
            'participant[firstname]' => 'John',
            'participant[lastname]' => 'Doe',
            'participant[username]' => 'JohnDoe',
            'participant[mail]' => 'john.doe@example.com',
            'participant[plainPassword]' => 'password123',
            'participant[confirmPassword]' => 'password123',
            'participant[base]' => $base->getId(),
            // Add other fields if necessary
        ]);

        // Submit the form
        $client->submit($form);

        // Check if the form submission was successful
        $this->assertResponseRedirects('/profil');

        // Follow the redirection and check for the success flash message
        $client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }

    private function getUser()
    {
        // Load a user for login (depends on how your User is set up)
        // For example, use the UserRepository to find a user by role or ID
        $userRepository = static::getContainer()->get('doctrine')->getRepository(Participant::class);
        return $userRepository->findOneBy(['mail'=>'admin@example.com']); // Adjust this to your needs
    }

   


}
