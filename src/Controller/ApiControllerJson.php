<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Card\DeckOfCards;
use App\Card\CardHand;

class ApiControllerJson extends AbstractController
{
    #[Route("/api/quote", name: "api_quote", methods: ['GET'])]
    public function quote(): JsonResponse
    {
        $quotes = [
            "Nar du lyckas viskar dom, nar du misslyckas skriker dom.",
            "Man ar inte vacker forran man ar vacker nyvaken.",
            "En blomma har aldrig blommat pa en dag."
        ];

        $quote = $quotes[array_rand($quotes)];
        $date = new \DateTime();

        return $this->json([
            'quote' => $quote,
            'date' => $date->format('Y-m-d'),
            'timestamp' => $date->getTimestamp(),
        ]);
    }

    #[Route('/api/deck', name: 'api_deck', methods: ['GET'])]
    public function apiDeck(SessionInterface $session): JsonResponse
    {
        $deck = new DeckOfCards(true);
        $deck->sort();

        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        $cards = $deck->getCards();

        $cardData = array_map(function ($card) {
            return [
                'suit' => $card->getSuit(),
                'value' => $card->getValue(),
                'ascii' => $card->getAsString()
            ];
        }, $cards);

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
        $cardData = array_map(function ($card) {
            return [
                'suit' => $card->getSuit(),
                'value' => $card->getValue(),
                'ascii' => $card->getAsString(),
            ];
        }, $cards);

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

        $hand = new CardHand();

        if ($deck->cardsLeft() === 0) {
            return new JsonResponse([
                'message' => 'Kortleken är slut! Starta om spelet.',
                'cards_left' => 0,
                'cards' => [],
            ]);
        }

        $drawn = $deck->draw();
        $session->set('deck', $deck);
        $session->set('cards_left', $deck->cardsLeft());

        foreach ($drawn as $card) {
            $hand->addCard($card);
        }

        $cardData = array_map(function ($card) {
            return [
                'suit' => $card->getSuit(),
                'value' => $card->getValue(),
                'ascii' => $card->getAsString(),
            ];
        }, $hand->getCards());

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

        $hand = new CardHand();

        if ($deck->cardsLeft() === 0) {
            return new JsonResponse([
                'message' => 'Kortleken är slut! Starta om spelet.',
                'cards_left' => 0,
                'cards' => [],
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

        $cardData = array_map(function ($card) {
            return [
                'suit' => $card->getSuit(),
                'value' => $card->getValue(),
                'ascii' => $card->getAsString(),
            ];
        }, $hand->getCards());

        return new JsonResponse([
            'message' => "$number kort har dragits.",
            'cards_left' => $deck->cardsLeft(),
            'cards' => $cardData,
        ]);
    }

    #[Route('/api/game', name: 'api_game', methods: ['GET'])]
    public function gameStatus(SessionInterface $session): JsonResponse
    {
        $playerSum = $session->get('player_sum');
        $bankSum = $session->get('bank_sum');
        $status = $session->get('status');
        $scoreboard = $session->get('scoreboard');

        $response = [
            'player_sum' => $playerSum,
            'bank_sum' => $bankSum,
            'status' => $status,
            'scoreboard' => $scoreboard,
        ];

        return new JsonResponse($response);
    }
}
