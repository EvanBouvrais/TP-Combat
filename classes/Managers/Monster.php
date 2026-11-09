<?php

// Définit la classe Monster, qui représente un monstre dans le jeu.
// Elle gère ses points de vie, ses attaques, les dégâts qu'elle reçoit
// ainsi que certains effets passifs pouvant être redéfinis par ses classes enfants.

class Monster {

    // Nom du monstre.
    private $name;

    // Points de vie actuels du monstre.
    private $HP;

    // Nombre maximum de points de vie du monstre.
    private $maxHP;

    // Indique si la dernière attaque du monstre a été ratée.
    private $lastAttackMissed = false;

    // Indique si le monstre a esquivé les derniers dégâts reçus.
    // protected permet aux classes enfants d'accéder à cette propriété.
    protected $lastDamageDodged = false;

    // Indique si le monstre a ressuscité lors de sa dernière mort.
    protected $lastResurrected = false;


    /**
     * Effectue une attaque contre un héros.
     *
     * @param Hero $hero Héros ciblé par l'attaque.
     * @param int $damageReductionPercent Pourcentage de réduction des dégâts du héros.
     * @param bool $doubleDamage Indique si les dégâts doivent être doublés.
     * @param bool $convertToHealing Indique si les dégâts reçus doivent être convertis en soins.
     * @param ?int $dodgeChanceBasisPoints Probabilité d'esquive du héros, exprimée en points de base.
     * @param bool $allOut Indique si l'attaque utilise le multiplicateur All Out.
     *
     * @return int Nombre de dégâts réellement infligés au héros.
     */
    public function hit(
        Hero $hero,
        int $damageReductionPercent = 0,
        bool $doubleDamage = false,
        bool $convertToHealing = false,
        ?int $dodgeChanceBasisPoints = null,
        bool $allOut = false
    ): int
    {
        // Réinitialise les informations concernant la précédente attaque.
        $this->lastAttackMissed = false;
        $this->lastResurrected = false;

        // Réinitialise l'indicateur d'esquive du héros.
        $hero->resetLastAttackDodged();

        // Vérifie si le monstre rate son attaque.
        if ($this->rollAttackMiss()) {
            $this->lastAttackMissed = true;
            return 0;
        }

        // Vérifie si le héros esquive l'attaque.
        if ($hero->tryDodgeAttack($dodgeChanceBasisPoints)) {
            return 0;
        }

        // Génère aléatoirement des dégâts compris entre 0 et 50.
        $damage = rand(0, 50);

        // Applique le multiplicateur correspondant au type d'attaque.
        if ($allOut) {
            // Augmente les dégâts de 50 % en utilisant une division entière.
            $damage = intdiv($damage * 3, 2);
        } elseif ($doubleDamage) {
            // Double les dégâts si l'attaque bénéficie de cet effet.
            $damage *= 2;
        }

        // Ajoute le bonus de dégâts du monstre.
        $damage = $this->applyDamageBonus($damage);

        // Applique le multiplicateur de dégâts éventuellement défini
        // dans une classe enfant.
        $damage = $this->applyDamageMultiplier($damage);

        // Limite la réduction des dégâts entre 0 % et 100 %.
        $damageReductionPercent = min(100, max(0, $damageReductionPercent));

        // Réduit les dégâts en fonction du pourcentage fourni.
        // intdiv() permet de conserver un résultat entier.
        $damage = intdiv($damage * (100 - $damageReductionPercent), 100);

        // Transmet les dégâts au héros et retourne les dégâts réellement reçus.
        return $hero->receiveDamage($damage, $convertToHealing);
    }


    /**
     * Reçoit des dégâts provenant d'une attaque.
     *
     * @param int $damage Quantité de dégâts à recevoir.
     *
     * @return int Nombre de dégâts réellement subis.
     */
    public function receiveDamage(int $damage): int
    {
        // Réinitialise l'indicateur d'esquive avant de traiter les dégâts.
        $this->lastDamageDodged = false;

        // Vérifie si le monstre esquive les dégâts.
        if ($this->shouldDodgeDamage()) {
            $this->lastDamageDodged = true;
            return 0;
        }

        // Applique les dégâts aux points de vie du monstre.
        return $this->applyReceivedDamage($damage);
    }


    /**
     * Reçoit des dégâts sur la durée (Damage Over Time).
     *
     * Contrairement à receiveDamage(), cette méthode ne vérifie pas
     * si le monstre peut esquiver les dégâts.
     *
     * @param int $damage Quantité de dégâts à recevoir.
     *
     * @return int Nombre de dégâts réellement subis.
     */
    public function receiveDamageOverTime(int $damage): int
    {
        // Réinitialise l'indicateur d'esquive.
        $this->lastDamageDodged = false;

        // Applique directement les dégâts sans vérifier l'esquive.
        return $this->applyReceivedDamage($damage);
    }


