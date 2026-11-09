<?php

// Définit la classe Hero avec :
// 2 propriétés :
// Son nom (unique).
// Ses points de vie.
// 1 méthode :
// frapper un monstre.

class Hero
{
    // Points de vie et points de mana maximum de base.
    public const MAX_HP = 100;
    public const MAX_MP = 30;

    // Informations générales du héros.
    private $name;
    private $HP = 100;
    private $MP = self::MAX_MP;
    private $id;
    private $imagePath;

    // Informations concernant les effets temporaires et les combats.
    private $nextHitDamageReduction = false;
    private $lastAttackDodged = false;
    private $lastDamageConvertedToHealing = 0;
    private $dodgeChanceBasisPoints = 0;
    private $monstersSurvived = 0;
    private $attackDamageReductionPercent = 0;
    private $nextAttackDamageBonus = 0;

    // Améliorations de cartes possédées et actuellement actives.
    private $rogueCardUpgrades = [];
    private $activeRogueCardUpgrades = [];

    /**
     * Liste des améliorations de cartes autorisées pour le héros.
     *
     * Cette constante privée permet de vérifier qu'une amélioration
     * ajoutée au héros existe bien dans la liste prévue.
     */
    private const ROGUE_CARD_UPGRADES = [
        'InvulnerabiltyFire',
        'InvulnerabiltyIce',
        'InvulnerabiltyPoison',
        'ManaRegenerationUpgrade',
        'MaxAllOutUpgrade',
        'MaxDefenseUpgrade',
        'MaxDMGUpgrade',
        'MaxDodgeUpgrade',
        'MaxHealthUpgrade',
        'MaxManaUpgrade',
        'RegenerationUpgrade',
        'VampirismUpgrade'
    ];

    /**
     * Crée un héros en fonction de la classe demandée.
     *
     * Cette méthode statique sert de fabrique : elle permet de créer
     * automatiquement le bon objet sans avoir à instancier directement
     * chaque classe depuis le code qui l'utilise.
     *
     * Certaines classes nécessitent également une vérification du nom.
     *
     * @param string $name Nom du héros.
     * @param string $className Classe du héros à créer.
     *
     * @return Hero Instance de la classe correspondant au choix effectué.
     *
     * @throws InvalidArgumentException Si la classe est inconnue
     * ou si le nom ne respecte pas une restriction.
     */
    public static function create($name, $className)
    {
        switch ($className) {
            case 'mage':
                return new Mage($name);
            case 'guerrier':
                return new Guerrier($name);
            case 'archer':
                return new Archer($name);
            case 'vampire':
                if (strcasecmp(trim($name), 'Jarona') === 0) {
                    return new Flowery($name);
                }
                return new Vampire($name);
            case 'flowery':
                if (strcasecmp(trim($name), 'Jarona') !== 0) {
                    throw new InvalidArgumentException(
                        'Flowery est réservée au héros nommé Jarona.'
                    );
                }
                return new Flowery($name);
            case 'gambler':
                return new Gambler($name);
            case 'werewolf':
                return new Werewolf($name);
            case 'hero':
                return new self($name);
            default:
                throw new InvalidArgumentException('Classe de personnage inconnue.');
        }
    }

    /**
     * Effectue une attaque normale contre un monstre.
     *
     * Les dégâts sont déterminés aléatoirement entre 0 et 50.
     * La méthode dealDamage() se charge ensuite d'appliquer les effets
     * liés aux cartes et à l'attaque.
     *
     * @param Monster $monster Monstre ciblé.
     *
     * @return int Nombre de dégâts réellement infligés.
     */
    public function hit(Monster $monster): int
    {
        return $this->dealDamage($monster, rand(0, 50), true);
    }

    /**
     * Effectue une attaque spéciale de type All Out contre un monstre.
     *
     * Les dégâts sont calculés par rollAllOutDamage().
     * Contrairement à l'attaque normale, cette attaque ne déclenche pas
     * le bonus défensif de la prochaine attaque reçue.
     *
     * @param Monster $monster Monstre ciblé.
     *
     * @return int Nombre de dégâts réellement infligés.
     */
    public function hitAllOut(Monster $monster): int
    {
        $damage = $this->rollAllOutDamage();
        return $this->dealDamage($monster, $damage, false);
    }

