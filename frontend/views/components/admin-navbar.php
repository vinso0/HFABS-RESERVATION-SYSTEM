<header class="page-header">
    <div class="page-flex">
        <div class="user-info">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php
                    if (isset($_SESSION['user_name'])) {
                        $username = trim($_SESSION['user_name']);
                        $nameParts = explode(' ', $username);
                        
                        if (count($nameParts) >= 2) {
                            // First letter of first name and first letter of last name
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
                        } else {
                            // First two letters of single name
                            $initials = strtoupper(substr($username, 0, 2));
                        }
                        
                        echo htmlspecialchars($initials);
                    } else {
                        echo 'AU';
                    }
                    ?>
                </div>
                <div>
                    <p class="user-name"><?php echo isset($_SESSION['user_name']) ? strtoupper(htmlspecialchars($_SESSION['user_name'])) : 'ADMIN USER'; ?></p>
                    <p class="user-role"><?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator'; ?></p>
                </div>
            </div>
        </div>
    </div>
</header>
