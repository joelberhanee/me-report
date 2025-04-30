<?php

namespace App\Tests\Card;

use PHPUnit\Framework\TestCase;
use App\Card\CardGraphic;

class CardGraphicTest extends TestCase
{
    public function testCreateCardGraphic(): void
    {
        $card = new CardGraphic('K', 'hearts');

        $this->assertInstanceOf(CardGraphic::class, $card);
        $this->assertEquals('K', $card->getValue());
    }

    public function testGetClass(): void
    {
        $redCard = new CardGraphic('Q', 'diamonds');
        $blackCard = new CardGraphic('2', 'clubs');

        $this->assertEquals('red', $redCard->getClass());
        $this->assertEquals('black', $blackCard->getClass());
    }

    public function testGetGraphic(): void
    {
        $card = new CardGraphic('A', 'spades');
        $this->assertEquals('[A♠]', $card->getGraphic());
    }

    public function testGetAsString(): void
    {
        $card = new CardGraphic('7', 'clubs');
        $this->assertEquals('[7♣]', (string) $card);
    }
}
