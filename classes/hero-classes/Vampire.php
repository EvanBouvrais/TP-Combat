<?php

class Vampire extends Hero
{
    private $lastLifeSteal = 0;
    private $batMode = false;
    private $lastVampiricDamage = 0;

    public function getClassName()
    {
        return 'vampire';
    }

    public function getClassLabel()
    {
        return 'Vampire';
    }

    public function getPassiveDescription(): string
    {
        return 'Récupère un montant aléatoire de 0 à ses dégâts infligés.';
    }

    public function getSpecialAttackName(): string
    {
        return 'Chauve-souris';
    }

    public function getSpecialAttackCost(): int
    {
        return 20;
    }

    public function canUseSpecialAttack(array $fightState): bool
    {
        return empty($fightState['vampire_bat_turns']);
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $fightState['vampire_bat_turns'] = 4;
        return ['Chauve-souris active le mode vampirique pour ses 3 prochaines attaques et les ripostes associées.'];
    }

    public function setBatMode(bool $enabled): void
    {
        $this->batMode = $enabled;
    }

    public function getLastVampiricDamage(): int
    {
        return $this->lastVampiricDamage;
    }

    public function hit(Monster $monster): int
    {
        if ($this->batMode) {
            $damage = $this->applyRogueCardDamageBonus($this->reduceAttackDamage(rand(0, 50)));
            $this->lastLifeSteal = $this->restoreHP($damage);
            $this->lastVampiricDamage = $damage;
            return 0;
        }

        $damage = parent::hit($monster);
        $this->stealLife($damage);

        return $damage;
    }

    public function hitAllOut(Monster $monster): int
    {
        if ($this->batMode) {
            $damage = $this->applyRogueCardDamageBonus(
                $this->reduceAttackDamage($this->rollAllOutDamage())
            );
            $this->lastLifeSteal = $this->restoreHP($damage);
            $this->lastVampiricDamage = $damage;
            return 0;
        }

        $damage = parent::hitAllOut($monster);
        $this->stealLife($damage);

        return $damage;
    }

    public function getLastLifeSteal(): int
    {
        return $this->lastLifeSteal;
    }

    private function stealLife(int $damage): void
    {
        $this->lastVampiricDamage = 0;
        $this->lastLifeSteal = $this->restoreHP(random_int(0, $damage));
    }
}
