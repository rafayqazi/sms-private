<?php
require_once '../includes/parent_auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['child_id']) || !isset($_GET['type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$childId = $_GET['child_id'];
$type = $_GET['type'];
$parentCnic = getLoggedInParentCnic();

$db = new Database();

// Security Check: Ensure this child belongs to the parent
$children = $db->getParentChildrenByCnic($parentCnic);
$isAuthorized = false;
$childData = null;

foreach ($children as $child) {
    if ($child['id'] == $childId) {
        $isAuthorized = true;
        $childData = $child;
        break;
    }
}

if (!$isAuthorized) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$response = [
    'success' => true,
    'child_name' => $childData['student_name'],
    'type' => $type,
    'data' => []
];

if ($type === 'attendance') {
    $response['data'] = $db->getStudentAttendanceHistory($childId);
} elseif ($type === 'results') {
    $response['data'] = $db->getStudentResults($childId);
} elseif ($type === 'certificates') {
    // Provide direct links to valid print pages
    // Character Certificate
    $response['data'][] = [
        'name' => 'Character Certificate',
        'icon' => 'fas fa-user-check',
        'color' => 'text-purple-600 bg-purple-50',
        'link' => "print_character_certificate.php?id={$childId}"
    ];
    // Testimonial
    $response['data'][] = [
        'name' => 'Testimonial Certificate',
        'icon' => 'fas fa-award',
        'color' => 'text-amber-600 bg-amber-50',
        'link' => "print_testimonial_certificate.php?id={$childId}"
    ];
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid activity type']);
    exit;
}

echo json_encode($response);
