<?php
require_once 'backend/app/config/config.php';
require_once 'backend/app/core/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h1>Password Resets Table Structure</h1>";

$result = $conn->query("DESCRIBE password_resets");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Could not get table structure.</p>";
}

// Also check a specific token
echo "<h2>Check Specific Token</h2>";
$token = "103ada75d8b9c4f1e5a2c0d6f9c3b9e6e3a9e2f9a7f5b2c5d5e2f4";
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<h3>Token Found:</h3>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<h3>Token NOT Found!</h3>";
}

$conn->close();
?>
