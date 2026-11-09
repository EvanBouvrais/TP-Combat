<?php
    // Permet le chargement automatique des classes et
    // configure PHP pour avoir de meilleurs messages d’erreurs.   

    // Strict
    declare(strict_types=1);

    // Enable all PHP errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Pretty errors
    ini_set("html_errors", "1");
    ini_set("error_prepend_string", "<pre style='color: #333; font-face:monospace; white-space: pre-wrap;font-size: 17px;color:#880808'>");
    ini_set("error_append_string", "</pre>");

    // Autoload logic
    function chargerClasse($classname)
    {
        $classDirectories = [
            'Hero' => 'Managers',
            'HeroesManager' => 'Managers',
            'Monster' => 'Managers',
            'FightsManager' => 'Managers',
            'Mage' => 'hero-classes',
            'Guerrier' => 'hero-classes',
            'Archer' => 'hero-classes',
            'Vampire' => 'hero-classes',
            'Flowery' => 'hero-classes',
            'Gambler' => 'hero-classes',
            'Werewolf' => 'hero-classes',
            'Ogre' => 'monstres-classes',
            'Sorcier' => 'monstres-classes',
            'Fantassin' => 'monstres-classes',
            'Angel' => 'monstres-classes',
            'EvilAnt' => 'monstres-classes',
            'Ditto' => 'Boss-monsters-classes',
            'NuclearWarhead' => 'Boss-monsters-classes',
            'NoWell' => 'Boss-monsters-classes',
            'MarioNeutralAir' => 'Boss-monsters-classes',
            'Ouroboros' => 'Boss-monsters-classes'
        ];
        $directory = $classDirectories[$classname] ?? '';

        if ($directory !== '') {
            require __DIR__ . '/../classes/' . $directory . '/' . $classname . '.php';
            return;
        }

        require __DIR__ . '/../classes/' . $classname . '.php';
    }
    spl_autoload_register('chargerClasse');

    // Session
    session_start();

?>