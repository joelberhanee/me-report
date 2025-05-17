<?php

namespace App\Dice;

// Den här klassen är en vanlig tärning som kan visa ett värde mellan 1 och 6.
class Dice
{
    // Spara senaste tärningsslaget. Det kan vara ett nummer eller (null).
    protected ?int $value;

    // När man skapar en ny tärning så börjar den utan något värde (den är inte slagen än).
    public function __construct()
    {
        $this->value = null;
    }

    // Den här funktionen slår tärningen, alltså väljer ett slumpmässigt tal mellan 1 och 6.
    // Det värdet sparas och skickas tillbaka.
    public function roll(): int
    {
        $this->value = random_int(1, 6);
        return $this->value;
    }

    // Den här funktionen ger tillbaka det senaste värdet från tärningen.
    // Om tärningen inte slagits ännu så får man null (inget värde).
    public function getValue(): ?int
    {
        return $this->value;
    }

    // Här gör vi om värdet till en text, som ser ut som [4] eller [ ] om inget slagits än.
    public function getAsString(): string
    {
        return "[" . ($this->value ?? ' ') . "]";
    }
}
