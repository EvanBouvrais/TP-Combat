<?php

class Ogre extends Monster
{
    public function __construct()
    {
        parent::__construct('Ogre', 120);
    }

    public function getClassName()
    {
        return 'Ogre';
    }

    public function getDamageBonusPercent(): int
    {
        return 20;
    }

    public function getMissChancePercent(): int
    {
        return 10;
    }

    public function getPassiveDescription(): string
    {
        return 'Possède 120 HP, inflige 20 % de dégâts supplémentaires et a 10 % de chance de rater ses attaques.';
    }
}
