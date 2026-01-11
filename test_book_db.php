<?php
require_once 'includes/book_db.php';
require_once 'includes/db.php';

$bookDb = new BookDatabase();
$issued = $bookDb->getAllIssuedBooksDetails();

echo "Count Issued: " . count($issued) . "\n";
print_r($issued);

$db = new Database();
$allStudents = $db->readData();
echo "Total Students: " . count($allStudents) . "\n";

$student32 = $db->getStudent(32);
if ($student32) {
    echo "Student 32 found: " . $student32['student_name'] . "\n";
} else {
    echo "Student 32 NOT FOUND in DB.\n";
    // Check if ID 32 exists in raw list
    foreach ($allStudents as $s) {
        if ($s['id'] == 32) {
            echo "Wait, found ID 32 in loop! Type: " . gettype($s['id']) . "\n";
        }
    }
}
?>
