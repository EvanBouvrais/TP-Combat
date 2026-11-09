<?php

class NuclearWarhead extends Monster
{
    public const STATE_KEY = 'nuclear_warhead_turns_remaining';

    private $turnsRemaining;

    public function __construct(?int $turnsRemaining = null)
    {
        parent::__construct('Nuclear Warhead of Doom and Despair', 200);
        $this->turnsRemaining = $turnsRemaining === null
            ? random_int(4, 8)
            : min(8, max(1, $turnsRemaining));
    }

    public function getClassName()
    {
        return 'Nuclear Warhead of Doom and Despair';
    }

    public function getPassiveDescription(): string
    {
        return 'Explosera et tuera le héros après 4 à 8 tours si elle n’est pas vaincue.';
    }

    public function getTurnsRemaining(): int
    {
        return $this->turnsRemaining;
    }

    public function advanceFuse(): int
    {
        $this->turnsRemaining--;

        return $this->turnsRemaining;
    }
}
