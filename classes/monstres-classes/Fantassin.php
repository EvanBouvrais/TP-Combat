<?php

class Fantassin extends Monster
{
    public function __construct()
    {
        parent::__construct('Fantassin', 50);
    }

    public function getClassName()
    {
        return 'Fantassin';
    }

    public function getPassiveDescription(): string
    {
        return 'Possède 50 HP et a 50 % de chance de ressusciter avec 25 HP à chaque défaite.';
    }

    public function receiveDamage(int $damage): int
    {
        $this->lastResurrected = false;
        $damageDealt = parent::receiveDamage($damage);

        if ($this->getHP() === 0 && random_int(1, 2) === 1) {
            $this->setHP(intdiv($this->getMaxHP(), 2));
            $this->lastResurrected = true;
        }

        return $damageDealt;
    }
}
