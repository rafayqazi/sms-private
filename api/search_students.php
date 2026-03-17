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
$includeAlumni = isset($_GET['include_alumni']) && $_GET['include_alumni'] == 1;
$alumniOnly = isset($_GET['alumni_only']) && $_GET['alumni_only'] == 1;

$students = $db->filterStudents(['search' => $query, 'include_alumni' => $includeAlumni]);

if ($alumniOnly) {
    $students = array_filter($students, function($s) {
        return isset($s['student_status']) && strcasecmp($s['student_status'], 'Alumni') === 0;
    });
}

// Limit results
$students = array_slice($students, 0, 15);

$results = array_map(function($s) {
    return [
        'id' => $s['id'],
        'student_name' => $s['student_name'],
        'value' => $s['student_name'], // For compatibility
        'gr_no' => $s['gr_no'],
        'current_class' => $s['current_class'],
        'father_name' => $s['father_name'] ?? 'N/A',
        'caste' => $s['caste'] ?? '',
        'admission_class' => $s['admission_class'] ?? '',
        'admission_date' => $s['admission_date'] ?? '',
        'updated_at' => $s['updated_at'] ?? '',
        'student_status' => $s['student_status'] ?? 'Active',
        'date_of_birth' => $s['date_of_birth'] ?? ''
    ];
}, $students);

echo json_encode($results);
?>
