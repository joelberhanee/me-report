<?php

namespace App\Card;

class CardGraphic extends Card
{
    private const SUIT_SYMBOLS = [
        'hearts' => '♥',
        'diamonds' => '♦',
        'clubs' => '♣',
        'spades' => '♠',
    ];

    private string $class;

    public function __construct(string $value, string $suit)
    {
        parent::__construct($value, $suit);
        $this->class = $this->determineClass($suit);
    }

    private function determineClass(string $suit): string
    {
        return in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black';
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getGraphic(): string
    {
        $symbol = self::SUIT_SYMBOLS[$this->suit] ?? '?';
        return "[{$this->value}{$symbol}]";
    }

    public function __toString(): string
    {
        return $this->getGraphic();
    }
}
