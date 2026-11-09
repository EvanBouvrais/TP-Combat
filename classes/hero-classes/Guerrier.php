<?php

class Guerrier extends Hero
{
    public function getClassName()
    {
        return 'guerrier';
    }

    public function getClassLabel()
    {
        return 'Guerrier';
    }

    public function actsFirst(): bool
    {
        return true;
    }

    public function getPassiveDamageReductionPercent(): int
    {
        return 10;
    }

    public function getPassiveDescription(): string
    {
        return 'Réduit les dégâts reçus de 10 % et attaque en premier au début du combat.';
    }

    public function getSpecialAttackName(): string
    {
        return 'Valeur';
    }

    public function getSpecialAttackCost(): int
    {
        return 15;
    }

    public function canUseSpecialAttack(array $fightState): bool
    {
        return empty($fightState['convert_next_damage_to_healing']);
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $fightState['convert_next_damage_to_healing'] = true;
        return ['Valeur convertira la prochaine attaque reçue en soins.'];
    }
}
