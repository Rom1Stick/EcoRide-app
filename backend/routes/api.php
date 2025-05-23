<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fichier de routes API simplifié sans routeur
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Répondre avec un statut 200 OK pour les requêtes OPTIONS (CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Définir le header de réponse comme JSON par défaut
header('Content-Type: application/json');

// Route d'inscription simplifiée
if ($requestUri === '/api/auth/register' && $method === 'POST') {
    try {
        // Récupérer les données de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Valider les données
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['password_confirmation'])) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Données incomplètes']);
            exit;
        }
        
        if ($data['password'] !== $data['password_confirmation']) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Les mots de passe ne correspondent pas']);
            exit;
        }
        
        // Connexion à la base de données MySQL
        $dbHost = env('DB_HOST', 'localhost');
        $dbPort = env('DB_PORT', '3306');
        $dbName = env('DB_DATABASE', 'ecoride');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Cet email est déjà utilisé']);
            exit;
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['email'], $hashedPassword]);
        
        // Générer un token JWT factice
        $token = bin2hex(random_bytes(32));
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $pdo->lastInsertId(),
                'name' => $data['name'],
                'email' => $data['email']
            ],
            'token' => $token
        ]);
    } catch (Exception $e) {
        // Journaliser l'erreur
        error_log("Erreur d'inscription: " . $e->getMessage());
        
        // Renvoyer une réponse d'erreur détaillée en mode DEBUG
        if (env('APP_DEBUG', false) === true) {
            http_response_code(500);
            echo json_encode([
                'error' => true, 
                'message' => 'Erreur interne lors de l\'inscription', 
                'debug' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'Erreur interne lors de l\'inscription']);
        }
    }
    exit;
} elseif (preg_match('/^\/api\/auth\/login(\/)?$/', $requestUri) && $method === 'POST') {
    // Route de connexion simplifiée
    try {
        // Récupérer les données de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Valider les données
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Données incomplètes']);
            exit;
        }
        
        // Connexion à la base de données MySQL
        $dbHost = env('DB_HOST', 'localhost');
        $dbPort = env('DB_PORT', '3306');
        $dbName = env('DB_DATABASE', 'ecoride');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Identifiants invalides']);
            exit;
        }
        
        // Générer un token JWT factice
        $token = bin2hex(random_bytes(32));
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ],
            'token' => $token
        ]);
    } catch (Exception $e) {
        // Journaliser l'erreur
        error_log("Erreur de connexion: " . $e->getMessage());
        
        // Renvoyer une réponse d'erreur détaillée en mode DEBUG
        if (env('APP_DEBUG', false) === true) {
            http_response_code(500);
            echo json_encode([
                'error' => true, 
                'message' => 'Erreur interne lors de la connexion', 
                'debug' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'Erreur interne lors de la connexion']);
        }
    }
    exit;
} else {
    // Route par défaut pour les autres endpoints
    http_response_code(404);
    echo json_encode(['error' => true, 'message' => 'Route non trouvée']);
    exit;
} 