<?php

class Gambler extends Hero
{
    private $batMode = false;
    private $lastLifeSteal = 0;
    private $lastVampiricDamage = 0;

    public function getClassName()
    {
        return 'gambler';
    }

    public function getClassLabel()
    {
        return 'Gambler';
    }

    public function getPassiveDescription(): string
    {
        return 'Restaure tous ses HP et MP après chaque victoire.';
    }

    public function getSpecialAttackName(): string
    {
        return 'All In';
    }

    public function getSpecialAttackCost(): int
    {
        return 30;
    }

    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        $outcomes = [
            'instakill',
            'jackpot',
            'magic',
            'reroll',
            'unlucky',
            'unlucky_magic',
            'mimic'
        ];
        $outcome = $outcomes[array_rand($outcomes)];

        switch ($outcome) {
            case 'instakill':
                $monster->setHP(0);
                return ['All In — Instakill : ' . $monster->getName() . ' est vaincu d’un coup.'];

            case 'jackpot':
                $damage = $this->applyRogueCardDamageBonus(random_int(50, 100));
                $dealtDamage = $monster->receiveDamage($damage);
                return [
                    'All In — Jackpot : inflige ' . $dealtDamage . ' dégâts au monstre.'
                ];

            case 'magic':
                $statuses = ['freeze', 'poison', 'burn', 'vampirism'];
                $status = $statuses[array_rand($statuses)];
                $fightState['gambler_pending_monster_status'] = $status;
                return ['All In — Magic : un effet de statut aléatoire vise le monstre.'];

            case 'reroll':
                $fightState['gambler_reroll'] = true;
                return ['All In — Reroll : le monstre adverse change de type.'];

            case 'unlucky':
                $damage = random_int(0, 50);
                $this->setHP(max(1, $this->getHP() - $damage));
                return [
                    'All In — Unlucky : le Gambler subit ' . $damage . ' dégâts.'
                ];

            case 'unlucky_magic':
                $statuses = ['freeze', 'poison', 'burn', 'vampirism'];
                $fightState['gambler_pending_hero_status'] = $statuses[array_rand($statuses)];
                return ['All In — Unlucky Magic : le Gambler reçoit un effet de statut aléatoire.'];

            case 'mimic':
                return $this->mimicRandomSpecialAttack($fightState);
        }

        throw new LogicException('Résultat All In inconnu.');
    }

    public function onMonsterDefeated(): string
    {
        $this->setHP($this->getMaxHP());
        $this->setMP($this->getMaxMP());

        return 'Addiction restaure tous les HP et MP du Gambler.';
    }

    public function setBatMode(bool $enabled): void
    {
        $this->batMode = $enabled;
    }

    public function getLastVampiricDamage(): int
    {
        return $this->lastVampiricDamage;
    }

    public function getLastLifeSteal(): int
    {
        return $this->lastLifeSteal;
    }

    public function hit(Monster $monster): int
    {
        if (!$this->batMode) {
            $this->lastVampiricDamage = 0;
            return parent::hit($monster);
        }

        return $this->useBatMode();
    }

    public function hitAllOut(Monster $monster): int
    {
        if (!$this->batMode) {
            $this->lastVampiricDamage = 0;
            return parent::hitAllOut($monster);
        }

        return $this->useBatMode($this->rollAllOutDamage());
    }

    private function useBatMode(?int $damage = null): int
    {
        if ($damage === null) {
            $damage = random_int(0, 50);
        }

        $this->lastVampiricDamage = $this->applyRogueCardDamageBonus(
            $this->reduceAttackDamage($damage)
        );
        $this->lastLifeSteal = $this->restoreHP($this->lastVampiricDamage);

        return 0;
    }

    private function mimicRandomSpecialAttack(array &$fightState): array
    {
        $classes = ['Mage', 'Guerrier', 'Archer', 'Vampire'];
        $className = $classes[array_rand($classes)];

        switch ($className) {
            case 'Mage':
                $healed = $this->restoreHP(60);
                return ['All In — Mimic (Mage) : restaure ' . $healed . ' HP.'];

            case 'Guerrier':
                $fightState['convert_next_damage_to_healing'] = true;
                return ['All In — Mimic (Guerrier) : la prochaine attaque reçue soigne le Gambler.'];

            case 'Archer':
                $this->setDodgeChanceBasisPoints(
                    max(2000, $this->getDodgeChanceBasisPoints())
                );
                $chance = $this->increaseDodgeChanceBy25Percent();
                return [
                    'All In — Mimic (Archer) : son esquive passe à '
                    . intdiv($chance, 100) . ' %.'
                ];

            case 'Vampire':
                $fightState['vampire_bat_turns'] = 4;
                return [
                    'All In — Mimic (Vampire) : active le mode vampirique pour les prochaines attaques et ripostes.'
                ];
        }

        throw new LogicException('Classe copiée inconnue.');
    }
}
