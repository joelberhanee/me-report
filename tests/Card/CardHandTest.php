<?php

namespace App\Tests\Card;

use PHPUnit\Framework\TestCase;
use App\Card\Card;
use App\Card\CardHand;

class CardHandTest extends TestCase
{
    public function testAddCard(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card('5', 'hearts'));

        $this->assertEquals(1, count($hand->getCards()));
    }

    public function testGetNumberOfCards(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card('5', 'hearts'));
        $hand->addCard(new Card('7', 'clubs'));

        $this->assertEquals(2, $hand->getNumberOfCards());
    }

    public function testToStringReturnsString(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card('K', 'spades'));

        $this->assertIsString((string) $hand);
    }

    public function testGetSumWithAcesNotElevated(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card('A', 'hearts'));
        $hand->addCard(new Card('K', 'spades'));
        $hand->addCard(new Card('5', 'clubs'));

        $this->assertEquals(16, $hand->getSum());
    }

    public function testGetSumWithNumbers(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card('2', 'diamonds'));
        $hand->addCard(new Card('3', 'hearts'));

        $this->assertEquals(5, $hand->getSum());
    }

    public function testGetSumWithAce(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card('A', 'hearts'));
        $hand->addCard(new Card('9', 'clubs'));

        $this->assertEquals(20, $hand->getSum());
    }
}
