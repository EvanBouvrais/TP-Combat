<?php
define('APP_ENTRY', true);
require __DIR__ . '/controllers/index.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un personnage</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <main>
        <section class="character-creation" aria-labelledby="character-creation-title">
            <h1 id="character-creation-title">Créer un personnage</h1>
            <p>Choisissez un nom, une classe et une image pour votre héros.</p>
            <img
                id="image-preview"
                class="image-preview"
                alt="Aperçu de l’image du héros"
                hidden
            >
            <span id="image-preview-status" class="image-preview-status" aria-live="polite"></span>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="name">Nom :</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($formValues['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-field">
                    <label for="class_name">Classe :</label>
                    <select id="class_name" name="class_name" required>
                        <option value="">Choisir une classe</option>
                        <?php foreach ($classOptions as $value => $label) { ?>
                            <option
                                value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                                <?= $formValues['class_name'] === $value ? 'selected' : ''; ?>
                            >
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-field image-field">
                    <label for="image">Image du héros (facultative, 5 Mo max) :</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                    >
                </div>

                <button type="submit">Créer mon personnage</button>
            </form>

            <?php if ($formError !== null) { ?>
                <p role="alert"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
        </section>
    </main>

    <?php
        $aliveCharacters = $heroesManager->findAllAlive();
        $fightManager = new FightsManager();
        $monsterOptions = [];
        foreach ($fightManager->getAvailableMonsterClasses() as $monsterClass) {
            $monsterOptions[$monsterClass] = $monsterClass;
        }
        foreach ($fightManager->getAvailableBossMonsterClasses() as $bossClass) {
            $monsterOptions[$bossClass] = $bossClass . ' (Boss)';
        }
    ?>
    <form id="game-start-form" class="game-start-form" action="fight.php" method="POST">
        <input type="hidden" name="action" value="start">
        <section class="mode-selection" aria-labelledby="mode-selection-title" tabindex="-1">
            <h2 id="mode-selection-title">Choisir un mode de jeu</h2>
            <div class="mode-card-grid">
                <label class="mode-card">
                    <input type="radio" name="game_mode" value="dungeon" checked>
                    <span class="mode-card-content">
                        <strong>Dungeon</strong>
                        <span>Enchaînez les monstres et affrontez un boss tous les cinq combats.</span>
                    </span>
                </label>
                <label class="mode-card">
                    <input type="radio" name="game_mode" value="boss_rush">
                    <span class="mode-card-content">
                        <strong>Boss Rush</strong>
                        <span>Affrontez uniquement des boss, avec récupération complète après chaque victoire.</span>
                    </span>
                </label>
                <label class="mode-card">
                    <input type="radio" name="game_mode" value="training">
                    <span class="mode-card-content">
                        <strong>Training Mode</strong>
                        <span>Choisissez un adversaire pour un combat sans conséquence sur votre progression.</span>
                    </span>
                </label>
            </div>
            <div id="training-opponent-field" class="training-opponent-field" hidden>
                <label for="training-opponent">Adversaire :</label>
                <select id="training-opponent" name="monster_class" disabled>
                    <?php foreach ($monsterOptions as $monsterClass => $monsterLabel) { ?>
                        <option value="<?= htmlspecialchars($monsterClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($monsterLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </section>

        <section class="hero-selection" aria-labelledby="hero-selection-title" tabindex="-1">
            <h2 id="hero-selection-title">Choisir un héros</h2>
            <?php if ($aliveCharacters === []) { ?>
                <p class="hero-selection-empty">Aucun héros vivant pour le moment.</p>
            <?php } else { ?>
                <div class="hero-card-grid">
                    <?php foreach ($aliveCharacters as $aliveCharacter) { ?>
                        <?php
                            $heroInitial = preg_match('/^./u', $aliveCharacter->getName(), $initialMatch)
                                ? strtoupper($initialMatch[0])
                                : '?';
                        ?>
                        <article class="hero-card">
                            <?php if ($aliveCharacter->getImagePath() !== null) { ?>
                                <img
                                    class="hero-card-image"
                                    src="<?= htmlspecialchars($aliveCharacter->getImagePath(), ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Portrait de <?= htmlspecialchars($aliveCharacter->getName(), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                            <?php } else { ?>
                                <div class="hero-card-placeholder" aria-hidden="true">
                                    <?= htmlspecialchars($heroInitial, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php } ?>
                            <div class="hero-card-content">
                                <h3><?= htmlspecialchars($aliveCharacter->getName(), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="hero-card-class">
                                    <span class="hero-class-<?= htmlspecialchars($aliveCharacter->getClassName(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($aliveCharacter->getClassLabel(), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </p>
                                <p class="hero-card-stat">
                                    HP <?= max(0, $aliveCharacter->getHP()); ?> / <?= $aliveCharacter->getMaxHP(); ?>
                                </p>
                                <p class="hero-card-stat">
                                    Kills <?= $aliveCharacter->getMonstersSurvived(); ?>
                                </p>
                            </div>
                            <button
                                class="hero-card-select"
                                type="submit"
                                name="hero_id"
                                value="<?= $aliveCharacter->getId(); ?>"
                                aria-label="Choisir <?= htmlspecialchars($aliveCharacter->getName(), ENT_QUOTES, 'UTF-8'); ?>"
                            ></button>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
        </section>
    </form>

    <script src="scripts/index.js" defer></script>
</body>
</html>