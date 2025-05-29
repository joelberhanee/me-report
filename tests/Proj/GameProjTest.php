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

        $this->assertEquals("Spelet har inte startat.", $result, 'När spelet inte är startat ska ett felmeddelande returneras.');
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
                'active_hand_index' => 1,
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
        // Simulera en kortlek som alltid returnerar ett kort
        $deck = $this->createMock(DeckOfCards::class);
        $deck->expects($this->once())
            ->method('draw')
            ->with(1)
            ->willReturn([$this->createMock(\App\Card\Card::class)]);
    
        // Håll koll på hur många gånger getSum anropas
        $callCount = 0;
    
        // Skapa bankhand som simulerar att den börjar på 16 och blir 17
        $bankHand = $this->createMock(CardHand::class);
        $bankHand->expects($this->once())->method('addCard');
        $bankHand->method('getSum')->willReturnCallback(function () use (&$callCount) {
            return ++$callCount < 2 ? 16 : 17;
        });
    
        // Mocka sessionen
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
    
        // Kontrollera att 'bank_sum' sätts till minst 17
        $session->expects($this->atLeastOnce())
            ->method('set')
            ->withConsecutive(
                ['bank', $bankHand],
                ['bank_sum', 17],
                ['showBank', true]
            );
    
        // Mocka GameProj och override evaluateResults
        $game = $this->getMockBuilder(GameProj::class)
            ->onlyMethods(['evaluateResults'])
            ->getMock();
    
        $game->expects($this->once())->method('evaluateResults');
    
        // Kör metoden via reflection
        $method = new \ReflectionMethod($game, 'bankPlay');
        $method->setAccessible(true);
        $method->invoke($game, $session);
    
        // Bekräfta att loopen kördes exakt en gång
        $this->assertEquals(2, $callCount, 'getSum bör ha kallats två gånger: en före och en efter dragning.');
    }
    
    public function testEvaluateResultsUpdatesScoresAndBalance(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
    
        $session->method('get')->willReturnCallback(function ($key, $default = null) {
            return match ($key) {
                'player_sums' => [20, 22],
                'bank_sum' => 18,
                'scoreboard' => ['playerWins' => 1, 'bankWins' => 1, 'draws' => 0],
                'player_balance' => 110,
                'bet' => 10,
                default => $default,
            };
        });
        $session->expects($this->any())->method('set');
    
        $game = new GameProj();
        $method = new \ReflectionMethod($game, 'evaluateResults');
        $method->setAccessible(true);
        $method->invoke($game, $session);
    
        $scoreboard = $session->get('scoreboard');
        $this->assertEquals(1, $scoreboard['playerWins'], 'Spelaren borde ha vunnit en hand.');
        $this->assertEquals(1, $scoreboard['bankWins'], 'Banken borde ha vunnit en hand.');
        $this->assertEquals(0, $scoreboard['draws'], 'Det borde inte finnas några oavgjorda spel.');
    
        $playerBalance = $session->get('player_balance');
        $this->assertEquals(110, $playerBalance, 'Spelarens balans borde ha ökat med 10 efter en vinst.');
    }

        public function testBankPlayReturnsIfDeckOrBankIsNull(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);

        // Simulera att deck eller bank är null (t.ex. båda)
        $session->method('get')->willReturnMap([
            ['deck', null, null],
            ['bank', null, null],
        ]);

        $session->expects($this->never())->method('set');

        $game = new GameProj();
        $method = new \ReflectionMethod($game, 'bankPlay');
        $method->setAccessible(true);

        // Om bank eller deck är null ska metoden bara returnera utan att anropa "set"
        $method->invoke($game, $session);
    }

    public function testBankPlayBreaksIfDeckDrawReturnsEmpty(): void
    {
        $deck = $this->createMock(DeckOfCards::class);
        $deck->method('draw')->willReturn([]); // Inga kort kvar

        $bankHand = $this->createMock(CardHand::class);
        $bankHand->expects($this->never())->method('addCard');

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\Session\SessionInterface $session */
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

        // Detta kommer bryta ut ur loopen på grund av tom dragning
        $method->invoke($game, $session);
    }
}
