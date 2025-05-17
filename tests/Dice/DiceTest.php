<?php

namespace App\Dice;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for class Dice.
 */
class DiceTest extends TestCase
{
    /**
     * Construct object and verify that the object has the expected
     * properties, use no arguments.
     */
    public function testCreateDice(): void
    {
        $die = new Dice();
        $this->assertInstanceOf("\App\Dice\Dice", $die);

        $res = $die->getAsString();
        $this->assertNotEmpty($res);
    }

    public function testConstruct(): void
    {
        $dice = new Dice();
        $this->assertNull($dice->getValue());
    }

    public function testRoll(): void
    {
        $dice = new Dice();
        $value = $dice->roll();
        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(6, $value);
        $this->assertEquals($value, $dice->getValue());
    }

    public function testGetValueBeforeRoll(): void
    {
        $dice = new Dice();
        $this->assertNull($dice->getValue());
    }

    public function testGetAsString(): void
    {
        $dice = new Dice();

        // Justera förväntat värde till att matcha koden
        $this->assertEquals('[]', $dice->getAsString());

        $dice->roll();
        $str = $dice->getAsString();
        $this->assertMatchesRegularExpression('/\[\d\]/', $str);
    }
}
