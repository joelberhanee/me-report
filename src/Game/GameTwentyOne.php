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
        $player = $this->getCardHand($session, 'player');

        $player->addCard($deck->draw()[0]);
        $playerSum = $player->getSum();

        $session->set('deck', $deck);
        $session->set('player', $player);
        $session->set('player_sum', $playerSum);

        if ($playerSum > 21) {
            $this->handlePlayerBust($session);
            return 'Du gick över 21. Banken vann!';
        }

        return null;
    }

    public function stay(SessionInterface $session): string
    {
        $deck = $this->getDeck($session);
        $player = $this->getCardHand($session, 'player');
        $bank = $this->getCardHand($session, 'bank');

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

        $scoreboard[$winner . 'Wins'] ??= 0;
        if (in_array($winner, ['player', 'bank'])) {
            $scoreboard[$winner . 'Wins']++;
        } else {
            $scoreboard['draws']++;
        }

        $session->set('scoreboard', $scoreboard);
    }

    private function getDeck(SessionInterface $session): DeckOfCards
    {
        $deck = $session->get('deck');
        if (!$deck instanceof DeckOfCards) {
            $deck = new DeckOfCards(true);
            $deck->shuffle();
            $session->set('deck', $deck);
        }
        return $deck;
    }

    private function getCardHand(SessionInterface $session, string $who): CardHand
    {
        $hand = $session->get($who);
        if (!$hand instanceof CardHand) {
            $hand = new CardHand();
            $session->set($who, $hand);
        }
        return $hand;
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
        }

        if ($bankSum >= $playerSum) {
            return [
                'status' => 'Bank won',
                'winner' => 'bank',
                'message' => 'Banken vann!'
            ];
        }

        return [
            'status' => 'You won',
            'winner' => 'player',
            'message' => 'Du vann!'
        ];
    }

    private function handlePlayerBust(SessionInterface $session): void
    {
        $session->set('status', 'You lost');
        $session->set('showBank', true);
        $this->updateScore($session, 'bank');
    }
}
