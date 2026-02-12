<?php
/**
 * Standalone Migration Script - No Auth Required
 * Run this ONCE by opening it in your browser
 */

// Simple password protection
$migration_password = 'admin123';
if (!isset($_GET['password']) || $_GET['password'] !== $migration_password) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Migration Password</title>
        <style>
            body { font-family: Arial; background: #f5f5f5; padding: 50px; text-align: center; }
            .box { background: white; padding: 30px; border-radius: 10px; max-width: 400px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            input { padding: 10px; width: 200px; border: 1px solid #ddd; border-radius: 5px; }
            button { padding: 10px 20px; background: #4F46E5; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>🔒 Migration Password</h2>
            <p>Enter password to run migration:</p>
            <form method="get">
                <input type="password" name="password" placeholder="Enter: admin123" autofocus>
                <br>
                <button type="submit">Run Migration</button>
            </form>
        </div>
    </body>
    </html>
    ');
}

// Read database
$csvFile = __DIR__ . '/../data/database.csv';
$students = [];

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle);
    while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $students[] = array_combine($headers, $row);
    }
    fclose($handle);
}

// Migration logic
$movedToFive = 0;
$movedToAlumni = 0;
$fixedUnknown = 0;

foreach ($students as &$student) {
    $currentClass = trim($student['current_class'] ?? '');
    $admissionClass = trim($student['admission_class'] ?? '');
    
    // Fix current_class
    if ($currentClass === '5') {
        $student['current_class'] = 'Five';
        $movedToFive++;
    } elseif (in_array($currentClass, ['6', '7', '8', '9', '10', '11', '12'])) {
        $student['student_status'] = 'Alumni';
        $student['last_class'] = 'Five';
        $student['current_class'] = '';
        $movedToAlumni++;
    } elseif ($currentClass === 'Unknown' || $currentClass === '') {
        if (($student['student_status'] ?? 'Active') === 'Active') {
            $student['current_class'] = 'Nursery';
            $fixedUnknown++;
        }
    }
    
    // Fix admission_class
    if ($admissionClass === '5') {
        $student['admission_class'] = 'Five';
    } elseif (in_array($admissionClass, ['6', '7', '8', '9', '10', '11', '12'])) {
        $student['admission_class'] = 'Five';
    } elseif ($admissionClass === 'Unknown' || $admissionClass === '') {
        $student['admission_class'] = 'Nursery';
    }
}

// Write back to CSV
$fp = fopen($csvFile, 'w');
fputcsv($fp, $headers);
foreach ($students as $student) {
    $row = [];
    foreach ($headers as $header) {
        $row[] = $student[$header] ?? '';
    }
    fputcsv($fp, $row);
}
fclose($fp);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Migration Complete</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 50px; }
        .container { background: white; padding: 40px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #10b981; font-size: 48px; text-align: center; margin-bottom: 20px; }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .stats { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .stat-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #d1fae5; }
        .stat-row:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #065f46; }
        .value { font-weight: bold; color: #10b981; }
        .warning { background: #fef3c7; border: 1px solid #fcd34d; padding: 15px; border-radius: 8px; margin: 20px 0; color: #92400e; }
        .buttons { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; padding: 12px 24px; margin: 0 10px; background: #4F46E5; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn:hover { background: #4338CA; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅</div>
        <h1>Migration Complete!</h1>
        
        <div class="stats">
            <div class="stat-row">
                <span class="label">Total Students Processed:</span>
                <span class="value"><?php echo count($students); ?></span>
            </div>
            <div class="stat-row">
                <span class="label">Moved to "Five":</span>
                <span class="value"><?php echo $movedToFive; ?></span>
            </div>
            <div class="stat-row">
                <span class="label">Moved to Alumni:</span>
                <span class="value"><?php echo $movedToAlumni; ?></span>
            </div>
            <div class="stat-row">
                <span class="label">Fixed Unknown:</span>
                <span class="value"><?php echo $fixedUnknown; ?></span>
            </div>
        </div>
        
        <div class="warning">
            <strong>⚠️ Important:</strong> This script has completed. You can now delete the file:
            <br><code>fix_classes_now.php</code>
        </div>
        
        <div class="buttons">
            <a href="pages/students.php" class="btn">View Students</a>
            <a href="pages/alumni.php" class="btn">View Alumni</a>
        </div>
    </div>
</body>
</html>
