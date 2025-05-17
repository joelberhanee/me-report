<?php

namespace App\Game;

use App\Card\DeckOfCards;
use App\Card\CardHand;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameTwentyOne
{
    public function start(SessionInterface $session): void
    {
        $deck = new DeckOfCards(true);
        $deck->shuffle();

        $session->set('deck', $deck);
        $session->set('player', new CardHand());
        $session->set('bank', new CardHand());
        $session->set('status', 'playing');
        $session->set('showBank', false);
        $session->set('player_sum', 0);
        $session->set('bank_sum', 0);
        $session->set('scoreboard', $session->get('scoreboard', [
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]));
    }

    public function draw(SessionInterface $session): ?string
    {
        $deck = $this->getDeck($session);
        $player = $this->getHand($session, 'player');

        $player->addCard($deck->draw()[0]);
        $playerSum = $player->getSum();

        $session->set('deck', $deck);
        $session->set('player', $player);
        $session->set('player_sum', $playerSum);

        if ($playerSum > 21) {
            $session->set('status', 'You lost');
            $session->set('showBank', true);
            $this->updateScore($session, 'bank');
            return 'Du gick över 21. Banken vann!';
        }

        return null;
    }

    public function stay(SessionInterface $session): string
    {
        $deck = $this->getDeck($session);
        $player = $this->getHand($session, 'player');
        $bank = $this->getHand($session, 'bank');

        $playerSum = $player->getSum();

        while ($bank->getSum() < 17) {
            $bank->addCard($deck->draw()[0]);
        }

        $bankSum = $bank->getSum();

        $session->set('deck', $deck);
        $session->set('bank', $bank);
        $session->set('player_sum', $playerSum);
        $session->set('bank_sum', $bankSum);
        $session->set('showBank', true);

        if ($bankSum > 21) {
            $session->set('status', 'You won');
            $this->updateScore($session, 'player');
            return 'Banken gick över 21. Du vann!';
        }

        if ($bankSum >= $playerSum) {
            $session->set('status', 'Bank won');
            $this->updateScore($session, 'bank');
            return 'Banken vann!';
        }

        $session->set('status', 'You won');
        $this->updateScore($session, 'player');
        return 'Du vann!';
    }

    public function reset(SessionInterface $session): void
    {
        foreach (['deck', 'player', 'bank', 'status', 'showBank', 'scoreboard', 'player_sum', 'bank_sum'] as $key) {
            $session->remove($key);
        }
    }

    private function updateScore(SessionInterface $session, string $winner): void
    {
        $scoreboard = $session->get('scoreboard', [
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]);

        if (!is_array($scoreboard)) {
            $scoreboard = ['playerWins' => 0, 'bankWins' => 0, 'draws' => 0];
        }

        match ($winner) {
            'player' => $scoreboard['playerWins']++,
            'bank' => $scoreboard['bankWins']++,
            default => $scoreboard['draws']++,
        };

        $session->set('scoreboard', $scoreboard);
    }

    private function getDeck(SessionInterface $session): DeckOfCards
    {
        $deck = $session->get('deck');
        if (!$deck instanceof DeckOfCards) {
            $deck = new DeckOfCards(true);
            $deck->shuffle();
        }
        return $deck;
    }

    private function getHand(SessionInterface $session, string $key): CardHand
    {
        $hand = $session->get($key);
        return $hand instanceof CardHand ? $hand : new CardHand();
    }
}
