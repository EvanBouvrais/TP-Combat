<?php

    if (!defined('APP_ENTRY')) {
        http_response_code(404);
        exit;
    }

    require dirname(__DIR__) . "/config/autoload.php";
    require dirname(__DIR__) . "/config/db.php";

    $heroesManager = new HeroesManager($db);
    $fightManager = new FightsManager();
    $fightKey = 'active_fight';
    $error = null;
    $getOuroborosHPBonus = function (int $heroId): int {
        $bonuses = $_SESSION['ouroboros_hp_bonuses'] ?? [];
        return is_array($bonuses)
            ? max(0, (int) ($bonuses[$heroId] ?? 0))
            : 0;
    };
    $recordOuroborosDefeat = function (int $heroId) use ($getOuroborosHPBonus): int {
        $bonus = $getOuroborosHPBonus($heroId) + Ouroboros::HP_PER_VICTORY;
        if (!isset($_SESSION['ouroboros_hp_bonuses']) || !is_array($_SESSION['ouroboros_hp_bonuses'])) {
            $_SESSION['ouroboros_hp_bonuses'] = [];
        }
        $_SESSION['ouroboros_hp_bonuses'][$heroId] = $bonus;
        return $bonus;
    };
    $resetOuroborosHPBonus = function (int $heroId): void {
        if (isset($_SESSION['ouroboros_hp_bonuses']) && is_array($_SESSION['ouroboros_hp_bonuses'])) {
            unset($_SESSION['ouroboros_hp_bonuses'][$heroId]);
        }
    };
    $rogueCardDefinitions = require dirname(__DIR__) . '/config/rogue_card_definitions.php';
    $chooseRogueCards = function (Hero $hero) use ($rogueCardDefinitions): array {
        $availableCards = array_filter(
            array_keys($rogueCardDefinitions),
            function ($cardKey) use ($hero, $rogueCardDefinitions) {
                return !empty($rogueCardDefinitions[$cardKey]['instant'])
                    || !in_array($cardKey, $hero->getRogueCardUpgrades(), true);
            }
        );
        if (count($availableCards) < 3) {
            $availableCards = array_keys($rogueCardDefinitions);
        }
        $availableCards = array_values($availableCards);
        shuffle($availableCards);

        return array_slice($availableCards, 0, 3);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if (
            $action === 'start'
            && is_array($_SESSION[$fightKey] ?? null)
            && !empty($_SESSION[$fightKey]['card_choice_pending'])
            && filter_input(INPUT_POST, 'hero_id', FILTER_VALIDATE_INT)
                === (int) $_SESSION[$fightKey]['hero_id']
        ) {
            $_SESSION['fight_error'] = 'Choisissez une carte avant de continuer.';
            header('Location: fight.php');
            exit;
        }

        if (
            $action === 'attack'
            || $action === 'all_out'
            || $action === 'spell'
            || $action === 'special'
            || $action === 'forfeit'
            || $action === 'choose_card'
        ) {
            $fight = $_SESSION[$fightKey] ?? null;
            $submittedHeroId = filter_input(INPUT_POST, 'hero_id', FILTER_VALIDATE_INT);

            if (
                !is_array($fight)
                || $submittedHeroId === false
                || $submittedHeroId === null
                || $submittedHeroId !== (int) $fight['hero_id']
            ) {
                $error = 'Aucun combat actif pour ce personnage.';
            } else {
                $isTrainingFight = ($fight['game_mode'] ?? 'dungeon') === 'training';
                $hero = $isTrainingFight && (($fight['training_hero'] ?? null) instanceof Hero)
                    ? $fight['training_hero']
                    : $heroesManager->find($fight['hero_id']);
                if ($hero !== null) {
                    $hero->setActiveRogueCardUpgrades(
                        ($fight['game_mode'] ?? 'dungeon') === 'dungeon'
                            ? $hero->getRogueCardUpgrades()
                            : []
                    );
                }

                if ($action === 'choose_card') {
                    $selectedCard = $_POST['card_key'] ?? null;
                    $cardChoices = $fight['card_choices'] ?? [];
                    if (
                        $hero === null
                        || $hero->getHP() <= 0
                        || ($fight['game_mode'] ?? null) !== 'dungeon'
                        || empty($fight['card_choice_pending'])
                        || !is_string($selectedCard)
                        || !in_array($selectedCard, $cardChoices, true)
                        || !isset($rogueCardDefinitions[$selectedCard])
                    ) {
                        $error = 'Choix de carte invalide.';
                    } else {
                        $card = $rogueCardDefinitions[$selectedCard];
                        if (!empty($card['instant'])) {
                            if ($selectedCard === 'InstantHealth') {
                                $healed = $hero->restoreHP($hero->getMaxHP());
                                $fight['log'][] = 'La carte Soin instantané restaure ' . $healed . ' HP.';
                            } else {
                                $hero->setMP($hero->getMaxMP());
                                $fight['log'][] = 'La carte Mana instantané restaure tout le mana.';
                            }
                        } else {
                            $upgrades = $hero->getRogueCardUpgrades();
                            $upgrades[] = $selectedCard;
                            $hero->setRogueCardUpgrades($upgrades);
                            $hero->setActiveRogueCardUpgrades($upgrades);
                            $fight['log'][] = 'La carte « ' . $card['name'] . ' » est active jusqu’à la victoire contre le prochain boss.';
                        }
                        $fight['card_choice_pending'] = false;
                        unset($fight['card_choices']);
                        $heroesManager->update($hero);
                        $_SESSION[$fightKey] = $fight;
                    }
                } elseif ($hero === null || $hero->getHP() <= 0 || $fight['monster_hp'] <= 0) {
                    $error = 'Ce combat est terminé.';
                } elseif ($action === 'forfeit') {
                    $hero->setHP(0);
                    if (!$isTrainingFight) {
                        $resetOuroborosHPBonus((int) $fight['hero_id']);
                    }
                    $fight['log'][] = $hero->getName() . ' abandonne le combat.';
                    if ($isTrainingFight) {
                        $fight['training_hero'] = $hero;
                    } else {
                        $heroesManager->update($hero);
                    }
                    $_SESSION[$fightKey] = $fight;
                } else {
                    if ($action === 'spell') {
                        if ($hero->getMP() < 10) {
                            $error = 'Il faut 10 MP pour lancer ce sort.';
                        } elseif (!empty($fight['pending_effect'])) {
                            $error = 'Un effet attend déjà la prochaine attaque.';
                        } else {
                            $effects = [
                                'freeze' => 'freeze',
                                'poison' => 'poison',
                                'burn' => 'burn',
                                'vampirism' => 'vampirism'
                            ];
                            $effect = $effects[array_rand($effects)];
                            $hero->spendMP(10);
                            $fight['pending_effect'] = $effect;
                            $effectLabels = [
                                'freeze' => 'gel',
                                'poison' => 'poison',
                                'burn' => 'brûlure',
                                'vampirism' => 'Vampirisme'
                            ];
                            $fight['log'][] = $hero->getName()
                                . ' prépare un effet aléatoire : '
                                . $effectLabels[$effect]
                                . '. Il s’appliquera à sa prochaine attaque.';
                            if ($isTrainingFight) {
                                $fight['training_hero'] = $hero;
                            } else {
                                $heroesManager->update($hero);
                            }
                            $_SESSION[$fightKey] = $fight;
                        }
                    } elseif (
                        $action === 'special'
                        && $hero->getSpecialAttackCost() <= 0
                        && !($hero instanceof Flowery)
                    ) {
                        $error = 'Cette classe ne possède pas d’attaque spéciale.';
                    } elseif (
                        $action === 'special'
                        && $hero->getMP() < $hero->getSpecialAttackCost()
                    ) {
                        $error = 'Il faut '
                            . $hero->getSpecialAttackCost()
                            . ' MP pour utiliser cette attaque spéciale.';
                    } elseif (
                        $action === 'special'
                        && !$hero->canUseSpecialAttack($fight['special_state'] ?? [])
                    ) {
                        $error = 'Cette attaque spéciale ne peut pas être utilisée maintenant.';
                    } else {
                        $effects = $fight['effects'] ?? [];
                        $heroEffects = $fight['hero_effects'] ?? [];
                        $specialState = $fight['special_state'] ?? [];
                        $monster = $fightManager->createMonster(
                            $fight['monster_class'],
                            null,
                            $hero,
                            $specialState
                        );
                        $monster->setHP($fight['monster_hp']);

                        if ($action === 'special') {
                            $hero->spendMP($hero->getSpecialAttackCost());
                            if ($hero->specialAttackConsumesTurn()) {
                                $turnLog = $fightManager->playSpecialTurn(
                                    $hero,
                                    $monster,
                                    $effects,
                                    $specialState,
                                    $heroEffects
                                );
                            } else {
                                $turnLog = $hero->useSpecialAttack($monster, $specialState);
                            }
                        } else {
                            $turnLog = $fightManager->playTurn(
                                $hero,
                                $monster,
                                $action === 'all_out',
                                $fight['pending_effect'] ?? null,
                                $effects,
                                $specialState,
                                $heroEffects
                            );
                        }

                        $fight['log'] = array_merge(
                            $fight['log'],
                            $turnLog
                        );
                        if ($action !== 'special' || $hero->specialAttackConsumesTurn()) {
                            $hero->regenerateMP($hero->getManaRegenerationPerTurn());
                        }
                        $fight['monster_hp'] = $monster->getHP();
                        $fight['monster_class'] = $monster->getClassName();
                        if ($monster->getHP() <= 0) {
                            if ($isTrainingFight) {
                                $fight['log'][] = $hero->getName()
                                    . ' a vaincu '
                                    . $monster->getName()
                                    . ' en entraînement.';
                            } else {
                                $hero->surviveMonster();
                                $fight['log'][] = $hero->getName()
                                    . ' a vaincu un monstre ! Total : '
                                    . $hero->getMonstersSurvived() . '.';
                            }
                            if ($fightManager->isBossMonster($monster)) {
                                if (($fight['game_mode'] ?? 'dungeon') === 'dungeon') {
                                    $hero->setRogueCardUpgrades([]);
                                    $hero->setActiveRogueCardUpgrades([]);
                                    $fight['card_choice_pending'] = false;
                                    unset($fight['card_choices']);
                                }
                                $hero->setHP($hero->getMaxHP());
                                $hero->setMP($hero->getMaxMP());
                                $fight['log'][] = 'Victoire contre le boss : tous les HP et MP du héros sont restaurés.';
                            } else {
                                if ($hero instanceof Gambler) {
                                    $fight['log'][] = $hero->onMonsterDefeated();
                                }
                                if (
                                    ($fight['game_mode'] ?? null) === 'dungeon'
                                    && $hero->getHP() > 0
                                ) {
                                    $fight['card_choices'] = $chooseRogueCards($hero);
                                    $fight['card_choice_pending'] = true;
                                }
                            }
                            if ($monster instanceof Ouroboros && !$isTrainingFight) {
                                $nextBonus = $recordOuroborosDefeat((int) $fight['hero_id']);
                                $fight['log'][] = 'Ouroboros gagnera '
                                    . (Ouroboros::BASE_HP + $nextBonus)
                                    . ' HP lors de sa prochaine apparition.';
                            }
                        }
                        if ($hero->getHP() <= 0 && !$isTrainingFight) {
                            $resetOuroborosHPBonus((int) $fight['hero_id']);
                        }
                        if (
                            $hero->getHP() <= 0
                            && $monster instanceof Ditto
                            && $monster->getCopiedHero() instanceof Gambler
                        ) {
                            $monster->restoreAfterVictory();
                            $monster->storeState($specialState);
                            $fight['monster_hp'] = $monster->getHP();
                            $fight['log'][] = 'Addiction restaure tous les HP et MP de Ditto.';
                        }
                        $fight['effects'] = $effects;
                        $fight['hero_effects'] = $heroEffects;
                        $fight['special_state'] = $specialState;
                        if (
                            $action !== 'special'
                            && empty($heroEffects['action_blocked'])
                        ) {
                            $fight['pending_effect'] = null;
                        }
                        unset($fight['hero_effects']['action_blocked']);
                        if ($isTrainingFight) {
                            $fight['training_hero'] = $hero;
                        } else {
                            $heroesManager->update($hero);
                        }
                        $_SESSION[$fightKey] = $fight;
                    }
                }
            }

            if ($error !== null) {
                $_SESSION['fight_error'] = $error;
            }

            header('Location: fight.php');
            exit;
        }

        if ($action !== 'start') {
            $_SESSION['fight_error'] = 'Action de combat invalide.';
            header('Location: fight.php');
            exit;
        }

        $submittedHeroId = filter_input(INPUT_POST, 'hero_id', FILTER_VALIDATE_INT);
        $gameMode = $_POST['game_mode'] ?? 'dungeon';
        $submittedMonsterClass = $_POST['monster_class'] ?? '';
        $availableTrainingClasses = array_merge(
            $fightManager->getAvailableMonsterClasses(),
            $fightManager->getAvailableBossMonsterClasses()
        );
        if ($submittedHeroId === false || $submittedHeroId === null || $submittedHeroId < 1) {
            $error = 'Personnage invalide.';
        } elseif (!is_string($gameMode) || !in_array($gameMode, ['dungeon', 'boss_rush', 'training'], true)) {
            $error = 'Mode de jeu invalide.';
        } elseif (
            $gameMode === 'training'
            && (
                !is_string($submittedMonsterClass)
                || !in_array($submittedMonsterClass, $availableTrainingClasses, true)
            )
        ) {
            $error = 'Adversaire invalide pour le mode entraînement.';
        } else {
            $hero = $heroesManager->find($submittedHeroId);

            if ($hero === null || $hero->getHP() <= 0) {
                $error = 'Ce personnage ne peut pas combattre.';
            } else {
                if ($gameMode === 'training') {
                    $hero = clone $hero;
                }
                $hero->setActiveRogueCardUpgrades(
                    $gameMode === 'dungeon' ? $hero->getRogueCardUpgrades() : []
                );
                if ($gameMode !== 'dungeon') {
                    $hero->setHP(min($hero->getHP(), $hero->getMaxHP()));
                    $hero->setMP(min($hero->getMP(), $hero->getMaxMP()));
                }
                $effects = [
                    'freeze' => false,
                    'poison' => false,
                    'burn_damage' => 0
                ];
                $specialState = [];
                if ($gameMode === 'boss_rush') {
                    $bossClasses = $fightManager->getAvailableBossMonsterClasses();
                    if ($bossClasses === []) {
                        throw new LogicException('Aucun boss n’est disponible pour le mode Boss Rush.');
                    }
                    $monsterClass = $bossClasses[array_rand($bossClasses)];
                } elseif ($gameMode === 'training') {
                    $monsterClass = $submittedMonsterClass;
                } elseif (
                    $hero->getMonstersSurvived() > 0
                    && $hero->getMonstersSurvived() % 5 === 0
                ) {
                    $bossClasses = $fightManager->getAvailableBossMonsterClasses();
                    if ($bossClasses === []) {
                        throw new LogicException('Aucun boss n’est disponible pour le mode Dungeon.');
                    }
                    $monsterClass = $bossClasses[array_rand($bossClasses)];
                } else {
                    $monsterClass = null;
                }
                if ($monsterClass === 'Ouroboros' && $gameMode !== 'training') {
                    $specialState[Ouroboros::HP_BONUS_STATE_KEY] = $getOuroborosHPBonus(
                        (int) $hero->getId()
                    );
                }
                $monster = $fightManager->createMonster($monsterClass, null, $hero);
                $heroEffects = [
                    'freeze' => false,
                    'poison' => false,
                    'burn_damage' => 0
                ];
                $cardChoices = [];
                $fightLog = $fightManager->startFight(
                    $hero,
                    $monster,
                    $heroEffects,
                    $effects,
                    $specialState
                );
                if ($monster->getHP() <= 0) {
                    if ($gameMode !== 'training') {
                        $hero->surviveMonster();
                    }
                    if ($fightManager->isBossMonster($monster)) {
                        if ($gameMode === 'dungeon') {
                            $hero->setRogueCardUpgrades([]);
                            $hero->setActiveRogueCardUpgrades([]);
                        }
                        $hero->setHP($hero->getMaxHP());
                        $hero->setMP($hero->getMaxMP());
                        $fightLog[] = 'Victoire contre le boss : tous les HP et MP du héros sont restaurés.';
                    } elseif ($hero instanceof Gambler) {
                        $fightLog[] = $hero->onMonsterDefeated();
                    }
                    if ($monster instanceof Ouroboros && $gameMode !== 'training') {
                        $nextBonus = $recordOuroborosDefeat((int) $hero->getId());
                        $fightLog[] = 'Ouroboros gagnera '
                            . (Ouroboros::BASE_HP + $nextBonus)
                            . ' HP lors de sa prochaine apparition.';
                    }
                    if (
                        $gameMode === 'dungeon'
                        && !$fightManager->isBossMonster($monster)
                        && $hero->getHP() > 0
                    ) {
                        $cardChoices = $chooseRogueCards($hero);
                    } else {
                        $cardChoices = [];
                    }
                    if ($gameMode === 'training' && !$fightManager->isBossMonster($monster)) {
                        $fightLog[] = $hero->getName()
                            . ' a vaincu '
                            . $monster->getName()
                            . ' en entraînement.';
                    }
                }
                if (
                    $hero->getHP() <= 0
                    && $monster instanceof Ditto
                    && $monster->getCopiedHero() instanceof Gambler
                ) {
                    $monster->restoreAfterVictory();
                    $monster->storeState($specialState);
                    $fightLog[] = 'Addiction restaure tous les HP et MP de Ditto.';
                }
                if ($hero->getHP() <= 0 && $gameMode !== 'training') {
                    $resetOuroborosHPBonus((int) $hero->getId());
                }
                if ($gameMode !== 'training') {
                    $heroesManager->update($hero);
                }

                $_SESSION[$fightKey] = [
                    'hero_id' => (int) $hero->getId(),
                    'game_mode' => $gameMode,
                    'monster_class' => $monster->getClassName(),
                    'monster_hp' => $monster->getHP(),
                    'log' => $fightLog,
                    'pending_effect' => null,
                    'effects' => $effects,
                    'hero_effects' => $heroEffects,
                    'special_state' => $specialState,
                    'card_choice_pending' => $cardChoices !== [],
                    'card_choices' => $cardChoices
                ];
                if ($gameMode === 'training') {
                    $_SESSION[$fightKey]['training_hero'] = $hero;
                }
            }
        }

        if ($error !== null) {
            $_SESSION['fight_error'] = $error;
        }

        header('Location: fight.php');
        exit;
    }

    $error = $_SESSION['fight_error'] ?? null;
    unset($_SESSION['fight_error']);
    $fight = $_SESSION[$fightKey] ?? null;
    $hero = null;
    $monster = null;

    if (is_array($fight)) {
        $isTrainingFight = ($fight['game_mode'] ?? 'dungeon') === 'training';
        $hero = $isTrainingFight && (($fight['training_hero'] ?? null) instanceof Hero)
            ? $fight['training_hero']
            : $heroesManager->find($fight['hero_id']);
        if ($hero !== null) {
            $hero->setActiveRogueCardUpgrades(
                ($fight['game_mode'] ?? 'dungeon') === 'dungeon'
                    ? $hero->getRogueCardUpgrades()
                    : []
            );
            $monster = $fightManager->createMonster(
                $fight['monster_class'],
                null,
                $hero,
                $fight['special_state'] ?? []
            );
            $monster->setHP($fight['monster_hp']);
        } else {
            unset($_SESSION[$fightKey]);
            $fight = null;
            $error = 'Personnage introuvable.';
        }
    }

    $fightIsOver = !is_array($fight)
        || $hero->getHP() <= 0
        || $monster->getHP() <= 0;
    $monsterPortraits = [
        'Ogre' => 'images/monster/orc.webp',
        'Sorcier' => 'images/monster/Wizard.png',
        'Fantassin' => 'images/monster/Fantassin.webp',
        'Angel' => 'images/monster/Virtue.webp',
        'Evil Ant' => 'images/monster/evil-ant.png',
        'Ditto' => 'images/Boss-monsters/Ditto.png',
        'Nuclear Warhead of Doom and Despair' => 'images/Boss-monsters/Nuclear-bomb.png',
        'NoWell' => 'images/Boss-monsters/NoWell.png',
        'Mario Neutral Air' => 'images/Boss-monsters/Mario-Neutral-Air.webp',
        'Ouroboros' => 'images/Boss-monsters/Ouroboros.png'
    ];
    $monsterPortrait = is_array($fight) && $monster !== null
        ? ($monsterPortraits[$monster->getClassName()] ?? null)
        : null;
    $specialActionDescription = $hero === null
        ? ''
        : match ($hero->getClassName()) {
            'mage' => 'Restaure jusqu’à 60 HP au Mage. Cette action coûte 20 MP.',
            'archer' => 'Augmente définitivement l’esquive de l’Archer de 25 %. Cette action coûte 20 MP.',
            'guerrier' => 'La prochaine attaque reçue est convertie en soins. Cette action coûte 15 MP.',
            'vampire' => 'Active le mode Chauve-souris pour les 3 prochaines attaques. Cette action coûte 20 MP.',
            'gambler' => 'Lance All In et déclenche un effet aléatoire, bénéfique ou risqué. Cette action coûte 30 MP.',
            'werewolf' => 'Restaure 1 à 10 HP et ajoute 1 à 10 dégâts à la prochaine attaque. Les bonus se cumulent. Cette action coûte 5 MP et ne passe pas le tour.',
            'flowery' => 'Joue la piste audio et retire tous les HP de l’adversaire. Gratuite et utilisable sans limite.',
            default => 'Utilise l’attaque spéciale propre à cette classe.',
        };

?>