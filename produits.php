<?php
session_start();
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=ecole;charset=utf8", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['erreur' => 'Non authentifié']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // 🔹 Lire les produits
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Produit non trouvé']);
                exit;
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM produits ORDER BY id DESC");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($result);

    } elseif ($method === 'POST') {
        // 🔹 Créer un produit (admin seulement)
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['erreur' => 'Accès refusé']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation simple
        if (
            empty($input['nom']) ||
            empty($input['categorie']) ||
            !isset($input['quantite']) ||
            !isset($input['prix'])
        ) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champs requis manquants']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO produits (nom, categorie, quantite, prix) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            htmlspecialchars($input['nom']),
            htmlspecialchars($input['categorie']),
            (int)$input['quantite'],
            (float)$input['prix']
        ]);

        http_response_code(201);
        echo json_encode(['message' => 'Produit créé avec succès']);

    } elseif ($method === 'PUT') {
        // 🔹 Modifier un produit (admin seulement)
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['erreur' => 'Accès refusé']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id']) || !is_numeric($input['id'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'ID invalide']);
            exit;
        }

        // Vérifier si le produit existe
        $check = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
        $check->execute([$input['id']]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Produit non trouvé']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE produits SET nom=?, categorie=?, quantite=?, prix=? WHERE id=?");
        $stmt->execute([
            htmlspecialchars($input['nom']),
            htmlspecialchars($input['categorie']),
            (int)$input['quantite'],
            (float)$input['prix'],
            (int)$input['id']
        ]);

        echo json_encode(['message' => 'Produit mis à jour avec succès']);

    } elseif ($method === 'DELETE') {
        // 🔹 Supprimer un produit (admin seulement)
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['erreur' => 'Accès refusé']);
            exit;
        }

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'ID invalide']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Produit non trouvé']);
            exit;
        }

        http_response_code(204); // Pas de contenu
    } else {
        http_response_code(405);
        echo json_encode(['erreur' => 'Méthode non autorisée']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>