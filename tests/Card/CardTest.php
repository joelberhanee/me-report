<?php

namespace App\Tests\Card;

use PHPUnit\Framework\TestCase;
use App\Card\Card;

class CardTest extends TestCase
{
    public function testCreateCardAndGetters(): void
    {
        $card = new Card('A', 'hearts');

        $this->assertInstanceOf(Card::class, $card);
        $this->assertEquals('A', $card->getValue());
        $this->assertEquals('hearts', $card->getSuit());
    }
    public function testGetAsString(): void
    {
        $card = new Card('10', 'spades');

        $this->assertEquals('10 of spades', $card->getAsString());
    }
}
