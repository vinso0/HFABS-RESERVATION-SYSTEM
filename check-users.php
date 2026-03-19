<?php
// Simple script to check users in database
require_once 'backend/app/config/config.php';
require_once 'backend/app/core/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h1>Users in Database</h1>";

try {
    $result = $conn->query("SELECT user_id, username, email, role FROM users ORDER BY user_id LIMIT 10");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

$conn->close();

echo "<h2>Test Email to Existing User</h2>";
echo "<p>If you see users above, use one of their email addresses to test the password reset.</p>";

?>
