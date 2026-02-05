<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['cnic']) && !isset($_GET['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'CNIC or Name is required']);
    exit;
}

// Search for a student with this CNIC or Name
foreach ($students as $student) {
    $match = false;
    
    if (isset($_GET['cnic'])) {
        $inputCnic = str_replace(['-', ' '], '', trim($_GET['cnic']));
        if (isset($student['father_cnic'])) {
            $storedCnic = str_replace(['-', ' '], '', trim($student['father_cnic']));
            if ($storedCnic === $inputCnic) $match = true;
        }
    } elseif (isset($_GET['name'])) {
        $inputName = strtolower(trim($_GET['name']));
        if (isset($student['father_name']) && strtolower(trim($student['father_name'])) === $inputName) {
            $match = true;
        }
    }

    if ($match) {
        $parent = [
            'father_name' => $student['father_name'],
            'father_contact' => $student['father_contact'],
            'father_cnic' => $student['father_cnic'],
            'father_cnic_front' => isset($student['father_cnic_front']) ? $student['father_cnic_front'] : '',
            'father_cnic_back' => isset($student['father_cnic_back']) ? $student['father_cnic_back'] : ''
        ];
        break; 
    }
}

if ($parent) {
    echo json_encode($parent);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Parent not found']);
}
?>
