<?php
define('APP_ENTRY', true);
require __DIR__ . '/controllers/fight.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combat</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/fight.css">
    <link rel="stylesheet" href="styles/RogueCards.css">
</head>


<body class="<?= is_array($fight) && $hero !== null && $monster !== null
    ? 'combat-active' . ($fightManager->isBossMonster($monster) ? ' combat-boss' : '')
    : ''; ?>">
    <?php if ($error !== null) { ?>
        <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <?php if (is_array($fight) && $hero !== null && $monster !== null) { ?>
        <aside class="survivor-counter" aria-label="Informations sur les classes">
            <p class="survivor-count">
                Monstres vaincus : <?= $hero->getMonstersSurvived(); ?>
            </p>
            <p>
                <?= htmlspecialchars($hero->getClassLabel(), ENT_QUOTES, 'UTF-8'); ?> —
                <?= htmlspecialchars($hero->getPassiveDescription(), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p>
                <?= $fightManager->isBossMonster($monster) ? 'Boss ' : ''; ?>
                <?= htmlspecialchars($monster->getClassName(), ENT_QUOTES, 'UTF-8'); ?> —
                <?= htmlspecialchars($monster->getPassiveDescription(), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </aside>
        <main class="combat-workspace">
            <section class="combat-log" aria-labelledby="combat-log-title">
                <h2 id="combat-log-title">Journal du combat</h2>
                <div class="combat-log-entries">
                    <?php foreach ($fight['log'] as $fightResult) { ?>
                        <?php
                            $heroName = (string) $hero->getName();
                            $monsterName = (string) $monster->getName();
                            $logParts = preg_split(
                                '/(?<![\p{L}\p{N}_])('
                                    . preg_quote($heroName, '/')
                                    . '|'
                                    . preg_quote($monsterName, '/')
                                    . ')(?![\p{L}\p{N}_])/u',
                                $fightResult,
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE
                            );
                        ?>
                        <p>
                            <?php if ($logParts === false) { ?>
                                <?= htmlspecialchars($fightResult, ENT_QUOTES, 'UTF-8'); ?>
                            <?php } else { ?>
                                <?php foreach ($logParts as $logPart) { ?>
                                    <?php if ($logPart === $heroName) { ?>
                                        <span class="hero-name hero-class-<?= htmlspecialchars($hero->getClassName(), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($logPart, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } elseif ($logPart === $monsterName) { ?>
                                        <span class="monster-name"><?= htmlspecialchars($logPart, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } else { ?>
                                        <?= htmlspecialchars($logPart, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>
                        </p>
                    <?php } ?>
                </div>
            </section>
        </main>

        <?php
            $activeRogueCardUpgrades = ($fight['game_mode'] ?? null) === 'dungeon'
                ? $hero->getRogueCardUpgrades()
                : [];
        ?>
        <?php if (!$fightIsOver) { ?>
            <div class="combat-actions">
                <form action="fight.php" method="POST">
                    <input type="hidden" name="hero_id" value="<?= $hero->getId(); ?>">
                    <div
                        class="action-button-wrapper"
                        data-action-info="Inflige 0 à 50 dégâts. Une attaque normale prépare aussi une réduction de 20 % des dégâts de la prochaine riposte reçue."
                    >
                        <button type="submit" name="action" value="attack">Attaquer</button>
                    </div>
                    <div
                        class="action-button-wrapper"
                        data-action-info="Inflige 0 à 75 dégâts, mais ne prépare pas la réduction de dégâts accordée par une attaque normale."
                    >
                        <button type="submit" name="action" value="all_out">All out</button>
                    </div>
                    <div
                        class="action-button-wrapper"
                        data-action-info="<?= htmlspecialchars(
                            !empty($fight['pending_effect'])
                                ? 'Un effet est déjà préparé : il sera appliqué à la prochaine attaque.'
                                : 'Coûte 10 MP et prépare un effet aléatoire (gel, poison, brûlure ou Vampirisme) qui sera appliqué à la prochaine attaque.',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >
                        <button
                            type="submit"
                            name="action"
                            value="spell"
                            <?= $hero->getMP() < 10 || !empty($fight['pending_effect']) ? 'disabled' : ''; ?>
                        >
                            <?= !empty($fight['pending_effect'])
                                ? 'Effet en attente'
                                : 'Effet aléatoire (10 MP)'; ?>
                        </button>
                    </div>
                    <?php if ($hero->getSpecialAttackCost() > 0 || $hero instanceof Flowery) { ?>
                        <div
                            class="action-button-wrapper"
                            data-action-info="<?= htmlspecialchars($specialActionDescription, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <button
                                type="submit"
                                name="action"
                                value="special"
                                <?= $hero instanceof Flowery ? 'data-play-audio="jarona"' : ''; ?>
                                <?= $hero->getMP() < $hero->getSpecialAttackCost()
                                    || !$hero->canUseSpecialAttack($fight['special_state'] ?? [])
                                    ? 'disabled'
                                    : ''; ?>
                            >
                                <?= htmlspecialchars($hero->getSpecialAttackName(), ENT_QUOTES, 'UTF-8'); ?>
                                <?= $hero instanceof Flowery
                                    ? '(Gratuit)'
                                    : '(' . $hero->getSpecialAttackCost() . ' MP)'; ?>
                            </button>
                        </div>
                    <?php } ?>
                </form>
                <form
                    action="fight.php"
                    method="POST"
                    onsubmit="return window.confirm('Voulez-vous vraiment déclarer forfait ?');"
                >
                    <input type="hidden" name="hero_id" value="<?= $hero->getId(); ?>">
                    <div
                        class="action-button-wrapper"
                        data-action-info="Abandonne le combat et met fin à la partie. Une confirmation sera demandée."
                    >
                        <button
                            class="forfeit-button"
                            type="submit"
                            name="action"
                            value="forfeit"
                        >Forfait</button>
                    </div>
                </form>
                <section
                    class="action-info-panel"
                    id="action-info-panel"
                    aria-label="Informations sur l’action"
                    aria-live="polite"
                    hidden
                >
                    <h2>Action</h2>
                    <p class="action-info-content"></p>
                </section>
            </div>
            <?php if ($activeRogueCardUpgrades !== []) { ?>
                <section class="active-rogue-cards" aria-labelledby="active-rogue-cards-title">
                    <h2 id="active-rogue-cards-title">Cartes bonus actives</h2>
                    <ul>
                        <?php foreach ($activeRogueCardUpgrades as $cardKey) { ?>
                            <?php if (isset($rogueCardDefinitions[$cardKey])) { ?>
                                <li>
                                    <img
                                        src="<?= htmlspecialchars($rogueCardDefinitions[$cardKey]['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                        alt=""
                                    >
                                    <span><?= htmlspecialchars($rogueCardDefinitions[$cardKey]['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                </section>
            <?php } ?>
            <?php if ($hero instanceof Flowery) { ?>
                <audio id="jarona-audio" src="Sound/deltarune-jarona.mp3" preload="auto"></audio>
            <?php } ?>
        <?php } else { ?>
            <div class="combat-actions">
                <p>
                    <?= $hero->getHP() <= 0
                        ? 'Votre héros a perdu le combat.'
                        : 'Le monstre a été vaincu !'; ?>
                </p>
                <?php if (
                    $hero->getHP() > 0
                    && $monster->getHP() <= 0
                    && empty($fight['card_choice_pending'])
                ) { ?>
                    <form action="fight.php" method="POST">
                        <input type="hidden" name="action" value="start">
                        <input type="hidden" name="hero_id" value="<?= $hero->getId(); ?>">
                        <input
                            type="hidden"
                            name="game_mode"
                            value="<?= htmlspecialchars($fight['game_mode'] ?? 'dungeon', ENT_QUOTES, 'UTF-8'); ?>"
                        >
                        <?php if (($fight['game_mode'] ?? 'dungeon') === 'training') { ?>
                            <input
                                type="hidden"
                                name="monster_class"
                                value="<?= htmlspecialchars($fight['monster_class'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        <?php } ?>
                        <button type="submit">Combat suivant avec ce personnage</button>
                    </form>
                <?php } ?>
                <form action="index.php" method="GET">
                    <button type="submit">Retour à la création ou au choix du personnage</button>
                </form>
            </div>
            <?php require __DIR__ . '/views/partials/rogue-card-modal.php'; ?>
        <?php } ?>
    <?php } ?>

    <?php if (is_array($fight) && $hero !== null && $monster !== null) { ?>
        <footer class="health-panel" aria-label="Ressources du combat">
            <div class="hero-health">
                <?php if ($hero->getImagePath() !== null) { ?>
                    <img
                        class="hero-portrait"
                        src="<?= htmlspecialchars($hero->getImagePath(), ENT_QUOTES, 'UTF-8'); ?>"
                        alt="Portrait de <?= htmlspecialchars($hero->getName(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                <?php } ?>
                <div class="hero-health-info">
                    <div class="health-resource">
                        <label for="hero-health">
                            <?= htmlspecialchars($hero->getName(), ENT_QUOTES, 'UTF-8'); ?>
                            — <?= max(0, $hero->getHP()); ?> / <?= $hero->getMaxHP(); ?> HP
                        </label>
                        <progress
                            id="hero-health"
                            max="<?= $hero->getMaxHP(); ?>"
                            value="<?= min($hero->getMaxHP(), max(0, $hero->getHP())); ?>"
                        ></progress>
                    </div>
                    <div class="mana">
                        <label for="hero-mana">
                            Mana — <?= $hero->getMP(); ?> / <?= $hero->getMaxMP(); ?> MP
                        </label>
                        <progress
                            id="hero-mana"
                            max="<?= $hero->getMaxMP(); ?>"
                            value="<?= $hero->getMP(); ?>"
                        ></progress>
                    </div>
                </div>
            </div>
            <div class="monster-health">
                <div class="monster-health-info">
                    <div class="health-resource">
                        <label for="monster-health">
                            <?= htmlspecialchars($monster->getName(), ENT_QUOTES, 'UTF-8'); ?>
                            — <?= max(0, $monster->getHP()); ?>
                            / <?= $monster->getMaxHP(); ?> HP
                        </label>
                        <progress
                            id="monster-health"
                            max="<?= $monster->getMaxHP(); ?>"
                            value="<?= min($monster->getMaxHP(), max(0, $monster->getHP())); ?>"
                        ></progress>
                    </div>
                    <?php if ($monster instanceof Ditto) { ?>
                        <div class="mana">
                            <label for="monster-mana">
                                Mana de Ditto — <?= $monster->getMana(); ?> / <?= Hero::MAX_MP; ?> MP
                            </label>
                            <progress
                                id="monster-mana"
                                max="<?= Hero::MAX_MP; ?>"
                                value="<?= $monster->getMana(); ?>"
                            ></progress>
                        </div>
                    <?php } ?>
                </div>
                <?php if ($monsterPortrait !== null) { ?>
                    <img
                        class="monster-portrait"
                        src="<?= htmlspecialchars($monsterPortrait, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="Portrait de <?= htmlspecialchars($monster->getName(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                <?php } ?>
            </div>
        </footer>
        <script src="scripts/fight.js" defer></script>
    <?php } ?>
</body>
</html>
