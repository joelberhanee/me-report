<?php

namespace App\Tests\Dice;

use PHPUnit\Framework\TestCase;
use App\Dice\Dice;

class DiceTest extends TestCase
{
    public function testDiceStartsWithNullValue(): void
    {
        $dice = new Dice();
        $this->assertNull($dice->getValue(), "Initial value should be null");
    }

    public function testRollReturnsValueBetween1And6(): void
    {
        $dice = new Dice();
        $value = $dice->roll();
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(6, $value);
    }

    public function testGetValueAfterRoll(): void
    {
        $dice = new Dice();
        $rolled = $dice->roll();
        $this->assertSame($rolled, $dice->getValue(), "Value after roll should match getValue()");
    }

    public function testGetAsStringBeforeAndAfterRoll(): void
    {
        $dice = new Dice();
        $this->assertSame("[ ]", $dice->getAsString(), "Initial string should be '[ ]'");
        
        $value = $dice->roll();
        $this->assertSame("[$value]", $dice->getAsString(), "String should reflect rolled value");
    }
}
