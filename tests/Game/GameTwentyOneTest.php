<?php

namespace App\Tests\Game;

use PHPUnit\Framework\TestCase;
use App\Game\GameTwentyOne;
use App\Card\CardHand;
use App\Card\DeckOfCards;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameTwentyOneTest extends TestCase
{
    private GameTwentyOne $game;
    private \PHPUnit\Framework\MockObject\MockObject&SessionInterface $session;

    protected function setUp(): void
    {
        $this->game = new GameTwentyOne();
        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testStartInitializesSession(): void
    {
        // Förvänta dig att set() kallas minst några gånger med olika nycklar
        $this->session->expects($this->atLeast(1))->method('set');

        $this->session->method('get')->willReturn([
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]);

        $this->game->start($this->session);

        // Ingen assertion behövs här, om inga fel kastas är testet OK
        $this->assertTrue(true);
    }

    public function testDrawAddsCardToPlayer(): void
    {
        $deck = new DeckOfCards(true);
        $player = new CardHand();

        $this->session->method('get')->willReturnMap([
            ['deck', null, $deck],
            ['player', null, $player]
        ]);

        $this->session->expects($this->atLeast(1))->method('set');

        $message = $this->game->draw($this->session);
        $this->assertNull($message);
    }

    public function testDrawBustsPlayer(): void
    {
        $deck = new DeckOfCards(true);
        $player = $this->createMock(CardHand::class);

        $player->method('getSum')->willReturn(22); // Simulerar att spelaren går över 21
        $player->expects($this->once())->method('addCard');

        $this->session->method('get')->willReturnMap([
            ['deck', null, $deck],
            ['player', null, $player],
            ['scoreboard', [
                'playerWins' => 0,
                'bankWins' => 0,
                'draws' => 0
            ]]
        ]);

        $this->session->expects($this->atLeastOnce())->method('set');

        $message = $this->game->draw($this->session);
        $this->assertEquals('Du gick över 21. Banken vann!', $message);
    }

    public function testStayBankWins(): void
    {
        $deck = new DeckOfCards(true);
        $player = new CardHand();
        $bank = new CardHand();

        // Lägg till kort så banken har minst 17
        $player->addCard($deck->draw()[0]);
        $bank->addCard($deck->draw()[0]);
        $bank->addCard($deck->draw()[0]);

        while ($bank->getSum() < 17) {
            $bank->addCard($deck->draw()[0]);
        }

        $this->session->method('get')->willReturnMap([
            ['deck', null, $deck],
            ['player', null, $player],
            ['bank', null, $bank],
            ['scoreboard', [
                'playerWins' => 0,
                'bankWins' => 0,
                'draws' => 0
            ]]
        ]);

        $this->session->expects($this->atLeastOnce())->method('set');

        $result = $this->game->stay($this->session);
        $this->assertIsString($result);
        $this->assertStringContainsString('vann', $result);
    }

    public function testResetClearsSessionVariables(): void
    {
        $this->session->expects($this->atLeast(1))->method('remove');

        $this->game->reset($this->session);

        // Även här: om inga fel uppstår så är testet godkänt
        $this->assertTrue(true);
    }
}
