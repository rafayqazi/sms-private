<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $db = new Database();
    $id = $_GET['id'];
    
    // Optional: Get teacher details first to delete image if needed
    $teacher = $db->getTeacher($id);
    
    if ($db->deleteTeacher($id)) {
        // Delete profile image if exists
        if ($teacher && !empty($teacher['profile_image']) && file_exists('../' . $teacher['profile_image'])) {
            unlink('../' . $teacher['profile_image']);
        }
        
        header("Location: ../pages/teacher_profile.php?msg=deleted");
    } else {
        header("Location: ../pages/teacher_profile.php?error=delete_failed");
    }
} else {
    header("Location: ../pages/teacher_profile.php");
}
exit;
?>
