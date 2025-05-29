<?php

namespace App\Tests\Card;

use App\Card\CardGraphic;
use PHPUnit\Framework\TestCase;

class CardGraphicTest extends TestCase
{
    public function testGetClassRed()
    {
        $card = new CardGraphic('A', 'hearts');
        $this->assertEquals('red', $card->getClass());

        $card2 = new CardGraphic('10', 'diamonds');
        $this->assertEquals('red', $card2->getClass());
    }

    public function testGetClassBlack()
    {
        $card = new CardGraphic('A', 'spades');
        $this->assertEquals('black', $card->getClass());

        $card2 = new CardGraphic('K', 'clubs');
        $this->assertEquals('black', $card2->getClass());
    }

    public function testGetGraphic()
    {
        $card = new CardGraphic('A', 'hearts');
        $this->assertEquals('[A♥]', $card->getGraphic());

        $card2 = new CardGraphic('10', 'spades');
        $this->assertEquals('[10♠]', $card2->getGraphic());

        $card3 = new CardGraphic('K', 'diamonds');
        $this->assertEquals('[K♦]', $card3->getGraphic());
    }

    public function testToString()
    {
        $card = new CardGraphic('A', 'hearts');
        $this->assertEquals('[A♥]', (string)$card);

        $card2 = new CardGraphic('10', 'clubs');
        $this->assertEquals('[10♣]', (string)$card2);

        $card3 = new CardGraphic('K', 'diamonds');
        $this->assertEquals('[K♦]', (string)$card3);
    }
}
