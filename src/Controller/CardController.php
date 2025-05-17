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
    #[Route('/card', name: 'card_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('card/index.html.twig');
    }

    #[Route('/card/deck', name: 'card_deck', methods: ['GET'])]
    public function deck(SessionInterface $session): Response
    {
        $deck = new DeckOfCards(true);
        $deck->sort();

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $this->addFlash('notice', 'Ny sorterad kortlek skapad!');

        return $this->render('card/deck.html.twig', [
            'cards' => $deck->getCards()
        ]);
    }

    #[Route('/card/deck/shuffle', name: 'card_shuffle', methods: ['GET'])]
    public function shuffle(): Response
    {
        return $this->render('card/shuffle.html.twig');
    }

    #[Route('/card/deck/shuffle', name: 'card_shuffle_post', methods: ['POST'])]
    public function shufflePost(SessionInterface $session): Response
    {
        $deck = new DeckOfCards(true);
        $deck->shuffle();

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $this->addFlash('notice', 'Kortleken har återställts och blandats!');

        return $this->render('card/shuffle.html.twig', [
            'cards' => $deck->getCards()
        ]);
    }

    #[Route('/card/deck/draw', name: 'card_draw', methods: ['GET'])]
    public function draw(SessionInterface $session): Response
    {
        $deck = $session->get('deck');
        $hand = new CardHand();

        if (!$deck instanceof DeckOfCards || $deck->cardsLeft() === 0) {
            $this->addFlash('warning', 'Kortleken är slut! Starta om spelet.');
            return $this->render('card/draw.html.twig', [
                'cards' => $hand->getCards(),
                'left' => 0
            ]);
        }

        $drawn = $deck->draw();
        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        foreach ($drawn as $card) {
            $hand->addCard($card);
        }

        return $this->render('card/draw.html.twig', [
            'cards' => $hand->getCards(),
            'left' => $deck->cardsLeft()
        ]);
    }

    #[Route('/card/deck/draw/{number<\d+>}', name: 'card_draw_number', methods: ['GET'])]
    public function drawNumber(SessionInterface $session, int $number): Response
    {
        $deck = $session->get('deck');
        $hand = new CardHand();

        if (!$deck instanceof DeckOfCards || $deck->cardsLeft() === 0) {
            $this->addFlash('warning', 'Kortleken är slut! Starta om spelet.');
            return $this->render('card/draw_number.html.twig', [
                'cards' => $hand->getCards(),
                'left' => 0
            ]);
        }

        if ($number > $deck->cardsLeft()) {
            $number = $deck->cardsLeft();
        }

        $drawn = $deck->draw($number);
        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

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
