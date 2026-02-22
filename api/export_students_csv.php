<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('students.php')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

$db = new Database();
$classFilter = isset($_GET['class']) ? $_GET['class'] : '';

$filters = [
    'class' => $classFilter,
    'include_alumni' => true // Include everyone for bulk edit
];

$students = $db->filterStudents($filters);

header('Content-Type: text/csv');
$filename = "student_export_" . ($classFilter ? str_replace(' ', '_', $classFilter) : 'all') . "_" . date('Y-m-d') . ".csv";
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Headers matching bulk_admission.php mapping expectations
$headers = [
    'GR Number', 
    'Student Name', 
    'Father Name', 
    'Gender', 
    'Date of Birth', 
    'Admission Date', 
    'CLASS INTO WHICH ADMITTED', 
    'Current Class', 
    'B-Form No', 
    'Father\'s CNIC', 
    'Father\'s Contact', 
    'District', 
    'Taluka', 
    'Caste', 
    'Religion', 
    'Place of Birth', 
    'Previous School Name', 
    'School Name', 
    'Status (Active/Alumni)', 
    'Is Repeater? (Yes/No)'
];

fputcsv($output, $headers);

foreach ($students as $student) {
    $row = [
        $student['gr_no'],
        $student['student_name'],
        $student['father_name'],
        $student['gender'],
        $student['date_of_birth'],
        $student['admission_date'],
        $student['admission_class'] ?? '',
        $student['current_class'],
        $student['b_form_no'],
        $student['father_cnic'],
        $student['father_contact'],
        $student['district'],
        $student['taluka'],
        $student['caste'] ?? '',
        $student['religion'] ?? '',
        $student['place_of_birth'] ?? '',
        $student['previous_school'] ?? '',
        $student['school_name'] ?? '',
        $student['student_status'] ?? 'Active',
        ($student['is_repeater'] == '1' ? 'Yes' : 'No')
    ];
    fputcsv($output, $row);
}

fclose($output);
exit;
