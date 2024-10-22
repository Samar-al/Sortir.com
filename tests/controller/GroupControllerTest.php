<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GroupControllerTest extends WebTestCase
{
    public function testGroupIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/group/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Group List');
    }
}
