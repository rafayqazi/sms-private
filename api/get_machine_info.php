<?php
require_once '../includes/license.php';

header('Content-Type: application/json');

echo json_encode([
    'mac' => License::getMacAddress()
]);
