<?php
// Test token verification manually
require_once 'backend/app/config/config.php';
require_once 'backend/app/core/Database.php';
require_once 'backend/app/models/User.php';

$db = new Database();
$conn = $db->getConnection();

// Get the most recent token from database
$result = $conn->query("SELECT * FROM password_resets ORDER BY created_at DESC LIMIT 5");

echo "<h1>Recent Password Reset Tokens</h1>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Token (first 8 chars)</th><th>Expires At</th><th>Used</th><th>Created At</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = 'Valid';
        if ($row['used']) {
            $status = 'Used';
        } elseif (strtotime($row['expires_at']) < time()) {
            $status = 'Expired';
        }
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . substr($row['token'], 0, 8) . "...</td>";
        echo "<td>" . $row['expires_at'] . "</td>";
        echo "<td>" . ($row['used'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td><strong>" . $status . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Test Token Verification</h2>";
    echo "<p>Copy a valid token and test it below:</p>";
    echo "<form method='post'>";
    echo "<input type='text' name='token' placeholder='Enter token here' size='64' required><br><br>";
    echo "<button type='submit'>Test Token</button>";
    echo "</form>";
    
    if ($_POST['token']) {
        $userModel = new User($conn);
        $tokenData = $userModel->getValidResetToken($_POST['token']);
        
        if ($tokenData) {
            echo "<h3 style='color: green;'>✅ Token is valid!</h3>";
            echo "<p>User: " . htmlspecialchars($tokenData['username']) . "</p>";
            echo "<p>Email: " . htmlspecialchars($tokenData['email']) . "</p>";
        } else {
            echo "<h3 style='color: red;'>❌ Token is invalid or expired!</h3>";
        }
    }
    
} else {
    echo "<p>No tokens found in database.</p>";
}

$conn->close();
?>
