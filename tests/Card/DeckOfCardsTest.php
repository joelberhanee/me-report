<?php

namespace App\Tests\Card;

use PHPUnit\Framework\TestCase;
use App\Card\Card;
use App\Card\CardGraphic;
use App\Card\DeckOfCards;

class DeckOfCardsTest extends TestCase
{
    public function testCreateDeckOfCardsWithoutGraphics(): void
    {
        $deck = new DeckOfCards();

        $this->assertCount(52, $deck->getCards());
        $this->assertInstanceOf(Card::class, $deck->getCards()[0]);
    }

    public function testCreateDeckOfCardsWithGraphics(): void
    {
        $deck = new DeckOfCards(true);

        $this->assertCount(52, $deck->getCards());
        $this->assertInstanceOf(CardGraphic::class, $deck->getCards()[0]);
    }

    public function testShuffle(): void
    {
        $deck = new DeckOfCards();
        $original = $deck->getCards();

        $deck->shuffle();
        $shuffled = $deck->getCards();

        $this->assertNotEquals($original, $shuffled);
    }

    public function testSort(): void
    {
        $deck = new DeckOfCards();
        $deck->shuffle();
        $deck->sort();
        $sorted = $deck->getCards();

        $this->assertEquals('2', $sorted[0]->getValue());
        $this->assertEquals('clubs', $sorted[0]->getSuit());
        $this->assertEquals('A', $sorted[51]->getValue());
        $this->assertEquals('spades', $sorted[51]->getSuit());
    }

    public function testDrawCards(): void
    {
        $deck = new DeckOfCards();
        $drawn = $deck->draw(5);

        $this->assertCount(5, $drawn);
        $this->assertInstanceOf(Card::class, $drawn[0]);
        $this->assertEquals(47, $deck->cardsLeft());
    }

    public function testDrawMoreCardsThanAvailable(): void
    {
        $deck = new DeckOfCards();
        $deck->draw(52);

        $this->assertEquals(0, $deck->cardsLeft());

        $extra = $deck->draw(1);
        $this->assertEmpty($extra);
        $this->assertEquals(0, $deck->cardsLeft());
    }

    public function testCardsLeft(): void
    {
        $deck = new DeckOfCards();

        $this->assertEquals(52, $deck->cardsLeft());

        $deck->draw(10);
        $this->assertEquals(42, $deck->cardsLeft());

        $deck->draw(42);
        $this->assertEquals(0, $deck->cardsLeft());
    }
}
