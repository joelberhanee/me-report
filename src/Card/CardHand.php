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
        $aces = 0;
    
        foreach ($this->cards as $card) {
            $value = $card->getValue();
    
            switch ($value) {
                case 'J':
                case 'Q':
                case 'K':
                    $sum += 10;
                    break;
                case 'A':
                    $aces++;
                    $sum += 1;
                    break;
                default:
                    $sum += (int)$value;
            }
        }
    
        while ($aces > 0 && $sum + 10 <= 21) {
            $sum += 10;
            $aces--;
        }
    
        return $sum;
    }    
}
