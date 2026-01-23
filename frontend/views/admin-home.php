<?php

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard | HappyFace</title>
</head>
<body>
    <h1>Welcome Admin, <?php echo $_SESSION['user_name']; ?></h1>
    <p>admin na to boi</p>
    
    <nav>
        <ul>
            <li><a href="manage-users.php">Manage Users</a></li>
            <li><a href="../../backend/public/index.php?url=auth/logout">Logout</a></li>
        </ul>
    </nav>
</body>
</html>