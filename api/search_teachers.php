<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? $_GET['query'] : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = new Database();

// Get all teachers and filter
$teachers = [];
$csvFile = __DIR__ . '/../data/teachers.csv';

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) == count($header)) {
            $teacher = array_combine($header, $data);
            // Search in name, cnic, subject (with safe checks)
            $name = isset($teacher['name']) ? $teacher['name'] : '';
            $cnic = isset($teacher['cnic']) ? $teacher['cnic'] : '';
            $subject = isset($teacher['subject']) ? $teacher['subject'] : '';
            
            if (stripos($name, $query) !== false || 
                stripos($cnic, $query) !== false || 
                stripos($subject, $query) !== false) {
                $teachers[] = $teacher;
            }
        }
    }
    fclose($handle);
}

// Limit results
$teachers = array_slice($teachers, 0, 10);

$results = array_map(function($t) {
    return [
        'id' => $t['id'],
        'name' => $t['name'],
        'cnic' => $t['cnic'],
        'subject' => $t['subject'] ?? 'N/A',
        'contact' => $t['contact'] ?? ''
    ];
}, $teachers);

echo json_encode($results);
?>
