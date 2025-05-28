<?php

namespace App\Game;

use App\Card\DeckOfCards;
use App\Card\CardHand;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameProj
{
    /**
     * Startar ett nytt spel.
     * Skapar och blandar en kortlek, delar ut kort till spelarens händer och banken,
     * samt sparar all relevant speldata i sessionen.
     *
     * @param SessionInterface $session Symfony-sessionen där speldata sparas
     * @param int $hands Antal händer spelaren vill spela (1-3)
     */
    public function start(SessionInterface $session, int $hands = 1): void
    {
        $deck = new DeckOfCards();
        $deck->shuffle();

        $playerHands = [];
        for ($i = 0; $i < $hands; $i++) {
            $hand = new CardHand();
            $this->drawCardsToHand($deck, $hand, 2); // Dra 2 kort till varje hand
            $playerHands[] = $hand;
        }

        $bankHand = new CardHand();
        $this->drawCardsToHand($deck, $bankHand, 2); // Dra 2 kort till banken

        // Beräkna poäng för varje spelarhand
        $playerSums = [];
        foreach ($playerHands as $i => $hand) {
            $playerSums[$i] = $this->calculateHandSum($hand);
        }

        // Spara spelets startdata i sessionen
        $session->set('deck', $deck);
        $session->set('player_hands', $playerHands);
        $session->set('bank', $bankHand);
        $session->set('status', 'playing');
        $session->set('active_hand_index', 0);
        $session->set('player_sums', $playerSums);
        $session->set('bank_sum', 0);
        $session->set('showBank', false);
        $session->set('scoreboard', ['playerWins' => 0, 'bankWins' => 0, 'draws' => 0]);
    }

    /**
     * Hjälpmetod för att dra ett antal kort från leken till en given hand.
     *
     * @param DeckOfCards $deck Kortleken att dra från
     * @param CardHand $hand Handen som korten ska läggas till
     * @param int $count Antal kort att dra
     */
    private function drawCardsToHand(DeckOfCards $deck, CardHand $hand, int $count): void
    {
        $cards = $deck->draw($count);
        foreach ($cards as $card) {
            $hand->addCard($card);
        }
    }

    /**
     * Dra ett kort till den aktiva handen.
     * Uppdaterar sessionen med nytt kort och summerar handen.
     * Kontrollerar om handen har "bustat" (över 21).
     *
     * @param SessionInterface $session
     * @return string Meddelande vid bust eller tom sträng annars
     */
    public function draw(SessionInterface $session): string
    {
        $deck = $session->get('deck');
        $playerHands = $session->get('player_hands', []);
        $activeHandIndex = $session->get('active_hand_index', 0);

        if (!$deck || empty($playerHands)) {
            return "Spelet har inte startat.";
        }

        $hand = $playerHands[$activeHandIndex];
        $cards = $deck->draw(1);
        if (empty($cards)) {
            return "Inga kort kvar i leken.";
        }

        $hand->addCard($cards[0]);
        $playerHands[$activeHandIndex] = $hand;

        // Uppdatera session med ny hand och kortlek
        $session->set('player_hands', $playerHands);
        $session->set('deck', $deck);

        // Uppdatera poäng för aktiv hand
        $sum = $this->calculateHandSum($hand);
        $playerSums = $session->get('player_sums', []);
        $playerSums[$activeHandIndex] = $sum;
        $session->set('player_sums', $playerSums);

        // Kontrollera om handen gått över 21 ("bust")
        if ($sum > 21) {
            $message = "Hand #" . ($activeHandIndex + 1) . " blev över 21 och förlorade.";
            $this->advanceHand($session);

            // Om sista handen är spelad, starta bankens tur
            if ($session->get('active_hand_index') >= count($playerHands)) {
                $this->bankPlay($session);
            }

            return $message;
        }

        return "";
    }

    /**
     * Spelaren väljer att stanna på aktiv hand.
     * Går vidare till nästa hand eller bankens tur om sista handen är spelad.
     *
     * @param SessionInterface $session
     * @return string Tom sträng eller felmeddelande
     */
    public function stay(SessionInterface $session): string
    {
        $playerHands = $session->get('player_hands', []);
        if (empty($playerHands)) {
            return "Spelet har inte startat.";
        }

        $this->advanceHand($session);

        // Om sista handen spelad, starta bankens tur
        if ($session->get('active_hand_index') >= count($playerHands)) {
            $this->bankPlay($session);
        }

        return "";
    }

    /**
     * Flytta till nästa hand i ordningen.
     *
     * @param SessionInterface $session
     */
    private function advanceHand(SessionInterface $session): void
    {
        $session->set('active_hand_index', $session->get('active_hand_index', 0) + 1);
    }

    /**
     * Bankens spel.
     * Banken drar kort tills summan är minst 17.
     * Uppdaterar sessionen och utvärderar slutresultatet.
     *
     * @param SessionInterface $session
     */
    private function bankPlay(SessionInterface $session): void
    {
        $deck = $session->get('deck');
        $bank = $session->get('bank');

        if (!$deck || !$bank) {
            return;
        }

        $sum = $this->calculateHandSum($bank);

        // Dra kort tills minst 17 poäng
        while ($sum < 17) {
            $cards = $deck->draw(1);
            if (empty($cards)) {
                break;
            }
            $bank->addCard($cards[0]);
            $sum = $this->calculateHandSum($bank);
        }

        // Uppdatera session med bankens hand och poäng
        $session->set('bank', $bank);
        $session->set('bank_sum', $sum);
        $session->set('showBank', true);

        // Utvärdera spelets slutresultat
        $this->evaluateResults($session);
    }

    /**
     * Beräknar summan av korten i en hand.
     *
     * @param CardHand $hand Handen vars poäng ska beräknas
     * @return int Summan av korten i handen
     */
    private function calculateHandSum(CardHand $hand): int
    {
        return $hand->getSum();
    }

    /**
     * Utvärderar slutresultatet efter bankens tur.
     * Jämför spelarhänder med banken och uppdaterar poängställning och spelarens saldo.
     *
     * @param SessionInterface $session
     */
    private function evaluateResults(SessionInterface $session): void
    {
        $playerHands = $session->get('player_hands', []);
        $bankSum = $session->get('bank_sum', 0);
        $playerSums = $session->get('player_sums', []);
        $scoreboard = $session->get('scoreboard', ['playerWins' => 0, 'bankWins' => 0, 'draws' => 0]);
        $balance = $session->get('player_balance', 0);
        $bet = $session->get('bet', 0);

        $resultsText = [];

        foreach ($playerHands as $i => $hand) {
            $playerSum = $playerSums[$i] ?? $this->calculateHandSum($hand);

            if ($playerSum > 21) {
                $resultsText[] = "Hand #" . ($i + 1) . " förlorade (bust).";
                $scoreboard['bankWins']++;
            } elseif ($bankSum > 21) {
                $resultsText[] = "Hand #" . ($i + 1) . " vann (banken gick bust).";
                $scoreboard['playerWins']++;
                $balance += $bet * 2;
            } elseif ($playerSum > $bankSum) {
                $resultsText[] = "Hand #" . ($i + 1) . " vann!";
                $scoreboard['playerWins']++;
                $balance += $bet * 2;
            } elseif ($playerSum === $bankSum) {
                $resultsText[] = "Hand #" . ($i + 1) . " blev oavgjord.";
                $scoreboard['draws']++;
                $balance += $bet;
            } else {
                $resultsText[] = "Hand #" . ($i + 1) . " förlorade.";
                $scoreboard['bankWins']++;
            }
        }

        // Spara uppdaterad poängställning, saldo och status i sessionen
        $session->set('player_balance', $balance);
        $session->set('scoreboard', $scoreboard);
        $session->set('status', implode(' ', $resultsText));
    }
}