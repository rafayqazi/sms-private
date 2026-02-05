<?php
// Migration script to update books_issued.csv schema
$issuedFile = __DIR__ . '/../data/books_issued.csv';

if (!file_exists($issuedFile)) {
    die("books_issued.csv not found!\n");
}

// Read all existing records
$records = [];
if (($handle = fopen($issuedFile, "r")) !== FALSE) {
    $oldHeader = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) == count($oldHeader)) {
            $records[] = array_combine($oldHeader, $data);
        }
    }
    fclose($handle);
}

// Write with new schema
$handle = fopen($issuedFile, 'w');
$newHeader = ['id', 'recipient_type', 'recipient_id', 'book_id', 'issue_date', 'return_date', 'status', 'remarks'];
fputcsv($handle, $newHeader);

foreach ($records as $record) {
    // Migrate: student_id -> recipient_id, add recipient_type = 'student'
    $newRecord = [
        $record['id'],
        'student',  // recipient_type
        $record['student_id'],  // recipient_id (was student_id)
        $record['book_id'],
        $record['issue_date'],
        $record['return_date'],
        $record['status'],
        $record['remarks']
    ];
    fputcsv($handle, $newRecord);
}

fclose($handle);

echo "Migration completed successfully!\n";
echo "Migrated " . count($records) . " records.\n";
?>
