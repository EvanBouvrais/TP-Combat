<?php if (!empty($fight['card_choice_pending'])) { ?>
    <div class="rogue-card-modal">
        <section
            class="rogue-card-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="rogue-card-title"
        >
            <h2 id="rogue-card-title">Choisissez une amélioration</h2>
            <p>Choisissez une carte. Ses effets dureront jusqu’à la victoire contre le prochain boss.</p>
            <form action="fight.php" method="POST">
                <input type="hidden" name="action" value="choose_card">
                <input type="hidden" name="hero_id" value="<?= $hero->getId(); ?>">
                <div class="rogue-card-options">
                    <?php foreach ($fight['card_choices'] as $cardKey) { ?>
                        <?php $card = $rogueCardDefinitions[$cardKey]; ?>
                        <label class="rogue-card-option">
                            <input
                                type="radio"
                                name="card_key"
                                value="<?= htmlspecialchars($cardKey, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                            <span class="rogue-card-art">
                                <img
                                    src="<?= htmlspecialchars($card['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt=""
                                >
                                <strong><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                        </label>
                    <?php } ?>
                </div>
                <button type="submit">Choisir cette carte</button>
            </form>
        </section>
    </div>
<?php } ?>
