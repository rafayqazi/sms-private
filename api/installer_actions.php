<?php
require_once '../includes/db.php';
require_once '../includes/license.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();

    switch ($action) {
        case 'verify_superuser':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($username === 'abdul rafay' && $password === 'khuljasimsim') {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid Super User credentials.']);
            }
            break;

        case 'activate_license':
            $mac = $_POST['mac_address'] ?? '';
            if ($mac && License::activate($mac)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to activate license.']);
            }
            break;

        case 'save_settings':
            $data = [
                'school_name' => $_POST['school_name'] ?? '',
                'address_tagline' => $_POST['address_tagline'] ?? '',
                'semis_code' => $_POST['semis_code'] ?? '',
                'headmaster_name' => $_POST['headmaster_name'] ?? ''
            ];
            
            if ($db->updateSchoolSettings($data)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save school settings.']);
            }
            break;

        case 'create_admin':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $result = $db->createUserRole(0, 'Admin', $username, $password);
            if ($result['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'POST request expected.']);
}
