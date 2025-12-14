<?php
// Mock GET request
$_GET['cnic'] = '41307-2298247-1';

// Adjust paths since we are running from root
require_once 'includes/db.php';

// Copy-paste logic from api/get_parent.php
$inputCnic = str_replace(['-', ' '], '', trim($_GET['cnic']));

$db = new Database();
$students = $db->readData();

$parent = null;

echo "Searching for: " . $inputCnic . "\n";

foreach ($students as $student) {
    if (isset($student['father_cnic'])) {
        $storedCnic = str_replace(['-', ' '], '', trim($student['father_cnic']));
        
        if ($storedCnic === $inputCnic) {
            echo "Match Found!\n";
            echo "Stored Raw: " . $student['father_cnic'] . "\n";
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
    echo "Parent not found";
}
?>