    /**
     * Centralise l'application des dégâts infligés par le héros.
     *
     * Applique successivement la réduction des dégâts du héros,
     * les bonus de dégâts des cartes et le bonus de la prochaine attaque.
     * Transmet ensuite les dégâts au monstre.
     *
     * Cette méthode gère également le vampirisme et le bonus défensif
     * éventuel accordé après l'attaque.
     *
     * @param Monster $monster Monstre ciblé.
     * @param int $damage Dégâts de départ.
     * @param bool $grantDefenseBoost Indique si l'attaque doit accorder
     * un bonus défensif pour la prochaine attaque reçue.
     *
     * @return int Nombre de dégâts réellement infligés au monstre.
     */
    private function dealDamage(Monster $monster, int $damage, bool $grantDefenseBoost): int
    {
        $damage = $this->applyRogueCardDamageBonus($this->reduceAttackDamage($damage))
            + $this->nextAttackDamageBonus;
        $this->nextAttackDamageBonus = 0;
        $damage = $monster->receiveDamage($damage);
        $this->healFromRogueVampirism($damage);
        $this->nextHitDamageReduction = $grantDefenseBoost;

        return $damage;
    }

    /**
     * Détermine les dégâts d'une attaque All Out.
     *
     * Si le héros possède l'amélioration MaxAllOutUpgrade,
     * les dégâts sont aléatoires entre 0 et 100.
     * Sinon, les dégâts sont calculés à partir d'un tirage entre 0 et 50,
     * multiplié par 1,5 grâce à intdiv().
     *
     * @return int Dégâts calculés pour l'attaque.
     */
    protected function rollAllOutDamage(): int
    {
        return $this->hasActiveRogueCardUpgrade('MaxAllOutUpgrade')
            ? random_int(0, 100)
            : intdiv(rand(0, 50) * 3, 2);
    }

    /**
     * Applique le bonus de dégâts apporté par une carte du voleur.
     *
     * Si MaxDMGUpgrade est active, les dégâts sont augmentés de 20 %.
     * Sinon, les dégâts restent inchangés.
     *
     * @param int $damage Dégâts initiaux.
     *
     * @return int Dégâts après application du bonus.
     */
    public function applyRogueCardDamageBonus(int $damage): int
    {
        return $this->hasActiveRogueCardUpgrade('MaxDMGUpgrade')
            ? intdiv($damage * 120, 100)
            : $damage;
    }

    /**
     * Applique l'effet de vampirisme du héros.
     *
     * Si l'amélioration VampirismUpgrade est active,
     * le héros récupère des points de vie correspondant aux dégâts infligés.
     *
     * @param int $damage Dégâts infligés au monstre.
     *
     * @return int Points de vie réellement récupérés.
     */
    public function healFromRogueVampirism(int $damage): int
    {
        return $this->hasActiveRogueCardUpgrade('VampirismUpgrade')
            ? $this->restoreHP(max(0, $damage))
            : 0;
    }

    /**
     * Définit la liste des améliorations de cartes possédées par le héros.
     *
     * Vérifie que chaque amélioration appartient à la liste autorisée.
     *
     * @param array $upgrades Liste des améliorations.
     *
     * @return void
     */
    public function setRogueCardUpgrades(array $upgrades): void
    {
        $this->rogueCardUpgrades = $this->validateRogueCardUpgrades($upgrades);
    }

    /**
     * Récupère les améliorations de cartes possédées par le héros.
     *
     * @return array Liste des améliorations.
     */
    public function getRogueCardUpgrades(): array
    {
        return $this->rogueCardUpgrades;
    }

    /**
     * Définit les améliorations de cartes actuellement actives.
     *
     * La liste est vérifiée avant d'être enregistrée.
     *
     * @param array $upgrades Liste des améliorations actives.
     *
     * @return void
     */
    public function setActiveRogueCardUpgrades(array $upgrades): void
    {
        $this->activeRogueCardUpgrades = $this->validateRogueCardUpgrades($upgrades);
    }

