<?php
// api/check_updates_auto.php
// Automatically check for updates from GitHub repository

header('Content-Type: application/json');

// Repository details
$owner = 'rafayqazi';
$repo = 'SMS-GBPS-ALI-BUX-JARWAR';
$branch = 'main';

try {
    // Fetch latest commit from GitHub API
    $apiUrl = "https://api.github.com/repos/$owner/$repo/commits/$branch";
    
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: PHP',
                'Accept: application/vnd.github.v3+json'
            ],
            'timeout' => 5 // 5 second timeout
        ]
    ];
    
    $context = stream_context_create($opts);
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to connect to GitHub',
            'updates_available' => false
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['sha'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from GitHub',
            'updates_available' => false
        ]);
        exit;
    }
    
    $remoteCommit = $data['sha'];
    $commitMessage = isset($data['commit']['message']) ? $data['commit']['message'] : 'No message';
    $commitDate = isset($data['commit']['committer']['date']) ? $data['commit']['committer']['date'] : '';
    
    // Get local commit hash
    $localCommit = null;
    if (file_exists('../.git/refs/heads/' . $branch)) {
        $localCommit = trim(file_get_contents('../.git/refs/heads/' . $branch));
    }
    
    // Compare commits
    $updatesAvailable = ($localCommit !== $remoteCommit);
    
    // Store in session
    session_start();
    $_SESSION['updates_available'] = $updatesAvailable;
    $_SESSION['update_check_done'] = true;
    $_SESSION['remote_commit'] = $remoteCommit;
    $_SESSION['local_commit'] = $localCommit;
    
    echo json_encode([
        'success' => true,
        'updates_available' => $updatesAvailable,
        'remote_commit' => substr($remoteCommit, 0, 7),
        'local_commit' => $localCommit ? substr($localCommit, 0, 7) : 'unknown',
        'commit_message' => $commitMessage,
        'commit_date' => $commitDate
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'updates_available' => false
    ]);
}
