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
    if (empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['erreur' => 'Email et password requis']);
        exit;
    }

    // Vérifier l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($input['password'], $user['password'])) {
        // Initialiser la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        echo json_encode(['message' => 'Connexion réussie', 'role' => $user['role']]);
    } else {
        http_response_code(401);
        echo json_encode(['erreur' => 'Identifiants incorrects']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>