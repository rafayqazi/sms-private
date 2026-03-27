<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$cnic = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($cnic) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both CNIC and Password.']);
    exit;
}

$db = new Database();
$teacherData = $db->verifyTeacherLogin($cnic, $password);

if ($teacherData) {
    if (session_status() === PHP_SESSION_NONE) {
        // Set session cookie security parameters
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
    
    $_SESSION['user'] = $cnic;
    $_SESSION['user_type'] = 'teacher';
    $_SESSION['user_role'] = 'Editor'; // Default role for auto-login teachers
    $_SESSION['username'] = $cnic;
    $_SESSION['teacher_id'] = $teacherData['id'];
    $_SESSION['teacher_name'] = $teacherData['name'];
    $_SESSION['assigned_classes'] = []; // Educators will see all classes if this is empty and we handle it in functions.php, or we can pre-fill it.
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['show_welcome_animation'] = true;
    
    session_regenerate_id(true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Redirecting...',
        'redirect' => 'index.php'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid CNIC or Password. Please ensure the password is your Date of Birth (YYYY-MM-DD).']);
}
