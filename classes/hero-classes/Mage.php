<?php

class Mage extends Hero
{
    public function getClassName()
    {
        return 'mage';
    }

    public function getClassLabel()
    {
        return 'Mage';
    }

    public function getManaRegenerationPerTurn(): int
    {
        return $this->applyManaRegenerationCardBonus(6);
    }

    public function getFreezeMissChancePercent(): int
    {
        return 33;
    }

    public function getPoisonDamagePerTurn(): int
    {
        return 7;
    }

    public function getBurnGrowthPercent(): int
    {
        return 150;
    }

    public function getPassiveDescription(): string
    {
        return 'Régénère 6 MP par tour, renforce ses effets de statut et récupère 120 % des dégâts avec Vampirisme.';
    }

    public function getSpecialAttackName(): string
    {
        return 'Cercle de soin';
    }

    public function getSpecialAttackCost(): int
    {
        return 20;
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $healed = $this->restoreHP(60);
        return ['Cercle de soin restaure ' . $healed . ' HP au Mage.'];
    }
}
