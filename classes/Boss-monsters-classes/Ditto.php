<?php

class Ditto extends Monster
{
    private $copiedHero;
    private $mana = Hero::MAX_MP;
    private $dodgeChanceBasisPoints = 0;
    private $lastDamageConvertedToHealing = false;
    private $convertNextDamageToHealing = false;

    public function __construct(Hero $hero)
    {
        parent::__construct('Ditto', 100);
        $this->copiedHero = $hero instanceof Flowery
            ? new Vampire($hero->getName())
            : Hero::create($hero->getName(), $hero->getClassName());
        $this->copiedHero->setDodgeChanceBasisPoints($hero->getDodgeChanceBasisPoints());
        $this->dodgeChanceBasisPoints = $this->copiedHero->getDodgeChanceBasisPoints();
    }

    public function getClassName()
    {
        return 'Ditto';
    }

    public function getPassiveDescription(): string
    {
        return 'Copie le passif de '
            . $this->copiedHero->getClassLabel()
            . ' : '
            . $this->copiedHero->getPassiveDescription();
    }

    public function getCopiedHero(): Hero
    {
        return $this->copiedHero;
    }

    public function actsFirst(): bool
    {
        return $this->copiedHero->actsFirst();
    }

    public function getManaRegenerationPerTurn(): int
    {
        return $this->copiedHero->getManaRegenerationPerTurn();
    }

    public function getMana(): int
    {
        return $this->mana;
    }

    public function setMana(int $mana): void
    {
        $this->mana = min(Hero::MAX_MP, max(0, $mana));
    }

    public function spendMana(int $amount): bool
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le coût en MP ne peut pas être négatif.');
        }

        if ($this->mana < $amount) {
            return false;
        }

        $this->mana -= $amount;
        return true;
    }

    public function regenerateMana(): void
    {
        $this->setMana($this->mana + $this->getManaRegenerationPerTurn());
    }

    public function getSpecialAttackCost(): int
    {
        return $this->copiedHero->getSpecialAttackCost();
    }

    public function getFreezeMissChancePercent(): int
    {
        return $this->copiedHero->getFreezeMissChancePercent();
    }

    public function getPoisonDamagePerTurn(): int
    {
        return $this->copiedHero->getPoisonDamagePerTurn();
    }

    public function getBurnGrowthPercent(): int
    {
        return $this->copiedHero->getBurnGrowthPercent();
    }

    public function getDodgeChanceBasisPoints(): int
    {
        return $this->dodgeChanceBasisPoints;
    }

    public function increaseDodgeChanceBy25Percent(): int
    {
        $this->dodgeChanceBasisPoints = (int) round($this->dodgeChanceBasisPoints * 1.25);
        return $this->dodgeChanceBasisPoints;
    }

    public function restoreState(array $fightState): void
    {
        $this->setMana((int) ($fightState['ditto_mana'] ?? Hero::MAX_MP));
        $this->dodgeChanceBasisPoints = max(
            0,
            (int) ($fightState['ditto_dodge_chance'] ?? $this->dodgeChanceBasisPoints)
        );
        $this->convertNextDamageToHealing = !empty($fightState['ditto_convert_next_damage']);
    }

    public function storeState(array &$fightState): void
    {
        $fightState['ditto_mana'] = $this->mana;
        $fightState['ditto_dodge_chance'] = $this->dodgeChanceBasisPoints;
        $fightState['ditto_convert_next_damage'] = $this->convertNextDamageToHealing;
    }

    public function convertNextDamageToHealing(): void
    {
        $this->convertNextDamageToHealing = true;
    }

    public function receiveDamage(int $damage): int
    {
        $this->lastDamageDodged = false;
        $this->lastDamageConvertedToHealing = false;
        $damage = max(0, $damage);

        if (
            $this->dodgeChanceBasisPoints > 0
            && random_int(1, 10000) <= min(10000, $this->dodgeChanceBasisPoints)
        ) {
            $this->lastDamageDodged = true;
            return 0;
        }

        if ($this->convertNextDamageToHealing) {
            $this->convertNextDamageToHealing = false;
            $this->setHP($this->getHP() + $damage);
            $this->lastDamageConvertedToHealing = true;
            return 0;
        }

        $reduction = $this->copiedHero->getPassiveDamageReductionPercent();
        $damage = intdiv($damage * (100 - min(100, max(0, $reduction))), 100);

        return parent::receiveDamage($damage);
    }

    public function didDodgeLastDamage(): bool
    {
        return $this->lastDamageDodged;
    }

    public function didConvertLastDamageToHealing(): bool
    {
        return $this->lastDamageConvertedToHealing;
    }

    public function heal(int $amount): int
    {
        $oldHP = $this->getHP();
        $this->setHP($oldHP + max(0, $amount));
        return $this->getHP() - $oldHP;
    }

    public function attack(
        Hero $hero,
        bool $allOut,
        bool $vampireMode,
        int $damageReductionPercent,
        int $bonusDamage = 0
    ): int
    {
        $damage = random_int(0, 50);
        if ($allOut) {
            $damage = intdiv($damage * 3, 2);
        }
        $damage = intdiv($damage * (100 - min(100, max(0, $damageReductionPercent))), 100);
        $damage += max(0, $bonusDamage);

        if ($vampireMode) {
            $this->heal($damage);
            return 0;
        }

        $dealtDamage = $hero->receiveDamage($damage);
        if ($this->copiedHero instanceof Vampire) {
            $this->heal(random_int(0, $dealtDamage));
        }

        return $dealtDamage;
    }

    public function restoreAfterVictory(): void
    {
        $this->setHP($this->getMaxHP());
        $this->setMana(Hero::MAX_MP);
    }
}
