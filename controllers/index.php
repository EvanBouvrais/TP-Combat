<?php

    if (!defined('APP_ENTRY')) {
        http_response_code(404);
        exit;
    }

    require dirname(__DIR__) . "/config/autoload.php";
    require dirname(__DIR__) . "/config/db.php";

    $heroesManager = new HeroesManager($db);

    $formError = null;
    $formValues = ['name' => '', 'class_name' => ''];
    $classOptions = [
        'mage' => 'Mage',
        'guerrier' => 'Guerrier',
        'archer' => 'Archer',
        'vampire' => 'Vampire',
        'gambler' => 'Gambler',
        'werewolf' => 'Werewolf'
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedName = $_POST['name'] ?? '';
        $name = is_string($submittedName) ? trim($submittedName) : '';
        $className = $_POST['class_name'] ?? '';
        $formValues = [
            'name' => $name,
            'class_name' => is_string($className) ? $className : ''
        ];
        $imagePath = null;

        if ($name === '') {
            $formError = 'Veuillez saisir un nom.';
        } elseif (!is_string($className) || !array_key_exists($className, $classOptions)) {
            $formError = 'Veuillez sélectionner une classe valide.';
        } else {
            if (
                strcasecmp($name, 'Jarona') === 0
                && $className === 'vampire'
            ) {
                $className = 'flowery';
                $formValues['class_name'] = 'vampire';
            }

            $uploadedImage = $_FILES['image'] ?? null;
            if ($uploadedImage !== null && $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($uploadedImage['error'] !== UPLOAD_ERR_OK) {
                    $formError = 'Le téléversement de l’image a échoué.';
                } elseif ($uploadedImage['size'] > 5 * 1024 * 1024) {
                    $formError = 'L’image ne doit pas dépasser 5 Mo.';
                } else {
                    $imageInfo = getimagesize($uploadedImage['tmp_name']);
                    $allowedImageTypes = [
                        IMAGETYPE_JPEG => 'jpg',
                        IMAGETYPE_PNG => 'png',
                        IMAGETYPE_GIF => 'gif',
                        IMAGETYPE_WEBP => 'webp'
                    ];

                    if ($imageInfo === false || !isset($allowedImageTypes[$imageInfo[2]])) {
                        $formError = 'Format invalide. Utilisez une image JPG, PNG, GIF ou WebP.';
                    } else {
                        $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
                            . DIRECTORY_SEPARATOR . 'heroes';

                        if (
                            !is_dir($uploadDirectory)
                            && !mkdir($uploadDirectory, 0755, true)
                            && !is_dir($uploadDirectory)
                        ) {
                            throw new RuntimeException('Impossible de créer le dossier de téléversement.');
                        }

                        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$imageInfo[2]];
                        $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

                        if (!move_uploaded_file($uploadedImage['tmp_name'], $destination)) {
                            throw new RuntimeException('Impossible d’enregistrer l’image téléversée.');
                        }

                        $imagePath = 'uploads/heroes/' . $fileName;
                    }
                }
            }

            if ($formError === null) {
                $hero = Hero::create($name, $className);
                $hero->setImagePath($imagePath);
                $heroesManager->add($hero);

                header("Location: index.php");
                exit;
            }
        }
    }

?>