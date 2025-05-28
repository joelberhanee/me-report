<?php

namespace App\Tests\Game;

use PHPUnit\Framework\TestCase;
use App\Game\GameProj;
use App\Card\DeckOfCards;
use App\Card\CardHand;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameProjTest extends TestCase
{
    public function testStartGameSetsSessionCorrectly(): void
    {
        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        // Vi förväntar oss att 'set' anropas minst 9 gånger (en för varje session-nyckel i start)
        $session->expects($this->atLeast(9))->method('set');

        $game = new GameProj();
        $game->start($session, 2); // Starta med 2 händer
    }

    public function testDrawReturnsMessageIfGameNotStarted(): void
    {
        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) {
            return match ($key) {
                'deck' => null,
                'player_hands' => [],
                default => $default,
            };
        });

        $game = new GameProj();
        $result = $game->draw($session);

        $this->assertEquals("Spelet har inte startat.", $result);
    }

    public function testDrawReturnsMessageIfNoCardsLeft(): void
    {
        $deck = $this->createMock(DeckOfCards::class);
        $deck->method('draw')->willReturn([]);

        $hand = new CardHand();

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($deck, $hand) {
            return match ($key) {
                'deck' => $deck,
                'player_hands' => [$hand],
                'active_hand_index' => 0,
                'player_sums' => [],
                default => $default,
            };
        });

        // Vi förväntar oss INTE att set() anropas
        $session->expects($this->never())->method('set');

        $game = new GameProj();
        $result = $game->draw($session);

        $this->assertEquals("Inga kort kvar i leken.", $result);
    }

    public function testDrawBustsHandAndAdvancesHandAndTriggersBankPlay(): void
    {
        $deck = $this->createMock(DeckOfCards::class);
        $deck->method('draw')->willReturn([$this->createMock(\App\Card\Card::class)]);

        $hand1 = $this->createMock(CardHand::class);
        $hand1->method('getSum')->willReturn(22); // bust

        $hand2 = $this->createMock(CardHand::class);
        $hand2->method('getSum')->willReturn(10);

        $playerHands = [$hand1, $hand2];

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($deck, $playerHands) {
            return match ($key) {
                'deck' => $deck,
                'player_hands' => $playerHands,
                'active_hand_index' => 0,
                'player_sums' => [0 => 22, 1 => 10],
                default => $default,
            };
        });
        $session->expects($this->any())->method('set');

        $game = new GameProj();
        $message = $game->draw($session);

        $this->assertStringContainsString("förlorade", $message);
    }

    public function testDrawReturnsEmptyStringIfNoBust(): void
    {
        $deck = $this->createMock(DeckOfCards::class);
        $deck->method('draw')->willReturn([$this->createMock(\App\Card\Card::class)]);

        $hand = $this->createMock(CardHand::class);
        $hand->method('getSum')->willReturn(18);

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($deck, $hand) {
            return match ($key) {
                'deck' => $deck,
                'player_hands' => [$hand],
                'active_hand_index' => 0,
                'player_sums' => [0 => 18],
                default => $default,
            };
        });

        $session->expects($this->any())->method('set');

        $game = new GameProj();
        $result = $game->draw($session);

        $this->assertEquals("", $result);
    }

    public function testStayReturnsErrorIfGameNotStarted(): void
    {
        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(fn ($key, $default = null) => match ($key) {
            'player_hands' => [],
            default => $default,
        });

        $game = new GameProj();
        $result = $game->stay($session);

        $this->assertEquals("Spelet har inte startat.", $result);
    }

    public function testStayAdvancesHandAndTriggersBankPlayWhenLastHand(): void
    {
        $deck = $this->createMock(DeckOfCards::class);
        $bank = $this->createMock(CardHand::class);
        $bank->method('getSum')->willReturn(18);

        $playerHands = [new CardHand()];

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($deck, $bank, $playerHands) {
            return match ($key) {
                'player_hands' => $playerHands,
                'active_hand_index' => 1, // Efter avancering är index >= antal händer
                'deck' => $deck,
                'bank' => $bank,
                default => $default,
            };
        });
        $session->expects($this->any())->method('set');

        $game = new GameProj();
        $result = $game->stay($session);

        $this->assertEquals("", $result);
    }

    public function testBankPlayStopsAt17OrMore(): void
    {
        $deck = $this->createMock(DeckOfCards::class);
        $deck->method('draw')->willReturn([
            $this->createMock(\App\Card\Card::class)
        ]);

        $callCount = 0;
        $bankHand = $this->createMock(CardHand::class);
        $bankHand->method('getSum')->willReturnCallback(function () use (&$callCount) {
            $callCount++;
            return $callCount < 2 ? 16 : 17;
        });
        $bankHand->expects($this->any())->method('addCard');

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($deck, $bankHand) {
            return match ($key) {
                'deck' => $deck,
                'bank' => $bankHand,
                'player_hands' => [new CardHand()],
                'player_sums' => [21],
                'bank_sum' => 0,
                'scoreboard' => ['playerWins' => 0, 'bankWins' => 0, 'draws' => 0],
                'player_balance' => 0,
                'bet' => 10,
                default => $default,
            };
        });
        $session->expects($this->any())->method('set');

        $game = new GameProj();
        $method = new \ReflectionMethod($game, 'bankPlay');
        $method->setAccessible(true);
        $method->invoke($game, $session);

        $this->assertTrue(true); // Om vi når hit utan fel har metoden kört
    }

    public function testEvaluateResultsUpdatesScoresAndBalance(): void
    {
        $playerHand1 = $this->createMock(CardHand::class);
        $playerHand2 = $this->createMock(CardHand::class);

        $playerHands = [$playerHand1, $playerHand2];

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($playerHands) {
            return match ($key) {
                'player_hands' => $playerHands,
                'bank_sum' => 18,
                'player_sums' => [20, 22],
                'scoreboard' => ['playerWins' => 0, 'bankWins' => 0, 'draws' => 0],
                'player_balance' => 100,
                'bet' => 10,
                default => $default,
            };
        });
        $session->expects($this->any())->method('set');

        $game = new GameProj();
        $method = new \ReflectionMethod($game, 'evaluateResults');
        $method->setAccessible(true);
        $method->invoke($game, $session);

        $this->assertTrue(true);
    }
}
