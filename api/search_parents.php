<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = new Database();
// We need to fetch students to extract parent info
// In a real DB, we'd query the parents table, but here parents are students' attributes
$students = $db->readData();
$parents = [];
$seen = [];

foreach ($students as $student) {
    if (!isset($student['father_name'])) continue;
    
    $name = $student['father_name'];
    $lowerName = strtolower($name);
    
    // Check if name matches query (partial match)
    if (strpos($lowerName, $query) !== false) {
        $cnic = isset($student['father_cnic']) ? $student['father_cnic'] : '';
        $key = $lowerName . '|' . $cnic;
        
        if (!isset($seen[$key])) {
            $parents[] = [
                'father_name' => $name,
                'father_cnic' => $cnic,
                'father_contact' => isset($student['father_contact']) ? $student['father_contact'] : '',
                'father_cnic_front' => isset($student['father_cnic_front']) ? $student['father_cnic_front'] : '',
                'father_cnic_back' => isset($student['father_cnic_back']) ? $student['father_cnic_back'] : ''
            ];
            $seen[$key] = true;
        }
    }
}

// Return top 10 matches
echo json_encode(array_slice($parents, 0, 10));
?>
