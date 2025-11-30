<?php
require_once 'includes/db.php';

echo "Adding mock profile pictures to students...\n\n";

$db = new Database();
$students = $db->readData();

// Create uploads directory if it doesn't exist
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Array of avatar colors for variety
$colors = ['4F46E5', '10B981', 'F59E0B', 'EF4444', '8B5CF6', '06B6D4', 'EC4899', '14B8A6'];

$updated = 0;

foreach ($students as $student) {
    // Skip if already has a profile image
    if (!empty($student['profile_image']) && file_exists($student['profile_image'])) {
        continue;
    }
    
    // Get first letter of student name
    $initial = strtoupper(substr($student['student_name'], 0, 1));
    
    // Choose color based on gender
    $bgColor = $student['gender'] == 'Male' ? '3B82F6' : 'EC4899'; // Blue for boys, pink for girls
    
    // Generate avatar URL from UI Avatars API
    // Format: https://ui-avatars.com/api/?name=John+Doe&background=random&color=fff&size=200
    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($student['student_name']) . 
                 "&background={$bgColor}&color=fff&size=400&bold=true&format=png";
    
    // Download the avatar
    $imageContent = @file_get_contents($avatarUrl);
    
    if ($imageContent !== false) {
        $filename = $uploadDir . 'GR-' . $student['gr_no'] . '-profile_image.png';
        file_put_contents($filename, $imageContent);
        
        // Update student record
        $db->updateStudent($student['id'], ['profile_image' => $filename]);
        
        echo "  ✓ Added profile picture for: {$student['student_name']} (GR: {$student['gr_no']})\n";
        $updated++;
        
        // Small delay to avoid rate limiting
        usleep(100000); // 100ms delay
    } else {
        echo "  ✗ Failed to download avatar for: {$student['student_name']}\n";
    }
}

echo "\n✓ Profile pictures added successfully!\n";
echo "  Total students updated: $updated\n";
?>
