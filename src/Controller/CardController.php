<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Card\DeckOfCards;
use App\Card\CardHand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CardController extends AbstractController
{
    // Visar startsidan för kortdelen
    #[Route('/card', name: 'card_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('card/index.html.twig');
    }

    // Skapar en ny sorterad kortlek och sparar den i sessionen
    #[Route('/card/deck', name: 'card_deck', methods: ['GET'])]
    public function deck(SessionInterface $session): Response
    {
        $deck = new DeckOfCards(true); // Skapar en ny kortlek med alla kort (standard)
        $deck->sort(); // Sorterar kortleken

        $session->set('deck', $deck); // Sparar kortleken i sessionen
        $session->set('cards_left', $deck->cardsLeft()); // Sparar antal kort kvar i session

        $this->addFlash('notice', 'Ny sorterad kortlek skapad!');

        return $this->render('card/deck.html.twig', [
            'cards' => $deck->getCards() // Skickar med alla kort för visning
        ]);
    }

    // Visar sidan för att blanda kortleken (GET)
    #[Route('/card/deck/shuffle', name: 'card_shuffle', methods: ['GET'])]
    public function shuffle(): Response
    {
        return $this->render('card/shuffle.html.twig');
    }

    // Blandar kortleken på POST och sparar ny blandad kortlek i sessionen
    #[Route('/card/deck/shuffle', name: 'card_shuffle_post', methods: ['POST'])]
    public function shufflePost(SessionInterface $session): Response
    {
        $deck = new DeckOfCards(true); // Skapar ny kortlek
        $deck->shuffle(); // Blandar kortleken

        $session->set('deck', $deck); // Sparar blandad kortlek i sessionen
        $session->set('cards_left', $deck->cardsLeft());

        $this->addFlash('notice', 'Kortleken har återställts och blandats!');

        return $this->render('card/shuffle.html.twig', [
            'cards' => $deck->getCards()
        ]);
    }

    // Drar ett kort från kortleken och visar det
    #[Route('/card/deck/draw', name: 'card_draw', methods: ['GET'])]
    public function draw(SessionInterface $session): Response
    {
        $deck = $session->get('deck'); // Hämtar kortleken från sessionen
        $hand = new CardHand(); // Skapar ett nytt kort-häfte (hand)

        // Om kortleken inte finns eller inga kort kvar, visa varning och tom hand
        if (!$deck instanceof DeckOfCards || $deck->cardsLeft() === 0) {
            $this->addFlash('warning', 'Kortleken är slut! Starta om spelet.');
            return $this->render('card/draw.html.twig', [
                'cards' => $hand->getCards(),
                'left' => 0
            ]);
        }

        $drawn = $deck->draw(); // Dra ett kort
        $session->set('deck', $deck); // Uppdatera kortleken i sessionen
        $session->set('cards_left', $deck->cardsLeft());

        // Lägg till det dragna kortet i handen
        foreach ($drawn as $card) {
            $hand->addCard($card);
        }

        return $this->render('card/draw.html.twig', [
            'cards' => $hand->getCards(),
            'left' => $deck->cardsLeft()
        ]);
    }

    // Drar ett angivet antal kort från kortleken
    #[Route('/card/deck/draw/{number<\d+>}', name: 'card_draw_number', methods: ['GET'])]
    public function drawNumber(SessionInterface $session, int $number): Response
    {
        $deck = $session->get('deck');
        $hand = new CardHand();

        // Kontrollera om kortleken finns och har kort kvar
        if (!$deck instanceof DeckOfCards || $deck->cardsLeft() === 0) {
            $this->addFlash('warning', 'Kortleken är slut! Starta om spelet.');
            return $this->render('card/draw_number.html.twig', [
                'cards' => $hand->getCards(),
                'left' => 0
            ]);
        }

        // Om begärt antal kort är fler än vad som finns kvar, anpassa det
        if ($number > $deck->cardsLeft()) {
            $number = $deck->cardsLeft();
        }

        $drawn = $deck->draw($number); // Dra antal kort
        $session->set('deck', $deck); // Uppdatera kortlek och kvarvarande antal
        $session->set('cards_left', $deck->cardsLeft());

        // Lägg till alla dragna kort i handen
        foreach ($drawn as $card) {
            $hand->addCard($card);
        }

        $this->addFlash('notice', "$number kort har dragits!");

        return $this->render('card/draw_number.html.twig', [
            'cards' => $hand->getCards(),
            'left' => $deck->cardsLeft()
        ]);
    }
}
