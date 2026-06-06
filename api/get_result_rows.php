<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$class = isset($_GET['class']) ? $_GET['class'] : '';
$examType = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

if (!$class || !$examType || !$year) {
    exit('');
}

$db = new Database();
$students = $db->getStudentsByClass($class);
$existingResults = $db->getResults($class, $examType, $year);
$activeSubjects = $db->getActiveSubjects($class, $examType, $year);

if (empty($students)) {
    echo '<tr><td colspan="12" class="px-6 py-4 text-center text-gray-500">No students found in this class.</td></tr>';
    exit;
}

foreach ($students as $student) {
    $studentId = $student['id'];
    $result = isset($existingResults[$studentId]) ? $existingResults[$studentId] : null;
    
    echo '<tr class="hover:bg-gray-50 transition-colors">';
    
    // GR No
    echo '<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">' . htmlspecialchars($student['gr_no']) . '</td>';
    
    // Name
    echo '<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 capitalize">' . htmlspecialchars($student['student_name']) . '</td>';
    
    $otherSubjectsData = ($result && isset($result['other_subjects'])) ? json_decode($result['other_subjects'], true) : [];
    if (!is_array($otherSubjectsData)) {
        $otherSubjectsData = [];
    }

    foreach ($activeSubjects as $subject) {
        if ($subject['type'] === 'standard') {
            $val = $result ? $result[$subject['key']] : '';
            $inputName = 'results[' . $studentId . '][' . $subject['key'] . ']';
        } else {
            $val = isset($otherSubjectsData[$subject['key']]) ? $otherSubjectsData[$subject['key']] : '';
            $inputName = 'results[' . $studentId . '][other_subjects][' . htmlspecialchars($subject['key']) . ']';
        }

        echo '<td class="px-2 py-3 whitespace-nowrap text-center">';
        echo '<input type="number" 
               name="' . $inputName . '" 
               value="' . htmlspecialchars($val) . '" 
               class="mark-input w-16 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1" 
               data-student-id="' . $studentId . '"
               data-subject="' . htmlspecialchars($subject['key']) . '"
               min="0" max="100">';
        echo '</td>';
    }
    
    // Total
    echo '<td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold text-gray-700" id="total-' . $studentId . '">';
    echo $result ? $result['total_obtained'] : '-';
    echo '</td>';
    
    // Grade
    $grade = $result ? $result['grade'] : '-';
    $badgeClass = $result ? ($result['grade'] == 'F' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800') : 'bg-gray-100 text-gray-800';
    
    echo '<td class="px-4 py-3 whitespace-nowrap text-center text-sm">';
    echo '<span id="grade-' . $studentId . '" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' . $badgeClass . '">';
    echo $grade;
    echo '</span>';
    echo '</td>';
    
    // Action
    echo '<td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">';
    if ($result) {
        echo '<button type="button" onclick="printResult(' . $studentId . ')" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-md transition-colors">';
        echo '<i class="fas fa-print mr-1"></i> Print';
        echo '</button>';
    }
    echo '</td>';
    
    echo '</tr>';
}
?>
