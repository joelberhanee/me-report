<?php

namespace App\Card;

class DeckOfCards
{
    /**
     * @var Card[]|CardGraphic[]
     */
    private array $cards = [];

    /**
     * @param bool $useGraphics
     */
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

    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function sort(): void
    {
        usort($this->cards, function (Card $a, Card $b) {
            $valueOrder = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
            $suitOrder = ['clubs', 'diamonds', 'hearts', 'spades'];

            $aValue = array_search($a->getValue(), $valueOrder, true);
            $bValue = array_search($b->getValue(), $valueOrder, true);

            if ($a->getSuit() === $b->getSuit()) {
                return $aValue <=> $bValue;
            }

            return array_search($a->getSuit(), $suitOrder, true) <=> array_search($b->getSuit(), $suitOrder, true);
        });
    }

    /**
     * @return Card[]|CardGraphic[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @param int $count
     * @return Card[]|CardGraphic[]
     */
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
