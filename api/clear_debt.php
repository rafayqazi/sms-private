<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$gr_no = trim($_POST['gr_no'] ?? '');

if (!$gr_no) {
    echo json_encode(['error' => 'GR No is required']);
    exit;
}

$db = new Database();
$student = $db->getStudentByGrNo($gr_no);
if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

if ($db->clearStudentDebt($gr_no)) {
    echo json_encode(['success' => true, 'message' => 'All debt cleared for ' . $student['student_name']]);
} else {
    echo json_encode(['error' => 'No outstanding debt found for this student']);
}
