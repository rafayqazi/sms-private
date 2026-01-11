<?php
// Fix for teachers.csv header mismatch
$file = '../data/teachers.csv';

if (!file_exists($file)) {
    die("File not found: $file");
}

$lines = file($file);
if (empty($lines)) {
    die("File is empty");
}

// The correct header from db.php addTeacher method
$correctHeader = "id,name,father_name,gender,cnic,dob,age,email,disability,payment_type,payment_no,iban,contact,retirement_date,designation,department,posting,basic_scale,address,district,tahsil,profile_image,joining_date,created_at";

// Replace the first line (header)
$lines[0] = $correctHeader . "\n";

// Write back
if (file_put_contents($file, implode('', $lines))) {
    echo "<h1>Success!</h1>";
    echo "<p>Updated teachers.csv header.</p>";
    echo "<p>Old header count: " . count(str_getcsv(trim(file($file)[0]))) . " (Wait, that's the new one now)</p>";
    echo "<p>New header: $correctHeader</p>";
} else {
    echo "Failed to write to file.";
}
?>
