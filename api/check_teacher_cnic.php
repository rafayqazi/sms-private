<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['cnic'])) {
    echo json_encode(['error' => 'CNIC is required']);
    exit;
}

$cnic = str_replace('-', '', $_GET['cnic']);
$excludeId = isset($_GET['exclude_id']) ? $_GET['exclude_id'] : null;

$db = new Database();
$exists = $db->isTeacherCnicExists($cnic, $excludeId);

echo json_encode(['exists' => $exists]);