    /**
     * Applique effectivement les dégâts aux points de vie du monstre.
     *
     * @param int $damage Quantité de dégâts à appliquer.
     *
     * @return int Nombre de dégâts réellement retirés.
     */
    private function applyReceivedDamage(int $damage): int
    {
        // Empêche les dégâts négatifs et limite les dégâts aux HP restants.
        // Un monstre ne peut donc pas perdre plus de points de vie qu'il n'en possède.
        $damage = min(max(0, $damage), max(0, $this->HP));

        // Retire les dégâts des points de vie sans jamais descendre sous 0.
        $this->HP = max(0, $this->HP - $damage);

        // Retourne la quantité de dégâts réellement appliquée.
        return $damage;
    }


    /**
     * Détermine si le monstre esquive les dégâts reçus.
     *
     * Cette méthode retourne false par défaut.
     * Elle est protected afin que les classes enfants puissent la redéfinir
     * pour ajouter leur propre mécanique d'esquive.
     *
     * @return bool true si les dégâts sont esquivés, false sinon.
     */
    protected function shouldDodgeDamage(): bool
    {
        return false;
    }


    /**
     * Réinitialise l'indicateur indiquant si le dernier dégât a été esquivé.
     */
    public function resetLastDamageDodged(): void
    {
        $this->lastDamageDodged = false;
    }


    /**
     * Indique si le monstre a esquivé les derniers dégâts reçus.
     *
     * @return bool true si les dégâts ont été esquivés, false sinon.
     */
    public function didDodgeLastDamage(): bool
    {
        return $this->lastDamageDodged;
    }


    /**
     * Effectue une attaque spéciale au début du combat.
     *
     * Cette attaque possède des dégâts différents de l'attaque classique :
     * - Sans All Out, les dégâts sont divisés par deux.
     * - Avec All Out, les dégâts sont augmentés de 50 %.
     *
     * Les dégâts sont également limités afin que le héros conserve
     * au moins 1 point de vie après cette attaque.
     *
     * @param Hero $hero Héros ciblé par l'attaque.
     * @param bool $allOut Indique si l'attaque utilise le multiplicateur All Out.
     *
     * @return int Nombre de dégâts réellement infligés au héros.
     */
    public function hitAtFightStart(Hero $hero, bool $allOut = false): int
    {
        // Réinitialise les informations concernant la précédente attaque.
        $this->lastAttackMissed = false;
        $this->lastResurrected = false;

        // Réinitialise l'indicateur d'esquive du héros.
        $hero->resetLastAttackDodged();

        // Vérifie si le monstre rate son attaque.
        if ($this->rollAttackMiss()) {
            $this->lastAttackMissed = true;
            return 0;
        }

        // Vérifie si le héros esquive l'attaque.
        if ($hero->tryDodgeAttack()) {
            return 0;
        }

        // Calcule les dégâts en fonction du type d'attaque.
        $damage = $allOut
            ? intdiv(rand(0, 50) * 3, 2)
            : intdiv(rand(0, 50), 2);

        // Applique le bonus de dégâts du monstre.
        $damage = $this->applyDamageBonus($damage);

        // Applique le multiplicateur de dégâts éventuellement défini
        // dans une classe enfant.
        $damage = $this->applyDamageMultiplier($damage);

        // Limite les dégâts pour que le héros conserve au moins 1 HP.
        // max(0, ...) évite également de générer des dégâts négatifs.
        $damage = min($damage, max(0, $hero->getHP() - 1));

        // Transmet les dégâts au héros et retourne les dégâts réellement reçus.
        return $hero->receiveDamage($damage);
    }


    /**
     * Détermine si le monstre rate son attaque.
     *
     * La probabilité de rater est récupérée grâce à getMissChancePercent().
     * Elle est ensuite limitée entre 0 % et 100 %.
     *
     * @return bool true si l'attaque est ratée, false sinon.
     */
    protected function rollAttackMiss(): bool
    {
        // Empêche une probabilité inférieure à 0 % ou supérieure à 100 %.
        $missChance = min(100, max(0, $this->getMissChancePercent()));

        // Génère un nombre entre 1 et 100 et vérifie s'il correspond
        // à la probabilité de rater l'attaque.
        return $missChance > 0 && random_int(1, 100) <= $missChance;
    }


