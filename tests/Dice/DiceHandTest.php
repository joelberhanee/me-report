<?php

namespace App\Tests\Dice;

use PHPUnit\Framework\TestCase;
use App\Dice\DiceHand;
use App\Dice\Dice;
use App\Dice\DiceGraphic;

class DiceHandTest extends TestCase
{
    public function testAddDiceIncreasesHandSize(): void
    {
        $hand = new DiceHand();
        $this->assertEquals(0, $hand->getNumberDices());

        $hand->add(new Dice());
        $this->assertEquals(1, $hand->getNumberDices());

        $hand->add(new Dice());
        $this->assertEquals(2, $hand->getNumberDices());
    }

    public function testRollChangesValues(): void
    {
        $hand = new DiceHand();
        $dice1 = new Dice();
        $dice2 = new Dice();
        $hand->add($dice1);
        $hand->add($dice2);

        $hand->roll();

        foreach ($hand->getValues() as $value) {
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
        }
    }

    public function testGetValuesReturnsCorrectCount(): void
    {
        $hand = new DiceHand();
        for ($i = 0; $i < 3; $i++) {
            $hand->add(new Dice());
        }
        $hand->roll();

        $values = $hand->getValues();
        $this->assertCount(3, $values);
    }

    public function testGetStringReturnsStrings(): void
    {
        $hand = new DiceHand();
        $hand->add(new DiceGraphic());
        $hand->add(new DiceGraphic());

        $hand->roll();
        $strings = $hand->getString();

        $this->assertCount(2, $strings);
        foreach ($strings as $str) {
            $this->assertIsString($str);
            $this->assertMatchesRegularExpression('/^⚀|⚁|⚂|⚃|⚄|⚅$/u', $str);
        }
    }
}
