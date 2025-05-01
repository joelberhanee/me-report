<?php

namespace App\Tests\Card;

use PHPUnit\Framework\TestCase;
use App\Card\CardGraphic;

/**
 * Testar klassen CardGraphic.
 */
class CardGraphicTest extends TestCase
{
    /**
     * Testar att ett kort kan skapas och att värde och färg hämtas rätt.
     */
    public function testCreateCardGraphic(): void
    {
        $card = new CardGraphic('K', 'hearts');

        $this->assertInstanceOf(CardGraphic::class, $card);
        $this->assertEquals('K', $card->getValue());
        $this->assertEquals('hearts', $card->getSuit());
    }

    /**
     * Testar att rätt färgklass returneras.
     */
    public function testGetClass(): void
    {
        $redCard = new CardGraphic('Q', 'diamonds');
        $blackCard = new CardGraphic('2', 'clubs');

        $this->assertEquals('red', $redCard->getClass());
        $this->assertEquals('black', $blackCard->getClass());
    }

    /**
     * Testar att rätt symboler visas.
     */
    public function testGetGraphic(): void
    {
        $card = new CardGraphic('A', 'spades');
        $this->assertEquals('[A♠]', $card->getGraphic());
    }

    /**
     * Testar att __toString returnerar samma som getGraphic.
     */
    public function testToString(): void
    {
        $card = new CardGraphic('7', 'clubs');
        $this->assertEquals('[7♣]', (string) $card);
    }
}
