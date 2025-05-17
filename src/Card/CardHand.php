<?php

namespace App\Card;

class CardHand
{
    /** @var Card[] */
    private array $cards = [];

    // Lägg till ett kort i handen
    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    /** 
     * Hämta alla kort i handen 
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    // Räkna hur många kort som finns i handen
    public function getNumberOfCards(): int
    {
        return count($this->cards);
    }

    // Gör om handen till en sträng som visar alla kort
    public function __toString(): string
    {
        $output = "";
        foreach ($this->cards as $card) {
            $output .= $card . " ";
        }
        return trim($output);
    }

    // Räkna ihop poängen i handen
    public function getSum(): int
    {
        $sum = 0;
        $acesCount = 0; // Räkna antalet ess
    
        foreach ($this->cards as $card) {
            $value = $card->getValue();
    
            if (in_array($value, ['J', 'Q', 'K'])) {
                // Klädda kort är värda 10 poäng
                $sum += 10;
            } elseif ($value === 'A') {
                // Ess räknas som 1 poäng först, men kan ändras senare
                $acesCount++;
                $sum += 1;
            } else {
                // Övriga kort är värda sitt nummer
                $sum += (int)$value;
            }
        }
    
        // Justera ess om det går utan att gå över 21
        return $this->optimizeAces($sum, $acesCount);
    }
    
    // Om det finns ess kan vi räkna dem som 11 istället för 1, om det inte gör att summan blir för hög
    private function optimizeAces(int $sum, int $acesCount): int
    {
        while ($acesCount > 0 && $sum + 10 <= 21) {
            $sum += 10; // Lägg till 10 extra poäng för ett ess (11 totalt)
            $acesCount--;
        }
        return $sum;
    }
}    
