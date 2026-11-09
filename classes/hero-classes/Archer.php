<?php

class Archer extends Hero
{
    public function getClassName()
    {
        return 'archer';
    }

    public function getClassLabel()
    {
        return 'Archer';
    }

    public function getDodgeChancePercent(): int
    {
        return intdiv($this->getDodgeChanceBasisPoints(), 100);
    }

    public function getDodgeChanceBasisPoints(): int
    {
        return min(9999, max(2000, parent::getDodgeChanceBasisPoints()));
    }

    protected function getMaximumDodgeChanceBasisPoints(): int
    {
        return 9999;
    }

    public function increaseDodgeChanceBy25Percent(): int
    {
        $currentChance = $this->getDodgeChanceBasisPoints();
        $remainingChance = $this->getMaximumDodgeChanceBasisPoints() - $currentChance;
        $newChance = min(
            $this->getMaximumDodgeChanceBasisPoints(),
            $currentChance + (int) round($remainingChance * 0.25)
        );
        $this->setDodgeChanceBasisPoints($newChance);

        return $newChance;
    }

    public function getPassiveDescription(): string
    {
        return 'A ' . $this->formatDodgeChance()
            . ' % de chance d’esquiver les attaques des monstres.';
    }

    public function getSpecialAttackName(): string
    {
        return 'Éloignement';
    }

    public function getSpecialAttackCost(): int
    {
        return 20;
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $this->increaseDodgeChanceBy25Percent();

        return [
            'Éloignement augmente définitivement l’esquive à '
            . $this->formatDodgeChance() . ' %.'
        ];
    }

    private function formatDodgeChance(): string
    {
        $chance = $this->getDodgeChanceBasisPoints();
        $wholePercent = intdiv($chance, 100);
        $fraction = $chance % 100;

        return $fraction === 0
            ? (string) $wholePercent
            : $wholePercent . '.' . rtrim(str_pad((string) $fraction, 2, '0', STR_PAD_LEFT), '0');
    }
}
