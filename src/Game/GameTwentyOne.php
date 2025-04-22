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

        $player = new CardHand();
        $bank = new CardHand();

        $session->set('deck', $deck);
        $session->set('player', $player);
        $session->set('bank', $bank);
        $session->set('status', 'playing');
        $session->set('showBank', false);
        $session->set('player_sum', $player->getSum());
        $session->set('bank_sum', $bank->getSum());

        $session->set('scoreboard', $session->get('scoreboard', [
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]));
    }

    public function draw(SessionInterface $session): ?string
    {
        $deck = $session->get('deck');
        $player = $session->get('player');

        if (!$player instanceof CardHand) {
            $player = new CardHand();
            $session->set('player', $player);
        }

        if (!$deck instanceof DeckOfCards) {
            $deck = new DeckOfCards(true);
            $deck->shuffle();
            $session->set('deck', $deck);
        }

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
        $deck = $session->get('deck');
        $player = $session->get('player');
        $bank = $session->get('bank');

        if (!$player instanceof CardHand) {
            $player = new CardHand();
            $session->set('player', $player);
        }

        if (!$bank instanceof CardHand) {
            $bank = new CardHand();
            $session->set('bank', $bank);
        }

        if (!$deck instanceof DeckOfCards) {
            $deck = new DeckOfCards(true);
            $deck->shuffle();
            $session->set('deck', $deck);
        }

        $playerSum = $player->getSum();

        while ($bank->getSum() < 17) {
            $bank->addCard($deck->draw()[0]);
        }

        $bankSum = $bank->getSum();
        $session->set('bank', $bank);
        $session->set('bank_sum', $bankSum);
        $session->set('player_sum', $playerSum);
        $session->set('showBank', true);

        if ($bankSum > 21) {
            $session->set('status', 'You won');
            $this->updateScore($session, 'player');
            return 'Banken gick över 21. Du vann!';
        } elseif ($bankSum >= $playerSum) {
            $session->set('status', 'Bank won');
            $this->updateScore($session, 'bank');
            return 'Banken vann!';
        } else {
            $session->set('status', 'You won');
            $this->updateScore($session, 'player');
            return 'Du vann!';
        }
    }

    public function reset(SessionInterface $session): void
    {
        $session->remove('deck');
        $session->remove('player');
        $session->remove('bank');
        $session->remove('status');
        $session->remove('showBank');
        $session->remove('scoreboard');
        $session->remove('player_sum');
        $session->remove('bank_sum');
    }

    private function updateScore(SessionInterface $session, string $winner): void
    {
        $scoreboard = $session->get('scoreboard', [
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]);

        if (!is_array($scoreboard)) {
            $scoreboard = [
                'playerWins' => 0,
                'bankWins' => 0,
                'draws' => 0,
            ];
        }

        $scoreboard['playerWins'] = (int)$scoreboard['playerWins'];
        $scoreboard['bankWins'] = (int)$scoreboard['bankWins'];
        $scoreboard['draws'] = (int)$scoreboard['draws'];

        if ($winner === 'player') {
            $scoreboard['playerWins']++;
        } elseif ($winner === 'bank') {
            $scoreboard['bankWins']++;
        } else {
            $scoreboard['draws']++;
        }

        $session->set('scoreboard', $scoreboard);
    }
}