    /**
     * Vérifie si une amélioration de carte est actuellement active.
     *
     * Le troisième argument de in_array() à true impose une comparaison stricte.
     *
     * @param string $upgrade Nom de l'amélioration recherchée.
     *
     * @return bool true si l'amélioration est active, false sinon.
     */
    public function hasActiveRogueCardUpgrade(string $upgrade): bool
    {
        return in_array($upgrade, $this->activeRogueCardUpgrades, true);
    }

    /**
     * Vérifie et nettoie une liste d'améliorations de cartes.
     *
     * Chaque élément doit être une chaîne de caractères correspondant
     * à une amélioration autorisée.
     * Les doublons sont supprimés et les index du tableau sont réorganisés.
     *
     * @param array $upgrades Liste des améliorations à vérifier.
     *
     * @return array Liste validée et sans doublons.
     *
     * @throws InvalidArgumentException Si une amélioration est inconnue
     * ou si un élément n'est pas une chaîne de caractères.
     */
    private function validateRogueCardUpgrades(array $upgrades): array
    {
        foreach ($upgrades as $upgrade) {
            if (!is_string($upgrade) || !in_array($upgrade, self::ROGUE_CARD_UPGRADES, true)) {
                throw new InvalidArgumentException('Amélioration de carte inconnue.');
            }
        }

        return array_values(array_unique($upgrades));
    }

    /**
     * Définit un bonus de dégâts qui sera appliqué à la prochaine attaque.
     *
     * Une valeur négative est refusée.
     *
     * @param int $damage Nombre de dégâts supplémentaires.
     *
     * @return void
     *
     * @throws InvalidArgumentException Si le bonus est négatif.
     */
    public function setNextAttackDamageBonus(int $damage): void
    {
        if ($damage < 0) {
            throw new InvalidArgumentException('Le bonus de dégâts ne peut pas être négatif.');
        }

        $this->nextAttackDamageBonus = $damage;
    }

    /**
     * Indique si l'attaque spéciale consomme un tour.
     *
     * Cette méthode peut être redéfinie dans les classes spécialisées.
     *
     * @return bool true si l'attaque spéciale consomme un tour.
     */
    public function specialAttackConsumesTurn(): bool
    {
        return true;
    }

    /**
     * Indique si le héros est immunisé à tous les effets de statut.
     *
     * Cette méthode peut être redéfinie dans une classe enfant.
     *
     * @return bool false par défaut.
     */
    public function isImmuneToStatusEffects(): bool
    {
        return false;
    }

    /**
     * Vérifie si le héros est immunisé à un effet de statut particulier.
     *
     * Vérifie d'abord une éventuelle immunité générale,
     * puis recherche si une carte active protège contre l'effet demandé.
     *
     * @param string $effect Effet de statut à vérifier.
     *
     * @return bool true si le héros est immunisé, false sinon.
     */
    public function isImmuneToStatusEffect(string $effect): bool
    {
        $cardImmunities = [
            'burn' => 'InvulnerabiltyFire',
            'freeze' => 'InvulnerabiltyIce',
            'poison' => 'InvulnerabiltyPoison'
        ];

        return $this->isImmuneToStatusEffects()
            || (
                isset($cardImmunities[$effect])
                && $this->hasActiveRogueCardUpgrade($cardImmunities[$effect])
            );
    }

    /**
     * Réduit les dégâts que le héros inflige lors de ses attaques.
     *
     * Le pourcentage de réduction est limité entre 0 et 100.
     * Les dégâts négatifs sont également ramenés à zéro.
     *
     * @param int $damage Dégâts initiaux.
     *
     * @return int Dégâts après réduction.
     */
    public function reduceAttackDamage(int $damage): int
    {
        $reduction = min(100, max(0, $this->attackDamageReductionPercent));
        return intdiv(max(0, $damage) * (100 - $reduction), 100);
    }

    /**
     * Définit le pourcentage de réduction des dégâts infligés par le héros.
     *
     * La valeur est automatiquement limitée entre 0 et 100.
     *
     * @param int $percent Pourcentage de réduction.
     *
     * @return void
     */
    public function setAttackDamageReductionPercent(int $percent): void
    {
        $this->attackDamageReductionPercent = min(100, max(0, $percent));
    }

