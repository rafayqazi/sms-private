<?php
// Test script to see what assign_role.php returns
$url = 'http://localhost/school%20Management%20system%20-%20ali%20bux%20jarwar/api/assign_role.php';

$data = array(
    'teacherId' => '1',
    'role' => 'Editor',
    'username' => 'testuser',
    'password' => 'testpass',
    'classes' => array('Kachi'),
    'isEdit' => false
);

$options = array(
    'http' => array(
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response:\n";
echo $result;
echo "\n\nFirst 200 characters:\n";
echo substr($result, 0, 200);