    /**
     * Augmente les dégâts en fonction du bonus de dégâts du monstre.
     *
     * @param int $damage Dégâts initiaux.
     *
     * @return int Dégâts après application du bonus.
     */
    private function applyDamageBonus(int $damage): int
    {
        // Récupère le bonus de dégâts et empêche qu'il soit négatif.
        $bonusPercent = max(0, $this->getDamageBonusPercent());

        // Applique le pourcentage de bonus aux dégâts.
        // Par exemple, avec 20 % de bonus, 50 dégâts deviennent 60.
        return intdiv($damage * (100 + $bonusPercent), 100);
    }


    /**
     * Applique un multiplicateur supplémentaire aux dégâts.
     *
     * Par défaut, aucun multiplicateur n'est appliqué.
     * Cette méthode protected peut être redéfinie par les classes enfants
     * pour personnaliser leurs dégâts.
     *
     * @param int $damage Dégâts avant application du multiplicateur.
     *
     * @return int Dégâts après application du multiplicateur.
     */
    protected function applyDamageMultiplier(int $damage): int
    {
        return $damage;
    }


    /**
     * Retourne le pourcentage de bonus de dégâts du monstre.
     *
     * Par défaut, le monstre ne possède aucun bonus.
     * Les classes enfants peuvent redéfinir cette méthode.
     *
     * @return int Pourcentage de bonus de dégâts.
     */
    public function getDamageBonusPercent(): int
    {
        return 0;
    }


    /**
     * Retourne la probabilité que le monstre rate son attaque.
     *
     * Par défaut, cette probabilité est de 0 %.
     * Les classes enfants peuvent redéfinir cette méthode.
     *
     * @return int Pourcentage de chances de rater une attaque.
     */
    public function getMissChancePercent(): int
    {
        return 0;
    }


    /**
     * Retourne une description de l'effet passif du monstre.
     *
     * Par défaut, aucun effet passif particulier n'est appliqué.
     * Les classes enfants peuvent fournir leur propre description.
     *
     * @return string Description de l'effet passif.
     */
    public function getPassiveDescription(): string
    {
        return 'Aucun effet passif.';
    }


    /**
     * Indique si la dernière attaque du monstre a été ratée.
     *
     * @return bool true si la dernière attaque a été ratée, false sinon.
     */
    public function didMissLastAttack(): bool
    {
        return $this->lastAttackMissed;
    }


    /**
     * Indique si le monstre a ressuscité lors de sa dernière mort.
     *
     * @return bool true si le monstre a ressuscité, false sinon.
     */
    public function didResurrectLastDeath(): bool
    {
        return $this->lastResurrected;
    }


    /**
     * Constructeur de la classe Monster.
     *
     * Initialise le nom du monstre et ses points de vie.
     * Si aucun maximum de points de vie n'est fourni, la valeur par défaut est 100.
     *
     * @param mixed $name Nom du monstre.
     * @param int $maxHP Nombre maximum de points de vie.
     *
     * @throws InvalidArgumentException Si le nombre maximum de HP est inférieur à 1.
     */
    public function __construct($name, int $maxHP = 100) {

        // Enregistre le nom du monstre.
        $this->name = $name;

        // Vérifie que le monstre possède au moins 1 point de vie maximum.
        if ($maxHP < 1) {
            throw new InvalidArgumentException('Les HP maximums doivent être positifs.');
        }

        // Initialise les points de vie maximum et actuels.
        $this->maxHP = $maxHP;
        $this->HP = $maxHP;
    }


    /**
     * Retourne le nom du monstre.
     *
     * @return mixed Nom du monstre.
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Retourne le nom générique de la classe du personnage.
     *
     * Cette méthode peut être redéfinie par une classe enfant
     * pour retourner un nom plus précis.
     *
     * @return string Nom de la classe du personnage.
     */
    public function getClassName() {
        return 'Monstre';
    }


    /**
     * Retourne les points de vie actuels du monstre.
     *
     * @return int|float Valeur actuelle des points de vie.
     */
    public function getHP() {
        return $this->HP;
    }


    /**
     * Retourne le nombre maximum de points de vie du monstre.
     *
     * @return int Nombre maximum de points de vie.
     */
    public function getMaxHP(): int {
        return $this->maxHP;
    }


    /**
     * Modifie le nom du monstre.
     *
     * @param mixed $name Nouveau nom du monstre.
     */
    public function setName($name) {
        $this->name = $name;
    }


    /**
     * Modifie les points de vie actuels du monstre.
     *
     * La valeur est convertie en entier puis limitée entre 0
     * et le nombre maximum de points de vie.
     *
     * @param mixed $HP Nouveau nombre de points de vie.
     */
    public function setHP($HP) {
        $this->HP = min($this->maxHP, max(0, (int) $HP));
    }

}

?>