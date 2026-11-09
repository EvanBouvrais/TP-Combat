<?php

class Sorcier extends Monster
{
    public function __construct()
    {
        parent::__construct('Sorcier');
    }

    public function getClassName()
    {
        return 'Sorcier';
    }

    public function getPassiveDescription(): string
    {
        return 'A 10 % de chance d’infliger gel, poison ou brûlure à chaque attaque.';
    }
}
