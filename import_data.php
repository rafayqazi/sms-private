<?php
require_once 'includes/db.php';

$inputFile = __DIR__ . '/data/Computrized Data of GBPS ALI BUX JARWAR csv.csv';
$outputFile = __DIR__ . '/data/database.csv';

if (!file_exists($inputFile)) {
    die("Input file not found.");
}

$database = new Database();
// We will manually handle the write to ensure we match the schema exactly and handle the specific format of the input CSV.

// Schema Headers from db.php
$headers = [
    'id', 'gr_no', 'student_name', 'father_name', 'gender', 'date_of_birth', 
    'admission_date', 'current_class', 'age', 'b_form_no', 'father_cnic', 
    'father_contact', 'district', 'taluka', 'school_name', 'semis_code', 
    'is_active', 'created_at', 'updated_at', 'father_cnic_front', 
    'father_cnic_back', 'b_form_img', 'profile_image', 'previous_school', 'slc_img',
    'student_status', 'is_repeater'
];

$handle = fopen($inputFile, "r");
if ($handle === FALSE) {
    die("Could not open input file.");
}

$students = [];
$idCounter = 1;
$currentTimestamp = date('Y-m-d H:i:s');

// Skip Line 1 (Title)
fgetcsv($handle);

// Read Line 2 (Headers)
$csvHeaders = fgetcsv($handle);
// Map headers to indices
$headerMap = [];
foreach ($csvHeaders as $index => $header) {
    $header = trim($header);
    if ($header) {
        $headerMap[$header] = $index;
    }
}

// Helper to get value by header name
function getValue($row, $headerMap, $name) {
    if (isset($headerMap[$name]) && isset($row[$headerMap[$name]])) {
        return trim($row[$headerMap[$name]]);
    }
    return '';
}

// Helper to format date
function formatDate($dateStr) {
    if (empty($dateStr)) return '';
    // Try to parse date like "2-Sep-14"
    $timestamp = strtotime($dateStr);
    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }
    return $dateStr;
}

while (($row = fgetcsv($handle)) !== FALSE) {
    // Check if it's a section header or empty line
    if (empty($row[0]) && empty($row[5])) continue; // Skip empty lines
    if (strpos($row[0], 'Class') === 0) continue; // Skip "Class ..." headers

    // Extract Data
    $grNo = getValue($row, $headerMap, 'GR No');
    if (empty($grNo)) {
        // Some rows might be valid students but missing GR No in the input, 
        // or they might be empty rows. 
        // Looking at the file, Kachi students have "-" or empty GR No.
        // We should still import them, maybe generate a temp GR or leave it empty if allowed.
        // Let's check if Name exists.
        $name = getValue($row, $headerMap, 'Student Name');
        if (empty($name)) continue;
    }

    $student = [
        'id' => $idCounter++,
        'gr_no' => $grNo,
        'student_name' => getValue($row, $headerMap, 'Student Name'),
        'father_name' => getValue($row, $headerMap, 'Father Name'),
        'gender' => getValue($row, $headerMap, 'Gender'),
        'date_of_birth' => formatDate(getValue($row, $headerMap, 'Date Of Birth')),
        'admission_date' => formatDate(getValue($row, $headerMap, 'Addmision Date')),
        'current_class' => getValue($row, $headerMap, 'Class'),
        'age' => getValue($row, $headerMap, 'Age'),
        'b_form_no' => getValue($row, $headerMap, 'B-Form #'),
        'father_cnic' => getValue($row, $headerMap, 'Father CNIC'),
        'father_contact' => getValue($row, $headerMap, "Father's Contact"),
        'district' => getValue($row, $headerMap, 'District'),
        'taluka' => getValue($row, $headerMap, 'Taluka'),
        'school_name' => getValue($row, $headerMap, 'School Name'),
        'semis_code' => getValue($row, $headerMap, 'Semis ID'),
        'is_active' => 1,
        'created_at' => $currentTimestamp,
        'updated_at' => $currentTimestamp,
        'father_cnic_front' => '',
        'father_cnic_back' => '',
        'b_form_img' => '',
        'profile_image' => '',
        'previous_school' => '',
        'slc_img' => '',
        'student_status' => 'Active',
        'is_repeater' => '0'
    ];

    // Normalize Class Names if needed
    // Input uses "Five", "Four", "Three", "Two", "One", "Kachi" which matches our system.
    
    $students[] = $student;
}
fclose($handle);

// Write to database.csv
$fp = fopen($outputFile, 'w');
fputcsv($fp, $headers);
foreach ($students as $student) {
    // Ensure order matches headers
    $row = [];
    foreach ($headers as $header) {
        $row[] = $student[$header];
    }
    fputcsv($fp, $row);
}
fclose($fp);

echo "Imported " . count($students) . " students successfully.";
?>
