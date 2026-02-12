<?php
/**
 * One-Time CSV Column Fix Script
 * Adds the missing 'admission_class' column after 'admission_date'
 */

$csvFile = __DIR__ . '/data/database.csv';
$tempFile = __DIR__ . '/data/database_temp.csv';

if (!file_exists($csvFile)) {
    die("Error: database.csv not found!");
}

$inputHandle = fopen($csvFile, 'r');
$outputHandle = fopen($tempFile, 'w');

// Read and fix header
$oldHeaders = fgetcsv($inputHandle);
$newHeaders = [];

foreach ($oldHeaders as $index => $header) {
    $newHeaders[] = $header;
    // After 'admission_date' (column 6), insert 'admission_class'
    if ($index === 6 && $header === 'admission_date') {
        $newHeaders[] = 'admission_class';
    }
}

fputcsv($outputHandle, $newHeaders);

// Read and fix data rows
$rowsProcessed = 0;
while (($row = fgetcsv($inputHandle)) !== FALSE) {
    $newRow = [];
    foreach ($row as $index => $value) {
        $newRow[] = $value;
        // After admission_date value (column 6), insert empty admission_class value
        // We'll default it to the same as current_class (column 7 in old structure)
        if ($index === 6) {
            // The next value (index 7) is current_class in the old structure
            $currentClass = isset($row[7]) ? $row[7] : '';
            $newRow[] = $currentClass; // Use current_class as admission_class default
        }
    }
    fputcsv($outputHandle, $newRow);
    $rowsProcessed++;
}

fclose($inputHandle);
fclose($outputHandle);

// Replace old file with new file
if (rename($tempFile, $csvFile)) {
    echo "✅ Success! Fixed $rowsProcessed student records.<br>";
    echo "Added 'admission_class' column to database.csv<br>";
    echo "<br><a href='pages/students.php'>View Students Page</a><br>";
    echo "<br><strong>You can now refresh the students page and see correct data!</strong>";
} else {
    echo "❌ Error: Could not update database.csv";
}
?>
