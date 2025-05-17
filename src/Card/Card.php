<?php

namespace App\Card;

// Den här klassen beskriver ett spelkort, t.ex. "10 of Hearts" eller "K of Spades"
class Card
{
    // Kortets värde, t.ex. "2", "K", "A"
    protected string $value;

    // Kortets färg (svit), t.ex. "Hearts", "Spades"
    protected string $suit;

    // När vi skapar ett kort så säger vi vilket värde och vilken färg det har
    public function __construct(string $value, string $suit)
    {
        $this->value = $value;
        $this->suit = $suit;
    }

    // Den här funktionen ger tillbaka kortets värde (t.ex. "Q")
    public function getValue(): string
    {
        return $this->value;
    }

    // Den här funktionen ger tillbaka kortets färg (t.ex. "Diamonds")
    public function getSuit(): string
    {
        return $this->suit;
    }

    // Här får vi kortet som en text, t.ex. "A of Spades"
    public function getAsString(): string
    {
        return "{$this->value} of {$this->suit}";
    }

    // Om man använder kortet som text (t.ex. skriver ut det direkt),
    // så visas det som "10 of Hearts", precis som getAsString
    public function __toString(): string
    {
        return $this->value . ' of ' . $this->suit;
    }
}
