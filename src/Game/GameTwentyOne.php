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
        $this->ensureGameObjects($session);

        $deck = $session->get('deck');
        $player = $session->get('player');
        $bank = $session->get('bank');

        $playerSum = $player->getSum();

        $this->bankDrawCards($bank, $deck);

        $bankSum = $bank->getSum();

        $session->set('bank', $bank);
        $session->set('bank_sum', $bankSum);
        $session->set('player_sum', $playerSum);
        $session->set('showBank', true);

        $result = $this->determineWinner($playerSum, $bankSum);

        $session->set('status', $result['status']);
        $this->updateScore($session, $result['winner']);

        return $result['message'];
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

    private function ensureGameObjects(SessionInterface $session): void
    {
        if (!$session->get('player') instanceof CardHand) {
            $session->set('player', new CardHand());
        }
        if (!$session->get('bank') instanceof CardHand) {
            $session->set('bank', new CardHand());
        }
        if (!$session->get('deck') instanceof DeckOfCards) {
            $deck = new DeckOfCards(true);
            $deck->shuffle();
            $session->set('deck', $deck);
        }
    }

    private function bankDrawCards(CardHand $bank, DeckOfCards $deck): void
    {
        while ($bank->getSum() < 17) {
            $bank->addCard($deck->draw()[0]);
        }
    }

    private function determineWinner(int $playerSum, int $bankSum): array
    {
        if ($bankSum > 21) {
            return [
                'status' => 'You won',
                'winner' => 'player',
                'message' => 'Banken gick över 21. Du vann!'
            ];
        } elseif ($bankSum >= $playerSum) {
            return [
                'status' => 'Bank won',
                'winner' => 'bank',
                'message' => 'Banken vann!'
            ];
        } else {
            return [
                'status' => 'You won',
                'winner' => 'player',
                'message' => 'Du vann!'
            ];
        }
    }
}
