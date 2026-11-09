<?php

// Définit la classe HeroesManager qui contient tout le CRUD d’un héros :
// Enregistrer un nouveau héros en base de données.
// Modifier un héros.
// Sélectionner un héros.
// Récupérer une liste de plusieurs héros vivants.
// Savoir si un héros existe.

class HeroesManager
{
    private $db;

    /**
     * Initialise le gestionnaire de héros avec une connexion à la base de données.
     *
     * @param PDO $db Connexion à la base de données.
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Enregistre un nouveau héros dans la base de données.
     *
     * Récupère les informations du héros grâce à ses getters,
     * puis les insère dans la table heroes.
     *
     * Les améliorations de cartes du voleur sont converties en JSON
     * afin de pouvoir être stockées dans la base de données.
     *
     * Une fois l'insertion effectuée, récupère l'identifiant généré
     * par la base de données et l'attribue à l'objet Hero.
     *
     * @param Hero $hero Héros à enregistrer.
     */
    public function add(Hero $hero)
    {
        $query = $this->db->prepare(
            "INSERT INTO heroes (name, health_point, class_name, mana_point, image_path, dodge_chance_basis_points, monsters_survived, rogue_card_upgrades)
             VALUES (:name, :health_point, :class_name, :mana_point, :image_path, :dodge_chance_basis_points, :monsters_survived, :rogue_card_upgrades)"
        );

        $query->execute([
            "name" => $hero->getName(),
            "health_point" => $hero->getHP(),
            "class_name" => $hero->getClassName(),
            "mana_point" => $hero->getMP(),
            "image_path" => $hero->getImagePath(),
            "dodge_chance_basis_points" => $hero->getDodgeChanceBasisPoints(),
            "monsters_survived" => $hero->getMonstersSurvived(),
            "rogue_card_upgrades" => json_encode(
                $hero->getRogueCardUpgrades(),
                JSON_THROW_ON_ERROR
            )
        ]);

        // Récupère l'identifiant attribué automatiquement par la base de données.
        $id = $this->db->lastInsertId();

        // Attribue cet identifiant à l'objet Hero.
        $hero->setId($id);
    }

    /**
     * Récupère tous les héros encore en vie dans la base de données.
     *
     * Les héros sont triés selon le nombre de monstres vaincus,
     * du plus élevé au plus faible. En cas d'égalité, ils sont triés
     * par identifiant croissant.
     *
     * Chaque résultat SQL est utilisé pour reconstruire un objet Hero.
     *
     * @return Hero[] Liste des héros vivants.
     */
    public function findAllAlive()
    {
        $query = $this->db->prepare(
            "SELECT * FROM heroes
            WHERE health_point > 0
            ORDER BY monsters_survived DESC, id ASC"
        );

        $query->execute();

        // Récupère toutes les lignes correspondant à la requête.
        $heroes = $query->fetchAll();

        // Prépare un tableau qui contiendra les objets Hero reconstruits.
        $heroesList = [];

        // Parcourt chaque ligne récupérée depuis la base de données.
        foreach ($heroes as $heroData) {

            // Crée un héros à partir de son nom et de sa classe.
            $hero = Hero::create(
                $heroData['name'],
                $heroData['class_name'] ?? 'hero'
            );

            // Restaure les différentes caractéristiques du héros.
            $hero->setHP($heroData['health_point']);
            $hero->setMP($heroData['mana_point'] ?? Hero::MAX_MP);
            $hero->setImagePath($heroData['image_path'] ?? null);

            // Convertit les valeurs récupérées en entiers avant de les attribuer.
            $hero->setDodgeChanceBasisPoints(
                (int) ($heroData['dodge_chance_basis_points'] ?? 0)
            );

            $hero->setMonstersSurvived(
                (int) ($heroData['monsters_survived'] ?? 0)
            );

            // Décode les améliorations enregistrées au format JSON.
            $hero->setRogueCardUpgrades(json_decode(
                $heroData['rogue_card_upgrades'] ?? '[]',
                true,
                512,
                JSON_THROW_ON_ERROR
            ));

            // Initialise les améliorations actives avec celles récupérées.
            $hero->setActiveRogueCardUpgrades($hero->getRogueCardUpgrades());

            // Restaure l'identifiant du héros.
            $hero->setId($heroData['id']);

            // Ajoute l'objet Hero reconstruit à la liste.
            $heroesList[] = $hero;
        }

        return $heroesList;
    }

    /**
     * Recherche un héros dans la base de données grâce à son identifiant.
     *
     * Si aucun héros ne correspond à cet identifiant, retourne null.
     * Sinon, reconstruit et retourne un objet Hero avec les données récupérées.
     *
     * @param int $id Identifiant du héros recherché.
     *
     * @return Hero|null Objet Hero trouvé, ou null si aucun résultat.
     */
    public function find($id)
    {
        $query = $this->db->prepare(
            "SELECT * FROM heroes WHERE id = :id"
        );

        // Exécute la requête en associant l'identifiant au paramètre :id.
        $query->execute([
            'id' => $id
        ]);

        // Récupère le résultat de la requête.
        $heroData = $query->fetch();

        // Si aucun héros n'a été trouvé, retourne null.
        if (!$heroData) {
            return null;
        }

        // Reconstruit un objet Hero à partir des données récupérées.
        $hero = Hero::create(
            $heroData['name'],
            $heroData['class_name'] ?? 'hero'
        );

        // Restaure les caractéristiques du héros.
        $hero->setHP($heroData['health_point']);
        $hero->setMP($heroData['mana_point'] ?? Hero::MAX_MP);
        $hero->setImagePath($heroData['image_path'] ?? null);

        $hero->setDodgeChanceBasisPoints(
            (int) ($heroData['dodge_chance_basis_points'] ?? 0)
        );

        $hero->setMonstersSurvived(
            (int) ($heroData['monsters_survived'] ?? 0)
        );

        // Décode les améliorations de cartes stockées au format JSON.
        $hero->setRogueCardUpgrades(json_decode(
            $heroData['rogue_card_upgrades'] ?? '[]',
            true,
            512,
            JSON_THROW_ON_ERROR
        ));

        // Restaure l'identifiant du héros.
        $hero->setId($heroData['id']);

        return $hero;
    }

    /**
     * Met à jour les informations d'un héros déjà enregistré en base de données.
     *
     * Utilise l'identifiant du héros pour retrouver sa ligne dans la table heroes,
     * puis actualise ses points de vie, ses points de mana, sa chance d'esquive,
     * son nombre de monstres vaincus et ses améliorations de cartes.
     *
     * Les améliorations de cartes sont converties en JSON avant leur enregistrement.
     *
     * @param Hero $hero Héros contenant les nouvelles informations à enregistrer.
     */
    public function update(Hero $hero)
    {
        $query = $this->db->prepare(
            "UPDATE heroes
            SET health_point = :health_point,
                mana_point = :mana_point,
                dodge_chance_basis_points = :dodge_chance_basis_points,
                monsters_survived = :monsters_survived,
                rogue_card_upgrades = :rogue_card_upgrades
            WHERE id = :id"
        );

        $query->execute([
            "id" => $hero->getId(),
            "health_point" => $hero->getHP(),
            "mana_point" => $hero->getMP(),
            "dodge_chance_basis_points" => $hero->getDodgeChanceBasisPoints(),
            "monsters_survived" => $hero->getMonstersSurvived(),
            "rogue_card_upgrades" => json_encode(
                $hero->getRogueCardUpgrades(),
                JSON_THROW_ON_ERROR
            )
        ]);
    }
}

?>