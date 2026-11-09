<?php

    // Définit la classe FightsManager qui stocke les données du combat et comporte ces fonctionnalités :
    // Créer automatiquement un monstre.
    // Déclencher un combat et obtenir les résultats du combat.

    class FightsManager {

        //défini les classes de monstres
        private function getMonsterClasses(): array
        {
            return [
                'Ogre' => Ogre::class,
                'Sorcier' => Sorcier::class,
                'Fantassin' => Fantassin::class,
                'Angel' => Angel::class,
                'Evil Ant' => EvilAnt::class
            ];
        }

        //defini les classes de boss
        private function getBossMonsterClasses(): array
        {
            return [
                'Ditto' => Ditto::class,
                'Nuclear Warhead of Doom and Despair' => NuclearWarhead::class,
                'NoWell' => NoWell::class,
                'Mario Neutral Air' => MarioNeutralAir::class,
                'Ouroboros' => Ouroboros::class
            ];
        }

        
        /**
         * Retourne les noms des classes de monstres ordinaires disponibles.
         * La liste est construite à partir des clés du tableau getMonsterClasses().
         *
         * @return array Liste des noms de monstres utilisables.
         */

        
        public function getAvailableMonsterClasses(): array
        {
            return array_keys($this->getMonsterClasses());
        }

        /**
         * Retourne les noms des classes de boss disponibles.
         * La liste est construite à partir des clés du tableau getBossMonsterClasses().
         *
         * @return array Liste des noms de boss utilisables.
         */

        public function getAvailableBossMonsterClasses(): array
        {
            return array_keys($this->getBossMonsterClasses());
        }

        /**
         * Vérifie si le monstre fourni appartient à l'une des classes de boss reconnues.
         *
         * @param Monster $monster Monstre dont on veut vérifier la catégorie.
         * @return bool true si le monstre est un boss, false sinon.
         */

        public function isBossMonster(Monster $monster): bool
        {
            return $monster instanceof Ditto
                || $monster instanceof NuclearWarhead
                || $monster instanceof NoWell
                || $monster instanceof MarioNeutralAir
                || $monster instanceof Ouroboros;
        }

        /**
         * Crée une instance de monstre ou de boss à partir de son nom.
         * Peut choisir aléatoirement un monstre ordinaire et restaurer certaines
         * données particulières lorsqu'un boss doit reprendre un combat.
         *
         * @param string|null $className Nom de la classe à créer ; null pour un choix aléatoire.
         * @param string|null $excludedClassName Nom d'une classe à exclure du choix aléatoire.
         * @param Hero|null $hero Héros nécessaire notamment à la création de Ditto.
         * @param array $fightState Données persistantes nécessaires à la restauration de certains monstres.
         * @return Monster Instance du monstre ou du boss créé.
         * @throws InvalidArgumentException Si Ditto n'a pas de héros à copier ou si la classe est inconnue.
         */

        public function createMonster(
            $className = null,
            ?string $excludedClassName = null,
            ?Hero $hero = null,
            array $fightState = []
        ) {
            $monsterClasses = $this->getMonsterClasses();
            $bossMonsterClasses = $this->getBossMonsterClasses();
            if ($className === 'Ditto' && isset($bossMonsterClasses[$className])) {
                if ($hero === null) {
                    throw new InvalidArgumentException('Ditto doit connaître la classe du héros à copier.');
                }
                $ditto = new Ditto($hero);
                $ditto->restoreState($fightState);
                return $ditto;
            }

            if ($className === 'Nuclear Warhead of Doom and Despair') {
                return new NuclearWarhead(
                    isset($fightState[NuclearWarhead::STATE_KEY])
                        ? (int) $fightState[NuclearWarhead::STATE_KEY]
                        : null
                );
            }

            if ($className === 'Ouroboros') {
                return new Ouroboros(
                    (int) ($fightState[Ouroboros::HP_BONUS_STATE_KEY] ?? 0)
                );
            }

            if (isset($bossMonsterClasses[$className])) {
                $bossClass = $bossMonsterClasses[$className];
                return new $bossClass();
            }

            if ($className === null) {
                $availableClasses = array_diff_key(
                    $monsterClasses,
                    $excludedClassName === null ? [] : [$excludedClassName => true]
                );
                $className = array_rand($availableClasses);
            }

            if (!isset($monsterClasses[$className])) {
                throw new InvalidArgumentException('Classe de monstre inconnue.');
            }

            $monsterClass = $monsterClasses[$className];
            return new $monsterClass();

        }

        /**
         * Prépare et exécute la première séquence d'un combat.
         * Applique les effets d'ouverture des boss, puis détermine qui agit en premier
         * et renvoie les événements produits sous forme de lignes de journal.
         *
         * @param Hero $hero Héros participant au combat.
         * @param Monster $monster Monstre ou boss affronté.
         * @param array &$heroEffects Effets de statut actifs sur le héros, modifiés par référence.
         * @param array &$effects Effets de statut actifs sur le monstre, modifiés par référence.
         * @param array &$fightState Données d'état conservées entre les tours, modifiées par référence.
         * @return array Messages décrivant les événements de cette séquence.
         */

        public function startFight(
            Hero $hero,
            Monster $monster,
            array &$heroEffects = [],
            array &$effects = [],
            array &$fightState = []
        ): array
        {
            $combatLog = [];
            $combatLog = array_merge(
                $combatLog,
                $this->applyBossOpeningStatus($monster, $hero, $heroEffects)
            );

            if ($monster instanceof NuclearWarhead) {
                $fightState[NuclearWarhead::STATE_KEY] = $monster->getTurnsRemaining();
                return [
                    $monster->getName()
                    . ' est armée : la bombe explosera dans '
                    . $monster->getTurnsRemaining() . ' tours.'
                ];
            }

            if ($monster instanceof Ditto && $monster->actsFirst() && !$hero->actsFirst()) {
                return $this->playDittoTurn($monster, $hero, $effects, $fightState, $heroEffects);
            }

            if ($hero->actsFirst()) {
                $damage = $hero->hit($monster);
                if ($monster->didDodgeLastDamage()) {
                    $combatLog[] = $monster->getName() . ' esquive l’attaque du héros.';
                } else {
                    $combatLog = array_merge(
                        $combatLog,
                        $this->formatAttackLog(
                            $hero->getName(),
                            'attaque en premier',
                            $damage,
                            $monster->getName(),
                            $monster->getHP()
                        )
                    );
                }
                $this->appendLifeStealLog($hero, $combatLog);
                $this->appendResurrectionLog($monster, $combatLog);

                if ($monster->getHP() <= 0) {
                    return $combatLog;
                }
            }

            if ($monster instanceof Ditto) {
                if ($monster->didConvertLastDamageToHealing()) {
                    $combatLog[] = 'Ditto convert l’attaque en soins.';
                }
                return array_merge(
                    $combatLog,
                    $this->playDittoTurn($monster, $hero, $effects, $fightState, $heroEffects)
                );
            }

            $usesNeutralAir = $monster instanceof MarioNeutralAir
                && $monster->usesNeutralAir();
            $damage = $monster->hitAtFightStart($hero, $usesNeutralAir);
            if ($monster->didMissLastAttack()) {
                $combatLog[] = $monster->getName() . ' rate sa première attaque.';
            } elseif ($hero->didDodgeLastAttack()) {
                $combatLog[] = $hero->getName() . ' esquive la première attaque du monstre.';
            } else {
                $combatLog = array_merge(
                    $combatLog,
                    $this->formatAttackLog(
                        $monster->getName(),
                        $usesNeutralAir ? 'utilise Neutral Air' : 'attaque',
                        $damage,
                        $hero->getName(),
                        $hero->getHP()
                    )
                );
                $this->tryApplySorcererStatus($monster, $hero, $heroEffects, $combatLog);
            }
            $combatLog = array_merge(
                $combatLog,
                $this->tryApplyNoWellSnowgrave(
                    $monster,
                    $hero,
                    $heroEffects,
                    !$monster->didMissLastAttack() && !$hero->didDodgeLastAttack()
                )
            );

            return $combatLog;
        }

        /**
         * Fait jouer un tour à Ditto en choisissant aléatoirement l'une de ses actions.
         * Gère le coût en mana, les attaques, les effets, les compétences copiées
         * et la sauvegarde de son état pour les tours suivants.
         *
         * @param Ditto $ditto Boss Ditto qui joue son tour.
         * @param Hero $hero Héros ciblé par les actions de Ditto.
         * @param array &$effects Effets actifs sur Ditto, modifiés par référence.
         * @param array &$fightState État du combat et données propres à Ditto, modifiés par référence.
         * @param array &$heroEffects Effets de statut actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant l'action de Ditto.
         */

        private function playDittoTurn(
            Ditto $ditto,
            Hero $hero,
            array &$effects,
            array &$fightState,
            array &$heroEffects
        ): array {
            $combatLog = [];

            if (
                !empty($effects['freeze'])
                && random_int(1, 100) <= ($effects['freeze_miss_chance_percent'] ?? 25)
            ) {
                $combatLog[] = 'Ditto est gelé et ne peut pas agir ce tour.';
            } else {
                $actions = ['attack', 'all_out', 'effect', 'special'];
                $action = $actions[array_rand($actions)];
                $actionCost = $action === 'effect'
                    ? 10
                    : ($action === 'special' ? $ditto->getSpecialAttackCost() : 0);

                if ($action === 'special' && !$this->canDittoUseSpecialAttack($ditto, $fightState)) {
                    $combatLog[] = 'Ditto choisit une compétence active déjà indisponible et perd son tour.';
                } elseif (!$ditto->spendMana($actionCost)) {
                    $combatLog[] = 'Ditto tente une action qui coûte '
                        . $actionCost . ' MP, mais n’en a pas assez et perd son tour.';
                } elseif ($action === 'attack' || $action === 'all_out') {
                    $vampireMode = !empty($fightState['ditto_bat_turns']);
                    $damageReduction = !empty($effects['poison']) ? 10 : 0;
                    $oldHP = $ditto->getHP();
                    $damage = $ditto->attack(
                        $hero,
                        $action === 'all_out',
                        $vampireMode,
                        $damageReduction,
                        $ditto->getCopiedHero() instanceof Werewolf
                            ? (int) ($fightState['ditto_werewolf_next_attack_bonus'] ?? 0)
                            : 0
                    );
                    if ($ditto->getCopiedHero() instanceof Werewolf) {
                        unset($fightState['ditto_werewolf_next_attack_bonus']);
                    }
                    if (!empty($effects['vampirism'])) {
                        $healingMultiplier = $ditto->getCopiedHero() instanceof Mage ? 120 : 100;
                        $healing = intdiv($damage * $healingMultiplier, 100);
                        $healed = $ditto->heal($healing);
                        unset($effects['vampirism']);
                        $combatLog[] = 'Vampirisme soigne Ditto de ' . $healed . ' HP.';
                    }

                    if ($vampireMode) {
                        $combatLog[] = 'Ditto utilise Chauve-souris et récupère '
                            . max(0, $ditto->getHP() - $oldHP) . ' HP.';
                    } else {
                        $combatLog = array_merge(
                            $combatLog,
                            $this->formatAttackLog(
                                'Ditto',
                                $action === 'all_out' ? 'utilise All out' : 'attaque',
                                $damage,
                                $hero->getName(),
                                $hero->getHP()
                            )
                        );
                        if ($ditto->getHP() > $oldHP) {
                            $combatLog[] = 'Ditto récupère '
                                . ($ditto->getHP() - $oldHP) . ' HP grâce au vol de vie.';
                        }
                    }
                } elseif ($action === 'effect') {
                    $statuses = ['freeze', 'poison', 'burn', 'vampirism'];
                    $status = $statuses[array_rand($statuses)];
                    $combatLog = array_merge(
                        $combatLog,
                        $this->applyDittoStatusToHero($status, $ditto, $hero, $heroEffects)
                    );
                } else {
                    $combatLog = array_merge(
                        $combatLog,
                        $this->useDittoSpecialAttack($ditto, $hero, $effects, $fightState, $heroEffects)
                    );
                }
            }

            if (!empty($fightState['ditto_bat_turns'])) {
                $fightState['ditto_bat_turns']--;
            }
            $ditto->regenerateMana();
            $ditto->storeState($fightState);

            return $combatLog;
        }

        /**
         * Vérifie si Ditto peut utiliser la compétence spéciale du héros qu'il a copié.
         * Traduit d'abord les informations d'état propres à Ditto vers le format
         * attendu par la compétence de la classe copiée.
         *
         * @param Ditto $ditto Ditto dont on vérifie la compétence copiée.
         * @param array $fightState État actuel du combat.
         * @return bool true si la compétence spéciale copiée est disponible.
         */

        private function canDittoUseSpecialAttack(Ditto $ditto, array $fightState): bool
        {
            $copiedFightState = [];
            if (!empty($fightState['ditto_bat_turns'])) {
                $copiedFightState['vampire_bat_turns'] = $fightState['ditto_bat_turns'];
            }
            if (!empty($fightState['ditto_convert_next_damage'])) {
                $copiedFightState['convert_next_damage_to_healing'] = true;
            }

            return $ditto->getCopiedHero()->canUseSpecialAttack($copiedFightState);
        }

        /**
         * Exécute la compétence spéciale correspondant à la classe copiée par Ditto.
         * Le comportement dépend du type du héros copié ; certains effets modifient
         * également l'état du combat pour influencer les tours suivants.
         *
         * @param Ditto $ditto Ditto qui utilise la compétence copiée.
         * @param Hero $hero Héros adverse.
         * @param array &$effects Effets actifs sur Ditto, modifiés par référence.
         * @param array &$fightState État du combat, modifié par référence si nécessaire.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant la compétence utilisée.
         * @throws LogicException Si la classe copiée ne possède pas de compétence gérée ici.
         */

        private function useDittoSpecialAttack(
            Ditto $ditto,
            Hero $hero,
            array &$effects,
            array &$fightState,
            array &$heroEffects
        ): array {
            $copiedHero = $ditto->getCopiedHero();

            if ($copiedHero instanceof Mage) {
                $healed = $ditto->heal(60);
                return ['Ditto copie Cercle de soin et restaure ' . $healed . ' HP.'];
            }

            if ($copiedHero instanceof Guerrier) {
                $ditto->convertNextDamageToHealing();
                return ['Ditto copie Valeur : sa prochaine attaque reçue le soignera.'];
            }

            if ($copiedHero instanceof Archer) {
                $chance = $ditto->increaseDodgeChanceBy25Percent();
                return [
                    'Ditto copie Éloignement : son esquive passe à '
                    . intdiv($chance, 100) . ' %.'
                ];
            }

            if ($copiedHero instanceof Vampire) {
                $fightState['ditto_bat_turns'] = 4;
                return ['Ditto copie Chauve-souris pour ses prochaines attaques.'];
            }

            if ($copiedHero instanceof Werewolf) {
                $healing = random_int(1, 10);
                $healed = $ditto->heal($healing);
                $bonus = random_int(1, 10);
                $fightState['ditto_werewolf_next_attack_bonus'] =
                    ($fightState['ditto_werewolf_next_attack_bonus'] ?? 0) + $bonus;

                return [
                    'Ditto copie Hurlement : restaure ' . $healed . ' HP et ajoute '
                    . $bonus . ' dégâts à sa prochaine attaque (bonus cumulé : '
                    . $fightState['ditto_werewolf_next_attack_bonus'] . ').'
                ];
            }

            if ($copiedHero instanceof Gambler) {
                return $this->useDittoAllIn($ditto, $hero, $effects, $fightState, $heroEffects);
            }

            throw new LogicException('Ditto ne peut pas copier la compétence active de cette classe.');
        }

        /**
         * Résout la compétence All In copiée par Ditto en tirant un résultat aléatoire.
         * Selon le résultat, le héros ou Ditto peut subir des dégâts, un effet peut être
         * appliqué ou Ditto peut déclencher une imitation.
         *
         * @param Ditto $ditto Ditto qui utilise All In.
         * @param Hero $hero Héros ciblé par certains résultats.
         * @param array &$effects Effets actifs sur Ditto, modifiés par référence.
         * @param array &$fightState État du combat, modifié par référence.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant le résultat obtenu.
         * @throws LogicException Si le résultat tiré n'est pas reconnu.
         */

        private function useDittoAllIn(
            Ditto $ditto,
            Hero $hero,
            array &$effects,
            array &$fightState,
            array &$heroEffects
        ): array {
            $outcomes = [
                'instakill',
                'jackpot',
                'magic',
                'jackpot',
                'unlucky',
                'unlucky_magic',
                'mimic'
            ];
            $outcome = $outcomes[array_rand($outcomes)];

            switch ($outcome) {
                case 'instakill':
                    $hero->setHP(0);
                    return ['Ditto copie All In — Instakill : le héros est vaincu d’un coup.'];

                case 'jackpot':
                    $damage = $hero->receiveDamage(random_int(50, 100));
                    return ['Ditto copie All In — Jackpot et inflige ' . $damage . ' dégâts.'];

                case 'magic':
                    $statuses = ['freeze', 'poison', 'burn', 'vampirism'];
                    $status = $statuses[array_rand($statuses)];
                    return $this->applyDittoStatusToHero($status, $ditto, $hero, $heroEffects);

                case 'unlucky':
                    $damage = random_int(0, 50);
                    $ditto->setHP(max(1, $ditto->getHP() - $damage));
                    return ['Ditto copie All In — Unlucky et subit ' . $damage . ' dégâts.'];

                case 'unlucky_magic':
                    $statuses = ['freeze', 'poison', 'burn', 'vampirism'];
                    $status = $statuses[array_rand($statuses)];
                    return $this->applyEffect($status, $effects, $ditto->getCopiedHero(), $ditto);

                case 'mimic':
                    $classes = ['Mage', 'Guerrier', 'Archer', 'Vampire'];
                    $className = $classes[array_rand($classes)];
                    return $this->useDittoMimic($className, $ditto, $hero, $fightState);
            }

            throw new LogicException('Résultat All In de Ditto inconnu.');
        }

        /**
         * Exécute l'un des effets de Mimic pour la classe choisie aléatoirement.
         *
         * @param string $className Nom de la classe dont Ditto imite l'effet.
         * @param Ditto $ditto Ditto qui utilise Mimic.
         * @param Hero $hero Héros adverse transmis à la méthode ; ce paramètre n’est pas utilisé dans son implémentation actuelle.
         * @param array &$fightState État du combat, modifié par référence si nécessaire.
         * @return array Message décrivant l'effet imité.
         * @throws LogicException Si le nom de classe n'est pas pris en charge.
         */

        private function useDittoMimic(
            string $className,
            Ditto $ditto,
            Hero $hero,
            array &$fightState
        ): array {
            switch ($className) {
                case 'Mage':
                    $healed = $ditto->heal(60);
                    return ['Ditto copie Mimic (Mage) et restaure ' . $healed . ' HP.'];

                case 'Guerrier':
                    $ditto->convertNextDamageToHealing();
                    return ['Ditto copie Mimic (Guerrier) : sa prochaine attaque reçue le soignera.'];

                case 'Archer':
                    $chance = $ditto->increaseDodgeChanceBy25Percent();
                    return [
                        'Ditto copie Mimic (Archer) : son esquive passe à '
                        . intdiv($chance, 100) . ' %.'
                    ];

                case 'Vampire':
                    $fightState['ditto_bat_turns'] = 4;
                    return ['Ditto copie Mimic (Vampire) et active Chauve-souris.'];
            }

            throw new LogicException('Classe Mimic de Ditto inconnue.');
        }

        /**
         * Tente d'appliquer au héros un effet de statut choisi par Ditto.
         * Vérifie d'abord l'immunité du héros et empêche le cumul d'un même effet
         * lorsque celui-ci est déjà actif.
         *
         * @param string $effect Identifiant de l'effet à appliquer (freeze, poison, burn ou vampirism).
         * @param Ditto $ditto Ditto à l'origine de l'effet.
         * @param Hero $hero Héros qui reçoit potentiellement l'effet.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant l'application ou le refus de l'effet.
         * @throws InvalidArgumentException Si l'identifiant d'effet est inconnu.
         */

        private function applyDittoStatusToHero(
            string $effect,
            Ditto $ditto,
            Hero $hero,
            array &$heroEffects
        ): array {
            if ($hero->isImmuneToStatusEffect($effect)) {
                return [$hero->getName() . ' est immunisé aux effets de statut de Ditto.'];
            }

            switch ($effect) {
                case 'freeze':
                    if (!empty($heroEffects['freeze'])) {
                        return ['Le gel est déjà actif et ne se cumule pas.'];
                    }
                    $heroEffects['freeze'] = true;
                    $heroEffects['freeze_miss_chance_percent'] = $ditto->getFreezeMissChancePercent();
                    return [
                        'Ditto gèle le héros : '
                        . $ditto->getFreezeMissChancePercent()
                        . ' % de chance de rater son action.'
                    ];

                case 'poison':
                    if (!empty($heroEffects['poison'])) {
                        return ['Le poison est déjà actif et ne se cumule pas.'];
                    }
                    $heroEffects['poison'] = true;
                    $heroEffects['poison_damage_per_turn'] = $ditto->getPoisonDamagePerTurn();
                    return [
                        'Ditto empoisonne le héros : '
                        . $ditto->getPoisonDamagePerTurn()
                        . ' dégâts par tour et 10 % de dégâts d’attaque en moins.'
                    ];

                case 'burn':
                    if (!empty($heroEffects['burn_damage'])) {
                        return ['La brûlure est déjà active et ne se cumule pas.'];
                    }
                    $heroEffects['burn_damage'] = 5;
                    $heroEffects['burn_growth_percent'] = $ditto->getBurnGrowthPercent();
                    return [
                        'Ditto brûle le héros : 5 dégâts au premier tour, puis une augmentation de '
                        . $ditto->getBurnGrowthPercent() . ' % par tour.'
                    ];

                case 'vampirism':
                    if (!empty($heroEffects['vampirism'])) {
                        return ['Vampirisme est déjà actif et ne se cumule pas.'];
                    }
                    $heroEffects['vampirism'] = true;
                    return ['Ditto applique Vampirisme : la prochaine attaque du héros lui rendra les dégâts infligés en HP.'];

                default:
                    throw new InvalidArgumentException('Effet de statut inconnu.');
            }
        }

        /**
         * Exécute le tour d'attaque normal du héros.
         * Traite les effets qui s'appliquent au début du tour, les éventuels blocages,
         * l'attaque normale ou All Out, les soins associés et la réponse du monstre.
         *
         * @param Hero $hero Héros qui joue son tour.
         * @param Monster $monster Monstre ciblé puis susceptible de riposter.
         * @param bool $allOut Indique si le héros utilise son attaque All Out.
         * @param string|null $pendingEffect Effet éventuellement en attente d'application sur le monstre.
         * @param array &$effects Effets actifs sur le monstre, modifiés par référence.
         * @param array &$fightState État du combat, modifié par référence.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant le tour du héros et la réponse du monstre.
         */

        public function playTurn(
            Hero $hero,
            Monster $monster,
            bool $allOut = false,
            ?string $pendingEffect = null,
            array &$effects = [],
            array &$fightState = [],
            array &$heroEffects = []
        ): array {
            $heroEffects['action_blocked'] = false;
            $combatLog = $this->applyHeroTurnStart($hero, $heroEffects);
            if ($hero->getHP() <= 0) {
                $heroEffects['action_blocked'] = true;
                return $combatLog;
            }

            if (
                !$hero->isImmuneToStatusEffect('freeze')
                && !empty($heroEffects['freeze'])
                && random_int(1, 100) <= ($heroEffects['freeze_miss_chance_percent'] ?? 25)
            ) {
                $heroEffects['action_blocked'] = true;
                $hero->setAttackDamageReductionPercent(0);
                $combatLog[] = $hero->getName() . ' est gelé et ne peut pas attaquer ce tour.';
                return array_merge(
                    $combatLog,
                    $this->resolveMonsterResponse($hero, $monster, $effects, $fightState, $heroEffects)
                );
            }

            if ($pendingEffect === 'vampirism') {
                if ($hero->isImmuneToStatusEffect('vampirism')) {
                    $combatLog[] = $hero->getName() . ' est immunisé à Vampirisme.';
                } else {
                    $heroEffects['vampirism'] = true;
                    $combatLog[] = 'Vampirisme renforce la prochaine attaque de '
                        . $hero->getName() . '.';
                }
                $pendingEffect = null;
            }

            $hero->setAttackDamageReductionPercent(
                !$hero->isImmuneToStatusEffect('poison') && !empty($heroEffects['poison']) ? 10 : 0
            );
            if ($hero instanceof Werewolf) {
                $hero->setNextAttackDamageBonus(
                    (int) ($fightState['werewolf_next_attack_bonus'] ?? 0)
                );
            }
            $vampireMode = !empty($fightState['vampire_bat_turns']);
            if ($hero instanceof Vampire || $hero instanceof Gambler) {
                $hero->setBatMode($vampireMode);
            }

            if ($allOut) {
                $damage = $hero->hitAllOut($monster);
                $attackVerb = 'utilise All out';
            } else {
                $damage = $hero->hit($monster);
                $attackVerb = 'attaque';
            }
            if ($hero instanceof Werewolf) {
                unset($fightState['werewolf_next_attack_bonus']);
            }
            if (!empty($heroEffects['vampirism'])) {
                $healingMultiplier = $hero instanceof Mage ? 120 : 100;
                $healing = intdiv($damage * $healingMultiplier, 100);
                $healed = $hero->restoreHP($healing);
                unset($heroEffects['vampirism']);
                $combatLog[] = 'Vampirisme soigne ' . $hero->getName() . ' de ' . $healed . ' HP.';
            }
            if (
                ($hero instanceof Vampire || $hero instanceof Gambler)
                && $hero->getLastVampiricDamage() > 0
            ) {
                $combatLog[] = $hero->getName()
                    . ' détourne ' . $hero->getLastVampiricDamage()
                    . ' dégâts pour récupérer '
                    . $hero->getLastLifeSteal() . ' HP.';
            } elseif ($monster instanceof Ditto && $monster->didConvertLastDamageToHealing()) {
                $combatLog[] = 'Ditto convert l’attaque en soins.';
            } elseif ($monster->didDodgeLastDamage()) {
                $combatLog[] = $monster->getName() . ' esquive l’attaque du héros.';
            } else {
                $combatLog = array_merge(
                    $combatLog,
                    $this->formatAttackLog(
                        $hero->getName(),
                        $attackVerb,
                        $damage,
                        $monster->getName(),
                        $monster->getHP()
                    )
                );
                $this->appendLifeStealLog($hero, $combatLog);
            }
            $this->appendResurrectionLog($monster, $combatLog);
            $hero->setAttackDamageReductionPercent(0);

            if ($pendingEffect !== null) {
                $combatLog = array_merge(
                    $combatLog,
                    $this->applyEffect($pendingEffect, $effects, $hero, $monster)
                );
            }

            return array_merge(
                $combatLog,
                $this->resolveMonsterResponse($hero, $monster, $effects, $fightState, $heroEffects)
            );
        }

        /**
         * Exécute le tour pendant lequel le héros utilise sa compétence spéciale.
         * Gère les effets de début de tour, l'immunité ou le gel, l'utilisation de la
         * compétence, les effets différés du Gambler et la réponse du monstre.
         * Le monstre est passé par référence car certains effets peuvent le remplacer.
         *
         * @param Hero $hero Héros qui utilise sa compétence spéciale.
         * @param Monster &$monster Monstre ciblé ; peut être remplacé par un effet comme Reroll.
         * @param array &$effects Effets actifs sur le monstre, modifiés par référence.
         * @param array &$fightState État du combat, modifié par référence.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant la compétence et la réponse du monstre.
         */

        public function playSpecialTurn(
            Hero $hero,
            Monster &$monster,
            array &$effects,
            array &$fightState,
            array &$heroEffects = []
        ): array {
            $combatLog = $this->applyHeroTurnStart($hero, $heroEffects);
            if ($hero->getHP() <= 0) {
                return $combatLog;
            }

            if (
                !$hero->isImmuneToStatusEffect('freeze')
                && !empty($heroEffects['freeze'])
                && random_int(1, 100) <= ($heroEffects['freeze_miss_chance_percent'] ?? 25)
            ) {
                $combatLog[] = $hero->getName() . ' est gelé et ne peut pas utiliser son attaque spéciale.';
            } else {
                $hero->setAttackDamageReductionPercent(
                    !$hero->isImmuneToStatusEffect('poison') && !empty($heroEffects['poison']) ? 10 : 0
                );
                $monster->resetLastDamageDodged();
                $monsterHPBeforeSpecial = $monster->getHP();
                $combatLog = array_merge(
                    $combatLog,
                    $hero->useSpecialAttack($monster, $fightState)
                );
                $hero->healFromRogueVampirism(
                    max(0, $monsterHPBeforeSpecial - $monster->getHP())
                );
                if ($monster instanceof Ditto && $monster->didConvertLastDamageToHealing()) {
                    $combatLog[] = 'Ditto convert l’attaque en soins.';
                } elseif ($monster->didDodgeLastDamage()) {
                    $combatLog[] = $monster->getName() . ' esquive l’attaque spéciale du héros.';
                }
                $this->appendResurrectionLog($monster, $combatLog);
                if (!empty($fightState['gambler_pending_monster_status'])) {
                    $status = $fightState['gambler_pending_monster_status'];
                    unset($fightState['gambler_pending_monster_status']);
                    $combatLog = array_merge(
                        $combatLog,
                        $this->applyEffect($status, $effects, $hero, $monster)
                    );
                }
                if (!empty($fightState['gambler_pending_hero_status'])) {
                    $status = $fightState['gambler_pending_hero_status'];
                    unset($fightState['gambler_pending_hero_status']);
                    $combatLog = array_merge(
                        $combatLog,
                        $this->applyGamblerStatusToHero($status, $hero, $heroEffects)
                    );
                }
                if (!empty($fightState['gambler_reroll'])) {
                    unset($fightState['gambler_reroll']);
                    $previousClass = $monster->getClassName();
                    $previousHP = $monster->getHP();
                    $monster = $this->createMonster(null, $previousClass);
                    $monster->setHP(min($previousHP, $monster->getMaxHP()));
                    $effects = ['freeze' => false, 'poison' => false, 'burn_damage' => 0];
                    $combatLog[] = 'Reroll remplace le ' . $previousClass . ' par un '
                        . $monster->getClassName() . ' avec '
                        . $monster->getHP() . ' / ' . $monster->getMaxHP() . ' HP.';
                }
                $hero->setAttackDamageReductionPercent(0);
            }

            return array_merge(
                $combatLog,
                $this->resolveMonsterResponse($hero, $monster, $effects, $fightState, $heroEffects)
            );
        }

        /**
         * Résout la partie du tour correspondant aux effets persistants et à l'action du monstre.
         * Applique notamment le poison et la brûlure, puis gère l'action du monstre
         * (attaque, compétence ou comportement particulier selon sa classe).
         *
         * @param Hero $hero Héros susceptible de subir les effets et l'attaque.
         * @param Monster $monster Monstre qui agit en réponse.
         * @param array &$effects Effets actifs sur le monstre, modifiés par référence.
         * @param array &$fightState État du combat, modifié par référence.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant les dégâts, effets et actions du monstre.
         */

        private function resolveMonsterResponse(
            Hero $hero,
            Monster $monster,
            array &$effects,
            array &$fightState,
            array &$heroEffects
        ): array {
            $combatLog = [];

            if ($monster->getHP() > 0) {
                if (!empty($effects['poison'])) {
                    $damage = $monster->receiveDamageOverTime(
                        $effects['poison_damage_per_turn'] ?? $hero->getPoisonDamagePerTurn()
                    );
                    $this->appendResurrectionLog($monster, $combatLog);
                    $combatLog = array_merge(
                        $combatLog,
                        $this->formatAttackLog('Poison', 'inflige', $damage, $monster->getName(), $monster->getHP())
                    );
                }

                if ($monster->getHP() > 0 && !empty($effects['burn_damage'])) {
                    $burnDamage = (int) $effects['burn_damage'];
                    $damage = $monster->receiveDamageOverTime($burnDamage);
                    $this->appendResurrectionLog($monster, $combatLog);
                    $combatLog = array_merge(
                        $combatLog,
                        $this->formatAttackLog('Brûlure', 'inflige', $damage, $monster->getName(), $monster->getHP())
                    );
                    $effects['burn_damage'] = intdiv(
                        $burnDamage * ($effects['burn_growth_percent'] ?? $hero->getBurnGrowthPercent()) + 99,
                        100
                    );
                }
            }

            if ($monster->getHP() > 0) {
                if ($monster instanceof Ditto) {
                    $combatLog = array_merge(
                        $combatLog,
                        $this->playDittoTurn($monster, $hero, $effects, $fightState, $heroEffects)
                    );
                } elseif ($monster instanceof NuclearWarhead) {
                    $turnsRemaining = $monster->advanceFuse();
                    $fightState[NuclearWarhead::STATE_KEY] = $turnsRemaining;
                    if ($turnsRemaining <= 0) {
                        $hero->setHP(0);
                        $combatLog[] = $monster->getName()
                            . ' explose : '
                            . $hero->getName()
                            . ' est tué par la détonation.';
                    } else {
                        $combatLog[] = 'La bombe est toujours active : '
                            . $turnsRemaining . ' tours avant l’explosion.';
                    }
                } elseif (
                    !empty($effects['freeze'])
                    && random_int(1, 100) <= ($effects['freeze_miss_chance_percent'] ?? $hero->getFreezeMissChancePercent())
                ) {
                    $combatLog[] = $monster->getName() . ' est gelé et ne peut pas attaquer ce tour.';
                } else {
                    $damageReductionPercent = !empty($effects['poison']) ? 10 : 0;
                    $vampireMode = !empty($fightState['vampire_bat_turns']);
                    $convertToHealing = !empty($fightState['convert_next_damage_to_healing']);
                    $usesNeutralAir = $monster instanceof MarioNeutralAir
                        && $monster->usesNeutralAir();
                    $damage = $monster->hit(
                        $hero,
                        $damageReductionPercent,
                        $vampireMode,
                        $convertToHealing,
                        $vampireMode ? 8000 : null,
                        $usesNeutralAir
                    );
                    if (!empty($effects['vampirism'])) {
                        $healing = $damage;
                        if ($monster instanceof Ditto) {
                            $healed = $monster->heal($healing);
                        } else {
                            $oldHP = $monster->getHP();
                            $monster->setHP($oldHP + $healing);
                            $healed = $monster->getHP() - $oldHP;
                        }
                        unset($effects['vampirism']);
                        $combatLog[] = 'Vampirisme soigne ' . $monster->getName()
                            . ' de ' . $healed . ' HP.';
                    }

                    if (
                        $convertToHealing
                        && !$hero->didDodgeLastAttack()
                        && !$monster->didMissLastAttack()
                    ) {
                        $fightState['convert_next_damage_to_healing'] = false;
                        $healed = $hero->getLastDamageConvertedToHealing();
                        $combatLog[] = $hero->getName()
                            . ' convert l’attaque en '
                            . $healed . ' HP de soin.';
                    }
                    if ($monster->didMissLastAttack()) {
                        $combatLog[] = $monster->getName()
                            . ($usesNeutralAir ? ' rate Neutral Air.' : ' rate son attaque.');
                    } elseif ($hero->didDodgeLastAttack()) {
                        $combatLog[] = $hero->getName() . ' esquive l’attaque du monstre.';
                    } else {
                        $combatLog = array_merge(
                            $combatLog,
                            $this->formatAttackLog(
                                $monster->getName(),
                                $usesNeutralAir ? 'utilise Neutral Air' : 'attaque',
                                $damage,
                                $hero->getName(),
                                $hero->getHP()
                            )
                        );
                        $this->tryApplySorcererStatus($monster, $hero, $heroEffects, $combatLog);
                    }
                    $combatLog = array_merge(
                        $combatLog,
                        $this->tryApplyNoWellSnowgrave(
                            $monster,
                            $hero,
                            $heroEffects,
                            !$monster->didMissLastAttack() && !$hero->didDodgeLastAttack()
                        )
                    );
                }
            }

            if (!empty($fightState['vampire_bat_turns'])) {
                $fightState['vampire_bat_turns']--;
                if ($hero instanceof Vampire) {
                    $hero->setBatMode(false);
                }
            }

            return $combatLog;
        }

        /**
         * Applique au héros les effets qui se déclenchent au début de son tour.
         * Cela comprend les dégâts de poison et de brûlure ainsi que la régénération
         * lorsqu'une amélioration de carte du Voleur est active.
         *
         * @param Hero $hero Héros qui commence son tour.
         * @param array &$effects Effets actifs sur le héros, lus et éventuellement modifiés par référence.
         * @return array Messages décrivant les dégâts et les soins appliqués.
         */

        private function applyHeroTurnStart(Hero $hero, array &$effects): array
        {
            $combatLog = [];
            if (!$hero->isImmuneToStatusEffect('poison') && !empty($effects['poison'])) {
                $damage = $hero->receiveDamage($effects['poison_damage_per_turn'] ?? 5);
                $combatLog = array_merge(
                    $combatLog,
                    $this->formatAttackLog('Poison', 'inflige', $damage, $hero->getName(), $hero->getHP())
                );
            }

            if (
                !$hero->isImmuneToStatusEffect('burn')
                && !empty($effects['burn_damage'])
                && $hero->getHP() > 0
            ) {
                $burnDamage = (int) $effects['burn_damage'];
                $damage = $hero->receiveDamage($burnDamage);
                $combatLog = array_merge(
                    $combatLog,
                    $this->formatAttackLog('Brûlure', 'inflige', $damage, $hero->getName(), $hero->getHP())
                );
                $effects['burn_damage'] = intdiv(
                    $burnDamage * ($effects['burn_growth_percent'] ?? 125) + 99,
                    100
                );
            }

            if ($hero->getHP() > 0 && $hero->hasActiveRogueCardUpgrade('RegenerationUpgrade')) {
                $healed = $hero->restoreHP(random_int(1, 10));
                if ($healed > 0) {
                    $combatLog[] = $hero->getName() . ' récupère ' . $healed . ' HP grâce à la régénération.';
                }
            }

            return $combatLog;
        }

        /**
         * Applique l'effet de statut initial propre à certains boss au début du combat.
         * Le type de boss détermine si le héros est gelé, brûlé ou empoisonné.
         *
         * @param Monster $monster Boss ou monstre rencontré.
         * @param Hero $hero Héros qui peut recevoir l'effet.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant l'effet initial ou l'immunité du héros.
         */

        private function applyBossOpeningStatus(
            Monster $monster,
            Hero $hero,
            array &$heroEffects
        ): array {
            if ($monster instanceof NoWell) {
                if ($hero->isImmuneToStatusEffect('freeze')) {
                    return [$hero->getName() . ' est immunisé au gel de NoWell.'];
                }

                $heroEffects['freeze'] = true;
                $heroEffects['freeze_miss_chance_percent'] = $hero->getFreezeMissChancePercent();

                return [
                    'NoWell gèle ' . $hero->getName() . ' : '
                    . $heroEffects['freeze_miss_chance_percent']
                    . ' % de chance de manquer chaque action.'
                ];
            }

            if ($monster instanceof MarioNeutralAir) {
                if ($hero->isImmuneToStatusEffect('burn')) {
                    return [$hero->getName() . ' est immunisé à la brûlure de Mario Neutral Air.'];
                }

                $heroEffects['burn_damage'] = 5;
                $heroEffects['burn_growth_percent'] = $hero->getBurnGrowthPercent();

                return [
                    'Mario Neutral Air brûle ' . $hero->getName()
                    . ' : 5 dégâts au premier tour, puis une augmentation de '
                    . $hero->getBurnGrowthPercent() . ' % par tour.'
                ];
            }

            if ($monster instanceof Ouroboros) {
                if ($hero->isImmuneToStatusEffect('poison')) {
                    return [$hero->getName() . ' est immunisé au poison d’Ouroboros.'];
                }

                $heroEffects['poison'] = true;
                $heroEffects['poison_damage_per_turn'] = $hero->getPoisonDamagePerTurn();

                return [
                    'Ouroboros empoisonne ' . $hero->getName()
                    . ' : ' . $hero->getPoisonDamagePerTurn()
                    . ' dégâts par tour et 10 % de dégâts d’attaque en moins.'
                ];
            }

            return [];
        }

        /**
         * Tente d'appliquer Snowgrave après une attaque connectée de NoWell.
         * L'effet a une chance de se déclencher, respecte l'immunité au gel
         * et ne se cumule pas s'il est déjà actif.
         *
         * @param Monster $monster Monstre attaquant ; la méthode ne déclenche Snowgrave que pour NoWell.
         * @param Hero $hero Héros susceptible de recevoir Snowgrave.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @param bool $attackConnected Indique si l'attaque a touché et n'a pas été esquivée.
         * @return array Message décrivant l'application de Snowgrave, ou tableau vide.
         */

        private function tryApplyNoWellSnowgrave(
            Monster $monster,
            Hero $hero,
            array &$heroEffects,
            bool $attackConnected
        ): array {
            if (
                !($monster instanceof NoWell)
                || !$attackConnected
                || random_int(1, 100) > 20
            ) {
                return [];
            }

            if ($hero->isImmuneToStatusEffect('freeze')) {
                return [$hero->getName() . ' est immunisé à Snowgrave.'];
            }

            if (!empty($heroEffects['snowgrave'])) {
                return ['Snowgrave est déjà actif et ne se cumule pas.'];
            }

            $heroEffects['snowgrave'] = true;
            $heroEffects['freeze'] = true;
            $heroEffects['freeze_miss_chance_percent'] = min(
                100,
                2 * ($heroEffects['freeze_miss_chance_percent'] ?? $hero->getFreezeMissChancePercent())
            );

            return [
                'NoWell applique Snowgrave : le gel passe à '
                . $heroEffects['freeze_miss_chance_percent']
                . ' % de chance de bloquer chaque action.'
            ];
        }

        /**
         * Tente d'appliquer un effet de statut au héros lorsque le Sorcier attaque.
         * La tentative a une chance de réussir ; l'immunité du héros et les effets
         * déjà actifs sont pris en compte avant toute modification.
         *
         * @param Monster $monster Monstre attaquant ; seul un Sorcier peut appliquer cet effet.
         * @param Hero $hero Héros susceptible de recevoir l'effet.
         * @param array &$effects Effets actifs sur le héros, modifiés par référence.
         * @param array &$combatLog Journal du combat dans lequel les messages sont ajoutés.
         * @return void
         */

        private function tryApplySorcererStatus(
            Monster $monster,
            Hero $hero,
            array &$effects,
            array &$combatLog
        ): void {
            if (!$monster instanceof Sorcier || random_int(1, 100) > 10) {
                return;
            }

            $availableEffects = ['freeze', 'poison', 'burn', 'vampirism'];
            $effect = $availableEffects[array_rand($availableEffects)];
            if ($hero->isImmuneToStatusEffect($effect)) {
                $combatLog[] = $hero->getName() . ' est immunisé aux effets de statut du Sorcier.';
                return;
            }

            if ($effect === 'vampirism') {
                if (!empty($effects['vampirism'])) {
                    $combatLog[] = 'Vampirisme est déjà actif et ne se cumule pas.';
                    return;
                }
                $effects['vampirism'] = true;
                $combatLog[] = 'Le Sorcier applique Vampirisme : la prochaine attaque du héros lui rendra les dégâts infligés en HP.';
                return;
            }

            if ($effect === 'burn') {
                if (!empty($effects['burn_damage'])) {
                    $combatLog[] = 'La brûlure est déjà active et ne se cumule pas.';
                    return;
                }
                $effects['burn_damage'] = 5;
                $combatLog[] = 'Le Sorcier inflige une brûlure au héros : 5 dégâts, puis +25 % par tour.';
                return;
            }

            if (!empty($effects[$effect])) {
                $combatLog[] = 'L’effet de ' . ($effect === 'freeze' ? 'gel' : 'poison')
                    . ' est déjà actif et ne se cumule pas.';
                return;
            }

            $effects[$effect] = true;
            $combatLog[] = $effect === 'freeze'
                ? 'Le Sorcier gèle le héros : 25 % de chance de rater chaque attaque.'
                : 'Le Sorcier empoisonne le héros : 5 dégâts par tour et 10 % de dégâts d’attaque en moins.';
        }

        /**
         * Ajoute au journal un message si le monstre vient de ressusciter.
         * Le message indique ses points de vie après la résurrection.
         *
         * @param Monster $monster Monstre dont on vérifie la dernière mort.
         * @param array &$combatLog Journal du combat, modifié par référence.
         * @return void
         */

        private function appendResurrectionLog(Monster $monster, array &$combatLog): void
        {
            if ($monster->didResurrectLastDeath()) {
                $combatLog[] = $monster->getName() . ' ressuscite avec '
                    . $monster->getHP() . ' / ' . $monster->getMaxHP() . ' HP.';
            }
        }

        /**
         * Ajoute au journal un message si le héros a récupéré des HP grâce au vol de vie.
         *
         * @param Hero $hero Héros dont on récupère le montant du dernier soin par vol de vie.
         * @param array &$combatLog Journal du combat, modifié par référence.
         * @return void
         */

        private function appendLifeStealLog(Hero $hero, array &$combatLog): void
        {
            $healed = $hero->getLastLifeSteal();
            if ($healed > 0) {
                $combatLog[] = $hero->getName() . ' récupère ' . $healed . ' HP grâce au vol de vie.';
            }
        }

        /**
         * Applique au monstre l'effet de statut choisi par le héros.
         * Vérifie les immunités et empêche le cumul d'un effet déjà actif.
         *
         * @param string $effect Identifiant de l'effet à appliquer.
         * @param array &$effects Effets actifs sur le monstre, modifiés par référence.
         * @param Hero $hero Héros à l'origine de l'effet et dont certaines valeurs définissent son intensité.
         * @param Monster|null $monster Monstre ciblé, utilisé notamment pour vérifier le cas de Ditto.
         * @return array Messages décrivant l'application ou le refus de l'effet.
         * @throws InvalidArgumentException Si l'identifiant d'effet est inconnu.
         */

        private function applyEffect(
            string $effect,
            array &$effects,
            Hero $hero,
            ?Monster $monster = null
        ): array
        {
            if (
                $monster instanceof Ditto
                && $monster->getCopiedHero()->isImmuneToStatusEffects()
            ) {
                return ['Ditto est immunisé aux effets de statut copiés du héros.'];
            }

            switch ($effect) {
                case 'freeze':
                    if (!empty($effects['freeze'])) {
                        return ['Le gel est déjà actif et ne se cumule pas.'];
                    }
                    $effects['freeze'] = true;
                    $effects['freeze_miss_chance_percent'] = $hero->getFreezeMissChancePercent();
                    return [
                        'Le monstre est gelé : il a '
                        . $hero->getFreezeMissChancePercent()
                        . ' % de chance de manquer chaque attaque.'
                    ];

                case 'poison':
                    if (!empty($effects['poison'])) {
                        return ['Le poison est déjà actif et ne se cumule pas.'];
                    }
                    $effects['poison'] = true;
                    $effects['poison_damage_per_turn'] = $hero->getPoisonDamagePerTurn();
                    return [
                        'Le monstre est empoisonné : '
                        . $hero->getPoisonDamagePerTurn()
                        . ' dégâts par tour et ses attaques sont réduites de 10 %.'
                    ];

                case 'burn':
                    if (!empty($effects['burn_damage'])) {
                        return ['La brûlure est déjà active et ne se cumule pas.'];
                    }
                    $effects['burn_damage'] = 5;
                    $effects['burn_growth_percent'] = $hero->getBurnGrowthPercent();
                    return [
                        'Le monstre brûle : 5 dégâts au premier tour, puis une augmentation de '
                        . $hero->getBurnGrowthPercent()
                        . ' % par tour.'
                    ];

                case 'vampirism':
                    if (!empty($effects['vampirism'])) {
                        return ['Vampirisme est déjà actif et ne se cumule pas.'];
                    }
                    $effects['vampirism'] = true;
                    return ['Vampirisme est appliqué au monstre : sa prochaine attaque lui rendra les dégâts infligés en HP.'];

                default:
                    throw new InvalidArgumentException('Effet de monstre inconnu.');
            }
        }

        /**
         * Applique au héros un effet de statut déclenché par une action du Gambler.
         * Vérifie l'immunité et empêche qu'un effet déjà actif soit appliqué une seconde fois.
         *
         * @param string $effect Identifiant de l'effet à appliquer.
         * @param Hero $hero Héros qui reçoit potentiellement l'effet.
         * @param array &$heroEffects Effets actifs sur le héros, modifiés par référence.
         * @return array Messages décrivant l'application ou le refus de l'effet.
         * @throws InvalidArgumentException Si l'identifiant d'effet est inconnu.
         */

        private function applyGamblerStatusToHero(
            string $effect,
            Hero $hero,
            array &$heroEffects
        ): array
        {
            if ($hero->isImmuneToStatusEffect($effect)) {
                return [$hero->getName() . ' est immunisé aux effets de statut.'];
            }

            switch ($effect) {
                case 'freeze':
                    if (!empty($heroEffects['freeze'])) {
                        return ['Le gel est déjà actif et ne se cumule pas.'];
                    }
                    $heroEffects['freeze'] = true;
                    return ['Le Gambler est gelé : 25 % de chance de rater son action à chaque tour.'];

                case 'poison':
                    if (!empty($heroEffects['poison'])) {
                        return ['Le poison est déjà actif et ne se cumule pas.'];
                    }
                    $heroEffects['poison'] = true;
                    return ['Le Gambler est empoisonné : 5 dégâts par tour et ses attaques sont réduites de 10 %.'];

                case 'burn':
                    if (!empty($heroEffects['burn_damage'])) {
                        return ['La brûlure est déjà active et ne se cumule pas.'];
                    }
                    $heroEffects['burn_damage'] = 5;
                    return ['Le Gambler brûle : 5 dégâts au premier tour, puis une augmentation de 25 % par tour.'];

                case 'vampirism':
                    if (!empty($heroEffects['vampirism'])) {
                        return ['Vampirisme est déjà actif et ne se cumule pas.'];
                    }
                    $heroEffects['vampirism'] = true;
                    return ['Vampirisme rendra au Gambler les dégâts infligés par sa prochaine attaque.'];

                default:
                    throw new InvalidArgumentException('Effet de statut inconnu.');
            }
        }

        /**
         * Construit les deux lignes de journal qui décrivent une attaque et les HP restants de sa cible.
         * Les HP affichés sont ramenés à zéro minimum pour éviter d'afficher une valeur négative.
         *
         * @param string $attackerName Nom de l'attaquant.
         * @param string $attackVerb Verbe ou description de l'action effectuée.
         * @param int $damage Nombre de dégâts infligés.
         * @param string $targetName Nom de la cible.
         * @param int $targetHP Points de vie de la cible après l'attaque.
         * @return array Deux messages destinés au journal du combat.
         */

        private function formatAttackLog(
            string $attackerName,
            string $attackVerb,
            int $damage,
            string $targetName,
            int $targetHP
        ): array {
            return [
                $attackerName . ' ' . $attackVerb . ', ' . $damage . ' DMG !',
                $targetName . ' possède encore ' . max(0, $targetHP) . ' HP'
            ];
        }

    }

?>