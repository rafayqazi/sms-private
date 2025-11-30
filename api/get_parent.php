<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['cnic'])) {
    http_response_code(400);
    echo json_encode(['error' => 'CNIC is required']);
    exit;
}

// Normalize the input CNIC (remove dashes and spaces)
$inputCnic = str_replace(['-', ' '], '', trim($_GET['cnic']));

$db = new Database();
$students = $db->readData();

$parent = null;

// Search for the first student with this Father CNIC
foreach ($students as $student) {
    if (isset($student['father_cnic'])) {
        // Normalize the stored CNIC as well
        $storedCnic = str_replace(['-', ' '], '', trim($student['father_cnic']));
        
        if ($storedCnic === $inputCnic) {
            $parent = [
                'father_name' => $student['father_name'],
                'father_contact' => $student['father_contact'],
                'father_cnic_front' => isset($student['father_cnic_front']) ? $student['father_cnic_front'] : '',
                'father_cnic_back' => isset($student['father_cnic_back']) ? $student['father_cnic_back'] : ''
            ];
            break; 
        }
    }
}

if ($parent) {
    echo json_encode($parent);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Parent not found']);
}
?>
