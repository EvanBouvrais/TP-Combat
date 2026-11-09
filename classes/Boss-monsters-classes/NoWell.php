<?php

class NoWell extends Monster
{
    public function __construct()
    {
        parent::__construct('NoWell');
    }

    public function getClassName(): string
    {
        return 'NoWell';
    }

    public function getPassiveDescription(): string
    {
        return 'Gèle le héros dès le début du combat. Chaque attaque a 20 % de chance d’appliquer Snowgrave, qui double l’efficacité du gel sans se cumuler.';
    }
}
