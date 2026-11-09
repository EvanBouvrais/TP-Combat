<?php

class EvilAnt extends Monster
{
    public function __construct()
    {
        parent::__construct('Evil Ant');
    }

    public function getClassName()
    {
        return 'Evil Ant';
    }

    public function getPassiveDescription(): string
    {
        return 'Esquive 80 % des attaques et inflige 20 % des dégâts habituels.';
    }

    protected function shouldDodgeDamage(): bool
    {
        return random_int(1, 100) <= 80;
    }

    protected function applyDamageMultiplier(int $damage): int
    {
        return intdiv($damage, 5);
    }
}
