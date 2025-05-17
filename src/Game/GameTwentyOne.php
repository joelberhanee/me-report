<?php

namespace App\Game;

use App\Card\DeckOfCards;
use App\Card\CardHand;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameTwentyOne
{
    /**
     * Startar ett nytt spel: skapar och blandar kortleken,
     * nollställer spelarens och bankens händer samt status och poäng.
     *
     * @param SessionInterface $session Symfony-session för att lagra speldata
     */
    public function start(SessionInterface $session): void
    {
        $deck = new DeckOfCards(true);
        $deck->shuffle();

        $session->set('deck', $deck);
        $session->set('player', new CardHand());
        $session->set('bank', new CardHand());
        $session->set('status', 'playing');      // Spelets status
        $session->set('showBank', false);        // Om bankens kort ska visas
        $session->set('player_sum', 0);          // Spelarens poängsumma
        $session->set('bank_sum', 0);            // Bankens poängsumma

        // Initiera eller behåll tidigare scoreboard
        $session->set('scoreboard', $session->get('scoreboard', [
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]));
    }

    /**
     * Spelaren drar ett kort från kortleken.
     * Om summan överstiger 21, förlorar spelaren direkt.
     *
     * @param SessionInterface $session
     * @return string|null Felmeddelande vid förlust, annars null
     */
    public function draw(SessionInterface $session): ?string
    {
        $deck = $this->getDeck($session);
        $player = $this->getHand($session, 'player');

        // Dra ett kort och lägg till spelarens hand
        $player->addCard($deck->draw()[0]);
        $playerSum = $player->getSum();

        // Uppdatera sessionen med nytt kort och poängsumma
        $session->set('deck', $deck);
        $session->set('player', $player);
        $session->set('player_sum', $playerSum);

        // Kontrollera om spelaren gått över 21 (bust)
        if ($playerSum > 21) {
            $session->set('status', 'You lost');
            $session->set('showBank', true);
            $this->updateScore($session, 'bank');
            return 'Du gick över 21. Banken vann!';
        }

        return null;
    }

    /**
     * Spelaren väljer att stanna.
     * Banken drar kort tills den når minst 17 poäng.
     * Spelets resultat avgörs och status uppdateras.
     *
     * @param SessionInterface $session
     * @return string Resultatmeddelande
     */
    public function stay(SessionInterface $session): string
    {
        $deck = $this->getDeck($session);
        $player = $this->getHand($session, 'player');
        $bank = $this->getHand($session, 'bank');

        $playerSum = $player->getSum();

        // Banken drar kort tills summan är minst 17
        while ($bank->getSum() < 17) {
            $bank->addCard($deck->draw()[0]);
        }

        $bankSum = $bank->getSum();

        // Uppdatera sessionen med bankens hand och summor
        $session->set('deck', $deck);
        $session->set('bank', $bank);
        $session->set('player_sum', $playerSum);
        $session->set('bank_sum', $bankSum);
        $session->set('showBank', true);  // Visa bankens kort nu

        // Avgör vinnare baserat på poängsumma
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

    /**
     * Återställer spelet genom att ta bort alla relaterade session-variabler.
     *
     * @param SessionInterface $session
     */
    public function reset(SessionInterface $session): void
    {
        foreach (['deck', 'player', 'bank', 'status', 'showBank', 'scoreboard', 'player_sum', 'bank_sum'] as $key) {
            $session->remove($key);
        }
    }

    /**
     * Uppdaterar scoreboard i sessionen beroende på vem som vann.
     *
     * @param SessionInterface $session
     * @param string $winner 'player', 'bank' eller annat (oavgjort)
     */
    private function updateScore(SessionInterface $session, string $winner): void
    {
        $scoreboard = $session->get('scoreboard', [
            'playerWins' => 0,
            'bankWins' => 0,
            'draws' => 0,
        ]);

        // Säkerställ att scoreboard är en array
        if (!is_array($scoreboard)) {
            $scoreboard = ['playerWins' => 0, 'bankWins' => 0, 'draws' => 0];
        }

        // Öka rätt räknare baserat på vinnare
        match ($winner) {
            'player' => $scoreboard['playerWins']++,
            'bank' => $scoreboard['bankWins']++,
            default => $scoreboard['draws']++,
        };

        $session->set('scoreboard', $scoreboard);
    }

    /**
     * Hämtar kortleken från sessionen, eller skapar en ny och blandar den om den saknas.
     *
     * @param SessionInterface $session
     * @return DeckOfCards
     */
    private function getDeck(SessionInterface $session): DeckOfCards
    {
        $deck = $session->get('deck');
        if (!$deck instanceof DeckOfCards) {
            $deck = new DeckOfCards(true);
            $deck->shuffle();
        }
        return $deck;
    }

    /**
     * Hämtar spelarens eller bankens hand från sessionen.
     * Returnerar en ny tom hand om den saknas eller är av fel typ.
     *
     * @param SessionInterface $session
     * @param string $key 'player' eller 'bank'
     * @return CardHand
     */
    private function getHand(SessionInterface $session, string $key): CardHand
    {
        $hand = $session->get($key);
        return $hand instanceof CardHand ? $hand : new CardHand();
    }
}

