<?php

namespace App\Card;

// Klassen beskriver ett spelkort, exempel. "K of Spades"
class Card
{
    // Kortets värde, t.ex. "2", "K", "A"
    protected string $value;

    // Kortets färg (svit), t.ex. "Hearts", "Spades"
    protected string $suit;

    // Skapar ett kort säger vilket värde och vilken färg det har
    public function __construct(string $value, string $suit)
    {
        $this->value = $value;
        $this->suit = $suit;
    }

    // Denna funktionen ger tillbaka kortets värde (t.ex. "Q")
    public function getValue(): string
    {
        return $this->value;
    }

    // Funktionen ger tillbaka kortets färg (t.ex. "Diamonds")
    public function getSuit(): string
    {
        return $this->suit;
    }

    // Får kortet som en text, t.ex. "A of Spades"
    public function getAsString(): string
    {
        return "{$this->value} of {$this->suit}";
    }

    // Om man använder kortet som text (t.ex. skriver ut det direkt),
    // Så visas det som "10 of Hearts", precis som getAsString
    public function __toString(): string
    {
        // Exempel: "10 ♥" eller "K ♦"
        $symbols = [
            'hearts' => '♥',
            'diamonds' => '♦',
            'clubs' => '♣',
            'spades' => '♠'
        ];

        $symbol = $symbols[$this->suit] ?? '';

        return $this->value . ' ' . $symbol;
    }
}
