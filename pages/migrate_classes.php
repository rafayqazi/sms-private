<?php
/**
 * One-Time Data Migration Script - REVISED
 * Purpose: Fix class assignment bug by converting numeric class values to Alumni status
 * 
 * Strategy:
 * - Students with "5" → "Five" (current class)
 * - Students with "6" through "12" → Alumni (they've graduated beyond Class Five)
 * - "Unknown" → "Nursery" (default fallback)
 */

require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die('Access Denied: Admin only');
}

$db = new Database();
$students = $db->readData();

$movedToFive = 0;
$movedToAlumni = 0;
$fixedUnknown = 0;
$errors = [];

foreach ($students as &$student) {
    $currentClass = trim($student['current_class'] ?? '');
    $admissionClass = trim($student['admission_class'] ?? '');
    
    // Fix current_class
    if ($currentClass === '5') {
        $student['current_class'] = 'Five';
        $movedToFive++;
    } elseif (in_array($currentClass, ['6', '7', '8', '9', '10', '11', '12'])) {
        // Student has graduated - mark as Alumni
        $student['student_status'] = 'Alumni';
        $student['last_class'] = 'Five'; // They graduated from Class Five
        $student['current_class'] = ''; // No current class for alumni
        $movedToAlumni++;
    } elseif ($currentClass === 'Unknown' || $currentClass === '') {
        $student['current_class'] = 'Nursery';
        $fixedUnknown++;
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

// Write updated data back
if ($db->writeData($students)) {
    $message = "✅ Migration Complete!";
    $success = true;
} else {
    $message = "❌ Migration Failed: Could not write to database.";
    $success = false;
}

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8 border-t-4 <?php echo $success ? 'border-green-500' : 'border-red-500'; ?>">
            <div class="text-center mb-6">
                <div class="text-6xl mb-4"><?php echo $success ? '✅' : '❌'; ?></div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Class Data Migration</h1>
                <p class="text-gray-600"><?php echo $message; ?></p>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <h3 class="font-bold text-green-800 mb-3">Migration Summary:</h3>
                    <div class="space-y-2 text-sm text-green-700">
                        <div class="flex justify-between">
                            <span>✓ Total students processed:</span>
                            <span class="font-bold"><?php echo count($students); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>✓ Moved to "Five":</span>
                            <span class="font-bold"><?php echo $movedToFive; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>✓ Moved to Alumni:</span>
                            <span class="font-bold"><?php echo $movedToAlumni; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>✓ Fixed Unknown:</span>
                            <span class="font-bold"><?php echo $fixedUnknown; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-bold text-blue-800 mb-2">ℹ️ What Changed:</h3>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Students in numeric "5" → moved to "Five"</li>
                        <li>Students in "6" through "12" → marked as Alumni</li>
                        <li>"Unknown" classes → changed to "Nursery"</li>
                    </ul>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <h3 class="font-bold text-yellow-800 mb-2">⚠️ Important:</h3>
                    <p class="text-sm text-yellow-700">This is a one-time migration script. You can safely delete this file now:</p>
                    <code class="block mt-2 bg-yellow-100 p-2 rounded text-xs">pages/migrate_classes.php</code>
                </div>
            <?php endif; ?>
            
            <div class="flex gap-3 justify-center">
                <a href="students.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md">
                    <i class="fas fa-users mr-2"></i>View Students
                </a>
                <a href="alumni.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md">
                    <i class="fas fa-user-graduate mr-2"></i>View Alumni
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
