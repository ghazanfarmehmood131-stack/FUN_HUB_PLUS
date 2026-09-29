<?php
/**
 * Authentication API Endpoint
 * Handles sign in, account registration, logout, and session check with MySQL persistence.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? $_GET['action'] ?? 'status';

if ($action === 'status') {
    $user = getCurrentUser();
    echo json_encode([
        'authenticated' => (bool)$user,
        'user' => $user
    ]);
    exit;
}

if ($action === 'login') {
    $email = trim(strtolower($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your password.']);
        exit;
    }

    $db = getDb();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password combination.']);
    }
    exit;
}

if ($action === 'signup') {
    $name = trim($input['name'] ?? '');
    $email = trim(strtolower($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (mb_strlen($name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Name must be at least 2 characters long.']);
        exit;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
        exit;
    }

    $db = getDb();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
        exit;
    }

    // Check existing email
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An archivist account with this email already exists.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $db->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $insertStmt->execute([$name, $email, $hash]);
    $newId = $db->lastInsertId();

    $_SESSION['user_id'] = $newId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'user';

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'role' => 'user'
        ]
    ]);
    exit;
}

if ($action === 'logout') {
    $_SESSION = [];
    if (session_id()) {
        session_destroy();
    }
    echo json_encode(['success' => true]);
    exit;
}
