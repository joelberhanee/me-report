<?php

namespace App\Controller\Api;

use App\Card\DeckOfCards;
use App\Card\CardHand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class DeckController extends AbstractController
{
    #[Route('/api/deck', name: 'api_deck', methods: ['GET'])]
    public function apiDeck(SessionInterface $session): JsonResponse
    {
        $deck = new DeckOfCards(true);
        $deck->sort();

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $cards = $deck->getCards();
        $cardData = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue(),
            'ascii' => $card->getAsString()
        ], $cards);

        return new JsonResponse([
            'deck' => $cardData,
            'cards_left' => $deck->cardsLeft()
        ]);
    }

    #[Route('/api/deck/shuffle', name: 'api_deck_shuffle', methods: ['POST'])]
    public function shufflePost(SessionInterface $session): JsonResponse
    {
        $deck = new DeckOfCards(true);
        $deck->shuffle();

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $cards = $deck->getCards();
        $cardData = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue(),
            'ascii' => $card->getAsString(),
        ], $cards);

        return $this->json([
            'message' => 'Kortleken har blandats.',
            'deck' => $cardData,
            'cards_left' => $deck->cardsLeft(),
        ]);
    }

    #[Route('/api/deck/draw', name: 'api_deck_draw', methods: ['POST'])]
    public function apiDraw(SessionInterface $session): JsonResponse
    {
        $deck = $session->get('deck');
        if (!$deck instanceof DeckOfCards) {
            return new JsonResponse(['message' => 'Kortleken har inte initierats korrekt.']);
        }

        if ($deck->cardsLeft() === 0) {
            return new JsonResponse([
                'message' => 'Kortleken är slut! Starta om spelet.',
                'cards_left' => 0,
                'cards' => [],
            ]);
        }

        $hand = new CardHand();
        $drawn = $deck->draw();
        foreach ($drawn as $card) {
            $hand->addCard($card);
        }

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $cardData = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue(),
            'ascii' => $card->getAsString(),
        ], $hand->getCards());

        return new JsonResponse([
            'message' => 'Ett kort har dragits.',
            'cards_left' => $deck->cardsLeft(),
            'cards' => $cardData,
        ]);
    }

    #[Route('/api/deck/draw/{number<\d+>}', name: 'api_deck_draw_number', methods: ['POST'])]
    public function apiDrawNumber(SessionInterface $session, int $number): JsonResponse
    {
        $deck = $session->get('deck');
        if (!$deck instanceof DeckOfCards) {
            return new JsonResponse(['message' => 'Kortleken har inte initierats korrekt.']);
        }

        if ($deck->cardsLeft() === 0) {
            return new JsonResponse([
                'message' => 'Kortleken är slut! Starta om spelet.',
                'cards_left' => 0,
                'cards' => [],
            ]);
        }

        $hand = new CardHand();
        $number = min($number, $deck->cardsLeft());
        $drawn = $deck->draw($number);

        foreach ($drawn as $card) {
            $hand->addCard($card);
        }

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $cardData = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue(),
            'ascii' => $card->getAsString(),
        ], $hand->getCards());

        return new JsonResponse([
            'message' => "$number kort har dragits.",
            'cards_left' => $deck->cardsLeft(),
            'cards' => $cardData,
        ]);
    }
}
