<?php

namespace App\Card;

class CardHand
{
    /**
     * @var Card[]
     */
    private array $cards = [];

    /**
     * Lägg till ett kort i handen.
     */
    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    /**
     * Returnerar alla kort i handen.
     *
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * Returnerar antalet kort i handen.
     */
    public function getNumberOfCards(): int
    {
        return count($this->cards);
    }

    /**
     * Returnerar handen som sträng.
     */
    public function __toString(): string
    {
        $output = "";
        foreach ($this->cards as $card) {
            $output .= $card . " ";
        }
        return trim($output);
    }
}
