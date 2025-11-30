<?php
require_once 'includes/db.php';
$db = new Database();
$student = $db->getStudent(1);
echo "Student found: " . ($student ? "YES" : "NO") . "\n";
if ($student) {
    echo "Name: " . $student['student_name'] . "\n";
    echo "GR No: " . $student['gr_no'] . "\n";
    echo "Profile Image: " . ($student['profile_image'] ?? 'EMPTY') . "\n";
}
?>
