<?php

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: superadmin-login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Superadmin Dashboard | HappyFace</title>
</head>
<body>
    <h1>Welcome Superadmin, <?php echo $_SESSION['user_name']; ?></h1>
    <p>ay wow napromote</p>
    
    <nav>
        <ul>
            <li><a href="manage-users.php">Manage Users</a></li>
            <li><a href="../../backend/public/index.php?url=auth/logout">Logout</a></li>
        </ul>
    </nav>
</body>
</html>