<header class="page-header">
    <div class="page-header-left">
    </div>
    <div class="user-info">
        <div class="user-profile">
            <div class="user-avatar">
                <?php
                if (isset($_SESSION['username'])) {
                    $username = trim($_SESSION['username']);
                    $nameParts = explode(' ', $username);
                    if (count($nameParts) >= 2) {
                        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
                    } else {
                        $initials = strtoupper(substr($username, 0, 2));
                    }
                    echo htmlspecialchars($initials);
                } else {
                    echo 'SA';
                }
                ?>
            </div>
            <div>
                <p class="user-name"><?php echo isset($_SESSION['username']) ? strtoupper(htmlspecialchars($_SESSION['username'])) : 'SUPERADMIN'; ?></p>
                <p class="user-role">Super Administrator</p>
            </div>
        </div>
    </div>
</header>

<div id="toast-container" class="toast-container"></div>
