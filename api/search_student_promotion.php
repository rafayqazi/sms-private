<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

try {
    $db = new Database();
    $allStudents = $db->readData();
    
    $results = array_filter($allStudents, function($student) use ($query) {
        $isActive = empty($student['student_status']) || $student['student_status'] === 'Active';
        if (!$isActive) return false;

        $nameMatch = stripos($student['student_name'] ?? '', $query) !== false;
        $grMatch = stripos($student['gr_no'] ?? '', $query) !== false;
        
        return $nameMatch || $grMatch;
    });

    // Format for frontend
    $formattedResults = array_map(function($student) {
        return [
            'id' => $student['id'],
            'student_name' => $student['student_name'],
            'gr_no' => $student['gr_no'],
            'father_name' => $student['father_name'],
            'current_class' => $student['current_class'],
            'profile_image' => $student['profile_image'] ?? ''
        ];
    }, array_values($results));

    echo json_encode(array_slice($formattedResults, 0, 10)); // Limit to top 10 results
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
