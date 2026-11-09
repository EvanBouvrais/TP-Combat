<?php

    // Gère la connexion à la base de données avec une
    // instance de PDO, similaire à la correction du QCM.

    $db = new PDO(
        'mysql:host=localhost;dbname=fightcards;charset=utf8mb4',
        'root',
        ''
    );

?>