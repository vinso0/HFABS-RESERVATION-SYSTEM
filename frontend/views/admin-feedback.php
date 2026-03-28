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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-feedback.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="feedback-section">
            <h2>Feedback &amp; Reviews</h2>
            <p class="subtitle-text">Moderate and manage customer feedback for your branch</p>

            <!-- Rating Overview -->
            <div class="rating-overview">
                <div class="overall-rating">
                    <div class="rating-score" id="overallRating">—</div>
                    <div class="rating-stars" id="overallStars"></div>
                    <div class="total-reviews" id="totalReviews">0 reviews</div>
                </div>
                <div class="rating-breakdown" id="ratingBreakdown"></div>
            </div>

            <!-- Moderation Status Tabs -->
            <div class="status-tabs">
                <button class="status-tab active" data-status="all">
                    All
                </button>
                <button class="status-tab" data-status="active">
                    <i class="fas fa-check-circle"></i> Active
                </button>
                <button class="status-tab tab-blocked" data-status="blocked">
                    <i class="fas fa-ban"></i> Blocked
                    <span class="tab-badge tab-badge--dark" id="blockedCount">0</span>
                </button>
                <button class="status-tab tab-reported" data-status="reported">
                    <i class="fas fa-flag"></i> Reported
                    <span class="tab-badge tab-badge--red" id="reportedCount">0</span>
                </button>
            </div>

            <!-- Search & Filter -->
            <div class="filter-section">
                <div class="filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by customer name, service, or comment...">
                </div>
                <div class="filter-rating">
                    <i class="fas fa-star"></i>
                    <select id="filterRating">
                        <option value="all">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars &amp; Up</option>
                        <option value="3">3 Stars &amp; Up</option>
                        <option value="2">2 Stars &amp; Up</option>
                        <option value="1">1 Star &amp; Up</option>
                    </select>
                </div>
            </div>

            <div class="feedback-list" id="feedbackList">
                <div class="loading-row">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading feedback...</span>
                    </div>
                </div>
            </div>

            <?php include 'components/pagination.php'; ?>
        </section>
    </main>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/admin-feedback.js"></script>
</body>
</html>
