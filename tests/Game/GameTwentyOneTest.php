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
        $this->session->expects($this->atLeastOnce())
            ->method('set')
            ->withConsecutive(
                ['deck', $this->isInstanceOf(DeckOfCards::class)],
                ['player', $this->isInstanceOf(CardHand::class)],
                ['bank', $this->isInstanceOf(CardHand::class)],
                ['status', 'playing'],
                ['showBank', false],
                ['player_sum', 0],
                ['bank_sum', 0],
                ['scoreboard', [
                    'playerWins' => 0,
                    'bankWins' => 0,
                    'draws' => 0,
                ]]
            );

        $this->session->method('get')->willReturn([
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]);

        $this->game->start($this->session);
    }

    public function testDrawAddsCardToPlayer(): void
    {
        $deck = new DeckOfCards(true);
        $deck->shuffle();

        $player = new CardHand();
        $this->session->method('get')
            ->willReturnMap([
                ['deck', null, $deck],
                ['player', null, $player]
            ]);

        $this->session->expects($this->atLeast(1))
            ->method('set')
            ->withConsecutive(
                ['deck', $this->isInstanceOf(DeckOfCards::class)],
                ['player', $this->isInstanceOf(CardHand::class)],
                ['player_sum', $this->isType('int')]
            );

        $message = $this->game->draw($this->session);
        $this->assertNull($message);
    }

    public function testDrawBustsPlayer(): void
    {
        $deck = new DeckOfCards(true);
        $player = $this->createMock(CardHand::class);

        $deckCard = $deck->draw()[0];
        $player->method('getSum')->willReturn(22); // Simulerar bust

        $player->expects($this->once())->method('addCard');

        $this->session->method('get')
            ->willReturnMap([
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

        // Spelare får 10, banken får 18
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
        $this->assertTrue(str_contains($result, 'vann'));
    }

    public function testResetClearsSessionVariables(): void
    {
        $this->session->expects($this->exactly(8))
            ->method('remove')
            ->withConsecutive(
                ['deck'],
                ['player'],
                ['bank'],
                ['status'],
                ['showBank'],
                ['scoreboard'],
                ['player_sum'],
                ['bank_sum']
            );

        $this->game->reset($this->session);
    }
}
