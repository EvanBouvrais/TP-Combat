<?php

class Werewolf extends Hero
{
    public function getClassName()
    {
        return 'werewolf';
    }

    public function getClassLabel()
    {
        return 'Werewolf';
    }

    public function getPassiveDescription(): string
    {
        return 'Immunisé à tous les effets de statut.';
    }

    public function isImmuneToStatusEffects(): bool
    {
        return true;
    }

    public function getSpecialAttackName(): string
    {
        return 'Hurlement';
    }

    public function getSpecialAttackCost(): int
    {
        return 5;
    }

    public function specialAttackConsumesTurn(): bool
    {
        return false;
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $healing = random_int(1, 10);
        $healed = $this->restoreHP($healing);
        $bonus = random_int(1, 10);
        $fightState['werewolf_next_attack_bonus'] =
            ($fightState['werewolf_next_attack_bonus'] ?? 0) + $bonus;

        return [
            'Hurlement restaure ' . $healed . ' HP et ajoute ' . $bonus
            . ' dégâts à la prochaine attaque (bonus cumulé : '
            . $fightState['werewolf_next_attack_bonus'] . ').'
        ];
    }
}
