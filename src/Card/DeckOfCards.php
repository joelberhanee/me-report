<?php

namespace App\Card;

class DeckOfCards
{
    /**
     * @var Card[]
     */
    private array $cards = [];

    public function __construct(bool $useGraphics = false)
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                if ($useGraphics) {
                    $this->cards[] = new CardGraphic($value, $suit);
                } else {
                    $this->cards[] = new Card($value, $suit);
                }
            }
        }
    }

    /**
     * Blandar kortleken.
     */
    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function sort(): void
    {
        usort($this->cards, function (Card $a, Card $b) {
            $valueOrder = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
            $suitOrder = ['clubs', 'diamonds', 'hearts', 'spades'];

            $aValue = array_search($a->getValue(), $valueOrder);
            $bValue = array_search($b->getValue(), $valueOrder);

            if ($a->getSuit() === $b->getSuit()) {
                return $aValue <=> $bValue;
            }

            return array_search($a->getSuit(), $suitOrder) <=> array_search($b->getSuit(), $suitOrder);
        });
    }
    public function getCards(): array
    {
        return $this->cards;
    }

    public function draw(int $count = 1): array
    {
        $drawn = [];

        for ($i = 0; $i < $count; $i++) {
            if (count($this->cards) === 0) {
                break;
            }
            $drawn[] = array_shift($this->cards);
        }

        return $drawn;
    }
    
    public function cardsLeft(): int
    {
        return count($this->cards);
    }
}
