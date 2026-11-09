<?php

class Ouroboros extends Monster
{
    public const HP_BONUS_STATE_KEY = 'ouroboros_hp_bonus';
    public const BASE_HP = 200;
    public const HP_PER_VICTORY = 20;

    public function __construct(int $hpBonus = 0)
    {
        parent::__construct('Ouroboros', self::BASE_HP + max(0, $hpBonus));
    }

    public function getClassName(): string
    {
        return 'Ouroboros';
    }

    public function getPassiveDescription(): string
    {
        return 'Empoisonne le héros dès le début du combat. Gagne 20 HP après chaque défaite et revient à 200 HP quand le héros meurt.';
    }
}
