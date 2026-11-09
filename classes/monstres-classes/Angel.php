<?php

class Angel extends Monster
{
    public function __construct()
    {
        parent::__construct('Angel');
    }

    public function getClassName()
    {
        return 'Angel';
    }

    public function getMissChancePercent(): int
    {
        return 0;
    }

    public function getPassiveDescription(): string
    {
        return 'Inflige deux tiers des dégâts habituels et ne rate jamais ses attaques.';
    }

    protected function applyDamageMultiplier(int $damage): int
    {
        return intdiv($damage * 2, 3);
    }
}
