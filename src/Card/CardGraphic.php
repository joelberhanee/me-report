<?php

namespace App\Card;

/**
 * Klassen skapar ett spelkort med färgklass röd eller svart samt grafisk symbol beroende på kortets färg.
 */
class CardGraphic extends Card
{
    /**
     * Denna del sätter symboler för varje färg i kortleken.
     */
    private const SUIT_SYMBOLS = [
        'hearts' => '♥',
        'diamonds' => '♦',
        'clubs' => '♣',
        'spades' => '♠',
    ];

    /**
     * Detta innehåller CSS styling som skapar själva kortets färg.
     */
    private string $class;

    /**
     * Här skapas ett nytt kort med värde och färg och sätter rätt färgklass.
     */
    public function __construct(string $value, string $suit)
    {
        parent::__construct($value, $suit);
        $this->class = $this->determineClass($suit);
    }

    /**
     * Denna bestämmer färgen på kortet ifall det ska vara rött eller svart.
     */
    private function determineClass(string $suit): string
    {
        return in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black';
    }

    /**
     * Här hämtas färgklassen för kortet.
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Hämtar kortet som en grafisk textsträng, exempelvis [A♥] eller [10♠].
     */
    public function getGraphic(): string
    {
        $symbol = self::SUIT_SYMBOLS[$this->suit] ?? '?';
        return "[{$this->value}{$symbol}]";
    }

    /**
     * Gör om objektet till en sträng så det visas som ett grafiskt kort.
     */
    public function __toString(): string
    {
        return $this->getGraphic();
    }
}