    /**
     * Gère les dégâts reçus par le héros.
     *
     * Applique les réductions passives et temporaires,
     * puis l'éventuel bonus de défense apporté par une carte.
     *
     * Si $convertToHealing vaut true, les dégâts sont transformés
     * en points de vie récupérés au lieu de retirer des points de vie.
     *
     * @param int $damage Dégâts reçus.
     * @param bool $convertToHealing Indique si les dégâts doivent devenir des soins.
     *
     * @return int Dégâts réellement subis, ou 0 si les dégâts sont convertis en soins.
     */
    public function receiveDamage(int $damage, bool $convertToHealing = false): int
    {
        $this->lastDamageConvertedToHealing = 0;
        $damageReduction = $this->getPassiveDamageReductionPercent();
        if ($this->nextHitDamageReduction) {
            $damageReduction += 20;
            $this->nextHitDamageReduction = false;
        }
        $damageReduction = min(100, $damageReduction);
        $damage = intdiv($damage * (100 - $damageReduction), 100);
        if ($this->hasActiveRogueCardUpgrade('MaxDefenseUpgrade')) {
            $damage = intdiv($damage * 80, 100);
        }

        if ($convertToHealing) {
            $this->lastDamageConvertedToHealing = $this->restoreHP($damage);
            return 0;
        }

        $damage = min(max(0, $damage), max(0, $this->HP));
        $this->setHP(max(0, $this->getHP() - $damage));
        return $damage;
    }

    /**
     * Récupère la quantité de dégâts précédemment convertis en soins.
     *
     * @return int Nombre de points de vie récupérés grâce à cette conversion.
     */
    public function getLastDamageConvertedToHealing(): int
    {
        return $this->lastDamageConvertedToHealing;
    }

