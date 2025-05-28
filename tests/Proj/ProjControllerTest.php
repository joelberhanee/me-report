<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

class ProjControllerTest extends WebTestCase
{
    private $client;
    private $session;

    protected function setUp(): void
    {
        // Skapa en klient för att köra tester
        $this->client = static::createClient();

        // Skapa en mock-session för att simulera användardata
        $this->session = new Session(new MockFileSessionStorage());
        $this->client->getContainer()->set('session', $this->session);
    }

    public function testIndex(): void
    {
        // Simulera en GET-förfrågan till index-sidan
        $crawler = $this->client->request('GET', '/proj');
        
        // Kontrollera att statuskoden är 200 (OK)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        // Kontrollera att korrekt vy renderas
        $this->assertSelectorTextContains('h1', 'Välkommen till Blackjack!');
    }

    public function testDoc(): void
    {
        // Simulera en GET-förfrågan till "about" sidan
        $crawler = $this->client->request('GET', '/proj/about');
        
        // Kontrollera att statuskoden är 200 (OK)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        // Kontrollera att "about" texten finns
        $this->assertSelectorTextContains('h2', 'Om spelet');
    }

    public function testStartWithValidData(): void
    {
        // Simulera en POST-förfrågan till "start" med korrekt namn och saldo
        $this->client->request('POST', '/proj/start', [
            'name' => 'Testspelare',
            'balance' => 1000
        ]);

        // Kontrollera att vi blir omdirigerade till "choose_hands"
        $this->assertResponseRedirects('/choose-hands');
    }

    public function testStartWithInvalidData(): void
    {
        // Testa ogiltiga data (namn saknas)
        $this->client->request('POST', '/proj/start', [
            'name' => '',
            'balance' => 1000
        ]);

        // Kontrollera att vi får ett flash-meddelande och blir omdirigerade till index
        $this->assertResponseRedirects('/proj');
    }

    public function testChooseHands(): void
    {
        // Förbered sessionen
        $this->session->set('player_name', 'Testspelare');
        $this->session->set('player_balance', 1000);

        // Simulera en GET-förfrågan till "choose-hands" sidan
        $crawler = $this->client->request('GET', '/choose-hands');
        
        // Kontrollera att sidan laddas korrekt och visar de gamla valen
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h2', 'Välj antal händer');
    }

    public function testSetHands(): void
    {
        // Simulera en POST-förfrågan med ett giltigt antal händer
        $this->client->request('POST', '/set-hands', [
            'hands' => 2
        ]);

        // Kontrollera att vi blir omdirigerade till "bet"
        $this->assertResponseRedirects('/proj/bet');
    }

    public function testSetInvalidHands(): void
    {
        // Testa ogiltigt antal händer (4)
        $this->client->request('POST', '/set-hands', [
            'hands' => 4
        ]);

        // Kontrollera att vi blir omdirigerade tillbaka till "choose_hands"
        $this->assertResponseRedirects('/choose-hands');
    }

    public function testPlaceBet(): void
    {
        // Simulera en POST-förfrågan till "placebet" med en satsning
        $this->session->set('player_balance', 1000);
        $this->client->request('POST', '/proj/placebet', [
            'bet' => 100
        ]);

        // Kontrollera att vi blir omdirigerade till "play"
        $this->assertResponseRedirects('/proj/play');
    }

    public function testPlaceBetWithInsufficientFunds(): void
    {
        // Testa satsning större än saldo
        $this->session->set('player_balance', 100);
        $this->client->request('POST', '/proj/placebet', [
            'bet' => 200
        ]);

        // Kontrollera att vi får ett flash-meddelande om otillräckliga medel
        $this->assertResponseRedirects('/proj/bet');
    }

    public function testReset(): void
    {
        // Simulera en GET-förfrågan för att rensa sessionen och återgå till startsidan
        $this->client->request('GET', '/proj/reset');
        
        // Kontrollera att sessionen rensas och vi omdirigeras till startsidan
        $this->assertResponseRedirects('/proj');
    }
}
