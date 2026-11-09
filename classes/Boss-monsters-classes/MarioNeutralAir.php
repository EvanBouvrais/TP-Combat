<?php

class MarioNeutralAir extends Monster
{
    public function __construct()
    {
        parent::__construct('Mario Neutral Air');
    }

    public function getClassName(): string
    {
        return 'Mario Neutral Air';
    }

    public function getPassiveDescription(): string
    {
        return 'Brûle le héros dès le début du combat. À chaque tour, 30 % de chance d’utiliser Neutral Air, une attaque équivalente à All Out.';
    }

    public function usesNeutralAir(): bool
    {
        return random_int(1, 100) <= 30;
    }
}
