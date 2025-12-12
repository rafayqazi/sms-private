<?php
session_start();
// Simulate admin session for testing
$_SESSION['user'] = 'admin';
$_SESSION['user_type'] = 'admin';
$_SESSION['user_role'] = 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Assign Role API</title>
</head>
<body>
    <h1>Debug Assign Role API</h1>
    <button onclick="testAPI()">Test API Call</button>
    <div id="result"></div>
    
    <script>
    async function testAPI() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = 'Testing...';
        
        try {
            const response = await fetch('api/assign_role.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    teacherId: '1',
                    role: 'Editor',
                    username: 'testuser123',
                    password: 'testpass123',
                    classes: ['Kachi'],
                    isEdit: false
                })
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            resultDiv.innerHTML = `
                <h3>Status: ${response.status}</h3>
                <h3>Raw Response:</h3>
                <pre>${text.substring(0, 500)}</pre>
                <h3>First 50 bytes (hex):</h3>
                <pre>${Array.from(text.substring(0, 50)).map(c => c.charCodeAt(0).toString(16).padStart(2, '0')).join(' ')}</pre>
            `;
            
            try {
                const json = JSON.parse(text);
                resultDiv.innerHTML += `<h3>Parsed JSON:</h3><pre>${JSON.stringify(json, null, 2)}</pre>`;
            } catch (e) {
                resultDiv.innerHTML += `<h3>JSON Parse Error:</h3><pre>${e.message}</pre>`;
            }
            
        } catch (error) {
            resultDiv.innerHTML = `<h3>Error:</h3><pre>${error.message}</pre>`;
            console.error(error);
        }
    }
    </script>
</body>
</html>
