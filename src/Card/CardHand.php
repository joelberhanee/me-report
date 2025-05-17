<?php

namespace App\Card;

class CardHand
{
    /** @var Card[] */
    private array $cards = [];

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    /** @return Card[] */
    public function getCards(): array
    {
        return $this->cards;
    }

    public function getNumberOfCards(): int
    {
        return count($this->cards);
    }

    public function __toString(): string
    {
        $output = "";
        foreach ($this->cards as $card) {
            $output .= $card . " ";
        }
        return trim($output);
    }

    public function getSum(): int
    {
        $sum = 0;
        $numAces = 0;

        foreach ($this->cards as $card) {
            $value = $card->getValue();

            if (is_numeric($value)) {
                $sum += (int)$value;
            } elseif (in_array($value, ['J', 'Q', 'K'])) {
                $sum += 10;
            } elseif ($value === 'A') {
                $numAces++;
                $sum += 1;
            }
        }

        for ($i = 0; $i < $numAces; $i++) {
            if ($sum + 10 <= 21) {
                $sum += 10;
            }
        }

        return $sum;
    }
}