    /**
     * Restaure les points de vie du héros sans dépasser son maximum.
     *
     * Si la quantité de soins demandée dépasse les points de vie manquants,
     * seule la quantité nécessaire pour atteindre le maximum est appliquée.
     *
     * @param int $amount Quantité de points de vie à restaurer.
     *
     * @return int Nombre de points de vie réellement restaurés.
     *
     * @throws InvalidArgumentException Si la quantité de soins est négative.
     */
    public function restoreHP(int $amount): int
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('La quantité de soin ne peut pas être négative.');
        }

        $healed = min($amount, $this->getMaxHP() - $this->HP);
        $this->HP += $healed;

        return $healed;
    }

    /**
     * Récupère le nombre maximum de points de vie du héros.
     *
     * Prend en compte l'éventuel bonus de la carte MaxHealthUpgrade.
     *
     * @return int Points de vie maximum.
     */
    public function getMaxHP(): int
    {
        return $this->applyMaxHealthCardBonus(self::MAX_HP);
    }

    /**
     * Récupère le nombre maximum de points de mana du héros.
     *
     * Prend en compte l'éventuel bonus de la carte MaxManaUpgrade.
     *
     * @return int Points de mana maximum.
     */
    public function getMaxMP(): int
    {
        return $this->applyMaxManaCardBonus(self::MAX_MP);
    }

    /**
     * Applique le bonus de points de vie maximum de la carte correspondante.
     *
     * Si MaxHealthUpgrade est active, le maximum augmente de 50 %.
     *
     * @param int $baseMax Maximum de points de vie de base.
     *
     * @return int Maximum après application du bonus.
     */
    public function applyMaxHealthCardBonus(int $baseMax): int
    {
        return $this->hasActiveRogueCardUpgrade('MaxHealthUpgrade')
            ? $baseMax + intdiv($baseMax, 2)
            : $baseMax;
    }

    /**
     * Applique le bonus de points de mana maximum de la carte correspondante.
     *
     * Si MaxManaUpgrade est active, le maximum augmente de 50 %.
     *
     * @param int $baseMax Maximum de points de mana de base.
     *
     * @return int Maximum après application du bonus.
     */
    public function applyMaxManaCardBonus(int $baseMax): int
    {
        return $this->hasActiveRogueCardUpgrade('MaxManaUpgrade')
            ? $baseMax + intdiv($baseMax, 2)
            : $baseMax;
    }

    /**
     * Récupère la quantité de points de vie volés lors de la dernière attaque.
     *
     * Retourne toujours 0 dans la classe Hero de base.
     * Cette méthode peut être redéfinie dans une classe spécialisée.
     *
     * @return int Points de vie volés.
     */
    public function getLastLifeSteal(): int
    {
        return 0;
    }

    /**
     * Détermine si le héros esquive une attaque.
     *
     * La chance est exprimée en points de base :
     * 100 points de base représentent 1 % et 10 000 représentent 100 %.
     *
     * L'amélioration MaxDodgeUpgrade ajoute 20 points de pourcentage.
     *
     * @param int|null $chanceBasisPoints Chance d'esquive facultative.
     *
     * @return bool true si l'attaque est esquivée, false sinon.
     */
    public function tryDodgeAttack(?int $chanceBasisPoints = null): bool
    {
        $chance = $chanceBasisPoints ?? $this->getDodgeChanceBasisPoints();
        if ($this->hasActiveRogueCardUpgrade('MaxDodgeUpgrade')) {
            $chance += 2000;
        }
        $chance = min($this->getMaximumDodgeChanceBasisPoints(), max(0, $chance));
        $this->lastAttackDodged = $chance > 0 && random_int(1, 10000) <= $chance;

        return $this->lastAttackDodged;
    }

    /**
     * Récupère la chance d'esquive maximale autorisée.
     *
     * Cette méthode protégée peut être redéfinie dans les classes enfants.
     *
     * @return int Chance maximale exprimée en points de base.
     */
    protected function getMaximumDodgeChanceBasisPoints(): int
    {
        return 10000;
    }

    /**
     * Indique si le héros a esquivé la dernière attaque.
     *
     * @return bool true si la dernière attaque a été esquivée.
     */
    public function didDodgeLastAttack(): bool
    {
        return $this->lastAttackDodged;
    }

    /**
     * Réinitialise l'indicateur de la dernière esquive.
     *
     * @return void
     */
    public function resetLastAttackDodged(): void
    {
        $this->lastAttackDodged = false;
    }

    /**
     * Indique si le héros joue avant les autres combattants.
     *
     * Retourne false par défaut.
     * Cette méthode peut être redéfinie dans une classe spécialisée.
     *
     * @return bool true si le héros agit en premier.
     */
    public function actsFirst(): bool
    {
        return false;
    }

    /**
     * Récupère la quantité de mana régénérée à chaque tour.
     *
     * Applique le bonus éventuel de la carte ManaRegenerationUpgrade.
     *
     * @return int Quantité de mana régénérée par tour.
     */
    public function getManaRegenerationPerTurn(): int
    {
        return $this->applyManaRegenerationCardBonus(3);
    }

    /**
     * Applique le bonus de régénération de mana de la carte correspondante.
     *
     * Si ManaRegenerationUpgrade est active, la régénération est doublée.
     *
     * @param int $amount Quantité de mana de base.
     *
     * @return int Quantité de mana après application du bonus.
     */
    protected function applyManaRegenerationCardBonus(int $amount): int
    {
        return $this->hasActiveRogueCardUpgrade('ManaRegenerationUpgrade')
            ? $amount * 2
            : $amount;
    }

    /**
     * Récupère le pourcentage de réduction passive des dégâts.
     *
     * Retourne 0 par défaut.
     * Cette méthode peut être redéfinie dans les classes spécialisées.
     *
     * @return int Pourcentage de réduction passive.
     */
    public function getPassiveDamageReductionPercent(): int
    {
        return 0;
    }

    /**
     * Convertit la chance d'esquive exprimée en points de base en pourcentage.
     *
     * @return int Chance d'esquive exprimée en pourcentage entier.
     */
    public function getDodgeChancePercent(): int
    {
        return intdiv($this->getDodgeChanceBasisPoints(), 100);
    }

    /**
     * Récupère la chance d'esquive du héros en points de base.
     *
     * @return int Chance d'esquive en points de base.
     */
    public function getDodgeChanceBasisPoints(): int
    {
        return $this->dodgeChanceBasisPoints;
    }

    /**
     * Définit la chance d'esquive du héros en points de base.
     *
     * Une valeur négative est refusée.
     *
     * @param int $chance Chance d'esquive en points de base.
     *
     * @return void
     *
     * @throws InvalidArgumentException Si la chance est négative.
     */
    public function setDodgeChanceBasisPoints(int $chance): void
    {
        if ($chance < 0) {
            throw new InvalidArgumentException('La chance d’esquive ne peut pas être négative.');
        }

        $this->dodgeChanceBasisPoints = $chance;
    }

    /**
     * Augmente la chance d'esquive actuelle de 25 %.
     *
     * Le nouveau résultat est arrondi à l'entier le plus proche,
     * puis enregistré dans les propriétés du héros.
     *
     * @return int Nouvelle chance d'esquive en points de base.
     */
    public function increaseDodgeChanceBy25Percent(): int
    {
        $newChance = (int) round($this->getDodgeChanceBasisPoints() * 1.25);
        $this->setDodgeChanceBasisPoints($newChance);

        return $newChance;
    }

    /**
     * Récupère le nom de l'attaque spéciale du héros.
     *
     * Cette méthode peut être redéfinie dans une classe spécialisée.
     *
     * @return string Nom de l'attaque spéciale.
     */
    public function getSpecialAttackName(): string
    {
        return 'Attaque spéciale';
    }

    /**
     * Récupère le coût en mana de l'attaque spéciale.
     *
     * @return int Coût en points de mana.
     */
    public function getSpecialAttackCost(): int
    {
        return 0;
    }

    /**
     * Vérifie si le héros peut utiliser son attaque spéciale.
     *
     * La classe Hero autorise l'utilisation par défaut.
     * Les classes spécialisées peuvent ajouter leurs propres conditions.
     *
     * @param array $fightState État actuel du combat.
     *
     * @return bool true si l'attaque spéciale peut être utilisée.
     */
    public function canUseSpecialAttack(array $fightState): bool
    {
        return true;
    }

    /**
     * Exécute l'attaque spéciale du héros.
     *
     * La classe Hero de base ne possède pas d'attaque spéciale.
     * Cette méthode doit donc être redéfinie dans les classes spécialisées.
     *
     * Le paramètre $fightState est passé par référence afin que la méthode
     * puisse modifier directement l'état du combat.
     *
     * @param Monster $monster Monstre ciblé.
     * @param array &$fightState État du combat, modifiable par la méthode.
     *
     * @return array Résultat de l'attaque spéciale.
     *
     * @throws LogicException Si cette méthode est appelée sur un héros
     * ne possédant pas d'attaque spéciale.
     */
    public function useSpecialAttack(Monster $monster, array &$fightState): array
    {
        throw new LogicException('Cette classe de héros ne possède pas d’attaque spéciale.');
    }

    /**
     * Récupère la probabilité qu'une attaque de gel échoue.
     *
     * Valeur par défaut : 25 %.
     *
     * @return int Pourcentage de chance d'échec.
     */
    public function getFreezeMissChancePercent(): int
    {
        return 25;
    }

    /**
     * Récupère les dégâts de poison infligés à chaque tour.
     *
     * Valeur par défaut : 5.
     *
     * @return int Dégâts de poison par tour.
     */
    public function getPoisonDamagePerTurn(): int
    {
        return 5;
    }

    /**
     * Récupère le pourcentage de croissance des dégâts de brûlure.
     *
     * Valeur par défaut : 125 %.
     *
     * @return int Pourcentage de croissance.
     */
    public function getBurnGrowthPercent(): int
    {
        return 125;
    }

    /**
     * Récupère la description de l'effet passif du héros.
     *
     * @return string Description de l'effet passif.
     */
    public function getPassiveDescription(): string
    {
        return 'Aucun effet passif.';
    }

    /**
     * Récupère les points de mana actuels du héros.
     *
     * @return int Points de mana actuels.
     */
    public function getMP(): int
    {
        return $this->MP;
    }

    /**
     * Modifie les points de mana du héros.
     *
     * La valeur est limitée entre 0 et le maximum de mana autorisé.
     *
     * @param int $MP Nouvelle quantité de mana.
     *
     * @return void
     */
    public function setMP(int $MP): void
    {
        $this->MP = min($this->getMaxMP(), max(0, $MP));
    }

    /**
     * Régénère les points de mana du héros.
     *
     * Utilise setMP() pour empêcher le mana de dépasser son maximum.
     *
     * @param int $amount Quantité de mana à régénérer (3 par défaut).
     *
     * @return void
     *
     * @throws InvalidArgumentException Si la quantité est négative.
     */
    public function regenerateMP(int $amount = 3): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('La régénération de MP ne peut pas être négative.');
        }

        $this->setMP($this->MP + $amount);
    }

    /**
     * Dépense une quantité de points de mana.
     *
     * Vérifie d'abord que le coût n'est pas négatif et que le héros
     * possède suffisamment de mana avant de retirer les points.
     *
     * @param int $amount Quantité de mana à dépenser.
     *
     * @return bool true si le mana a été dépensé, false si le héros
     * ne possède pas assez de mana.
     *
     * @throws InvalidArgumentException Si le coût est négatif.
     */
    public function spendMP(int $amount): bool
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le coût en MP ne peut pas être négatif.');
        }

        if ($this->MP < $amount) {
            return false;
        }

        $this->MP -= $amount;
        return true;
    }

    /**
     * Initialise un nouveau héros avec son nom.
     *
     * @param string $name Nom du héros.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Récupère le nom du héros.
     *
     * @return string Nom du héros.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Récupère les points de vie actuels du héros.
     *
     * @return int Points de vie actuels.
     */
    public function getHP()
    {
        return $this->HP;
    }

    /**
     * Récupère l'identifiant du héros.
     *
     * @return int Identifiant du héros.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Récupère le nombre de monstres vaincus par le héros.
     *
     * @return int Nombre de monstres vaincus.
     */
    public function getMonstersSurvived(): int
    {
        return $this->monstersSurvived;
    }

    /**
     * Définit le nombre de monstres vaincus par le héros.
     *
     * Une valeur négative est refusée.
     *
     * @param int $count Nombre de monstres vaincus.
     *
     * @return void
     *
     * @throws InvalidArgumentException Si le nombre est négatif.
     */
    public function setMonstersSurvived(int $count): void
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Le nombre de monstres ne peut pas être négatif.');
        }

        $this->monstersSurvived = $count;
    }

    /**
     * Incrémente le nombre de monstres vaincus de 1.
     *
     * @return void
     */
    public function surviveMonster(): void
    {
        $this->monstersSurvived++;
    }

    /**
     * Récupère le chemin de l'image du héros.
     *
     * @return string|null Chemin de l'image, ou null si aucun chemin n'est défini.
     */
    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    /**
     * Récupère le nom technique de la classe du héros.
     *
     * Cette méthode peut être redéfinie dans les classes spécialisées.
     *
     * @return string Nom technique de la classe.
     */
    public function getClassName()
    {
        return 'hero';
    }

    /**
     * Récupère le nom affiché de la classe du héros.
     *
     * Cette méthode peut être redéfinie dans les classes spécialisées.
     *
     * @return string Nom affiché de la classe.
     */
    public function getClassLabel()
    {
        return 'Héros';
    }

    /**
     * Modifie le nom du héros.
     *
     * @param string $name Nouveau nom du héros.
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Modifie les points de vie du héros.
     *
     * @param int $HP Nouvelle quantité de points de vie.
     *
     * @return void
     */
    public function setHP($HP)
    {
        $this->HP = $HP;
    }

    /**
     * Modifie l'identifiant du héros.
     *
     * @param int $id Nouvel identifiant.
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Modifie le chemin de l'image du héros.
     *
     * @param string|null $imagePath Nouveau chemin de l'image.
     *
     * @return void
     */
    public function setImagePath(?string $imagePath): void
    {
        $this->imagePath = $imagePath;
    }
}

?>