<?php

class Flowery extends Hero
{
    public const MAX_HP = 999;
    public const MAX_MP = 999;

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setHP(self::MAX_HP);
        $this->setMP(self::MAX_MP);
    }

    public function getClassName()
    {
        return 'flowery';
    }

    public function getClassLabel()
    {
        return 'Flowery';
    }

    public function getPassiveDescription(): string
    {
        return 'Possède 999 HP et 999 MP inaltérables et est immunisé à toutes les interactions négatives.';
    }

    public function getMaxHP(): int
    {
        return $this->applyMaxHealthCardBonus(self::MAX_HP);
    }

    public function getMaxMP(): int
    {
        return $this->applyMaxManaCardBonus(self::MAX_MP);
    }

    public function setHP($HP)
    {
        parent::setHP(self::MAX_HP);
    }

    public function setMP(int $MP): void
    {
        parent::setMP(self::MAX_MP);
    }

    public function receiveDamage(int $damage, bool $convertToHealing = false): int
    {
        return 0;
    }

    public function isImmuneToStatusEffects(): bool
    {
        return true;
    }

    public function spendMP(int $amount): bool
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le coût en MP ne peut pas être négatif.');
        }

        return true;
    }

    public function getSpecialAttackName(): string
    {
        return 'Jarona';
    }

    public function getSpecialAttackCost(): int
    {
        return 0;
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $monster->setHP(0);

        return ['Jarona joue sa piste audio et retire tous les HP de ' . $monster->getName() . '.'];
    }
}
