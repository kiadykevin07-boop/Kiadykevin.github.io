<?php
session_start();
header('Content-Type: application/json');
// Informations de connexion à la base de données
$servername = "127.0.0.1"; // ou localhost
$dbname = "ecole";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erreur' => 'Méthode non autorisée']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Validation des champs
    if (empty($input['username']) || empty($input['password']) || empty($input['email']) || empty($input['role'])) {
        http_response_code(400);
        echo json_encode(['erreur' => 'Tous les champs sont obligatoires']);
        exit;
    }

    if (!in_array($input['role'], ['user','admin'])) {
        http_response_code(400);
        echo json_encode(['erreur' => 'Role invalide']);
        exit;
    }

    // Vérifier si username ou email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$input['username'], $input['email']]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['erreur' => 'Username ou email déjà utilisé']);
        exit;
    }

    // Insérer l'utilisateur avec mot de passe haché
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$input['username'], $hashedPassword, $input['role'], $input['email']]);

    http_response_code(201);
    echo json_encode(['message' => 'Utilisateur créé avec succès']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>