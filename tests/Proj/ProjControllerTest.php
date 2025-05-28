<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class ProjControllerTest extends WebTestCase
{
    private function createSessionAndSetCookie($client, $playerName = 'Testspelare', $playerBalance = 1000)
    {
        $session = $client->getContainer()->get('session.factory')->createSession();
        $session->set('player_name', $playerName);
        $session->set('player_balance', $playerBalance);
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $session;
    }

    public function testIndexRouteLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/proj');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAboutRouteLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/proj/about');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Om Projektet');
    }

    public function testInvalidStartFormRedirectsBack(): void
    {
        $client = static::createClient();
        $client->request('POST', '/proj/start', [
            'name' => '',
            'balance' => 0
        ]);

        $this->assertResponseRedirects('/proj');
    }

    public function testValidStartFormRedirectsToChooseHands(): void
    {
        $client = static::createClient();
        $client->request('POST', '/proj/start', [
            'name' => 'Testspelare',
            'balance' => 1000
        ]);

        $this->assertResponseRedirects('/choose-hands');
    }

    public function testChooseHandsRouteLoads(): void
    {
        $client = static::createClient();
        $this->createSessionAndSetCookie($client);

        $client->request('GET', '/choose-hands');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testSetHandsRejectsInvalidInput(): void
    {
        $client = static::createClient();
        $client->request('POST', '/set-hands', ['hands' => 5]);

        $this->assertResponseRedirects('/choose-hands');
    }

    public function testSetHandsAcceptsValidInput(): void
    {
        $client = static::createClient();
        $this->createSessionAndSetCookie($client);

        $client->request('POST', '/set-hands', ['hands' => 2]);
        $this->assertResponseRedirects('/proj/bet');
    }

    public function testResetClearsSession(): void
    {
        $client = static::createClient();
        $this->createSessionAndSetCookie($client);

        $client->request('GET', '/proj/reset');
        $this->assertResponseRedirects('/proj');
    }
}
