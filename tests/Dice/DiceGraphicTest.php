<?php

namespace App\Tests\Dice;

use PHPUnit\Framework\TestCase;
use App\Dice\DiceGraphic;

class DiceGraphicTest extends TestCase
{
    public function testRollReturnsValueBetween1And6(): void
    {
        $dice = new DiceGraphic();
        $value = $dice->roll();

        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(6, $value);
    }

    public function testGetAsStringReturnsCorrectSymbolAfterRoll(): void
    {
        $dice = new DiceGraphic();
        $dice->roll();
        $symbol = $dice->getAsString();

        $validSymbols = ['⚀', '⚁', '⚂', '⚃', '⚄', '⚅'];
        $this->assertContains($symbol, $validSymbols);
    }
}
