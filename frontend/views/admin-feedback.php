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
            <h2>Feedback & Reviews</h2>
            <p class="subtitle-text">View and manage customer feedback for your branch</p>

            <!-- Rating Overview -->
            <div class="rating-overview">
                <div class="overall-rating">
                    <div class="rating-score" id="overallRating">4.8</div>
                    <div class="rating-stars" id="overallStars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="total-reviews" id="totalReviews">128 reviews</div>
                </div>
                
                <div class="rating-breakdown" id="ratingBreakdown">
                    <!-- Rating breakdown will be populated by JavaScript -->
                </div>
            </div>

            <div class="filter-section">
                <div class="filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search reviews by customer name or service...">
                </div>
                
                <div class="filter-rating">
                    <i class="fas fa-star"></i>
                    <select id="filterRating">
                        <option value="all">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars & Up</option>
                        <option value="3">3 Stars & Up</option>
                        <option value="2">2 Stars & Up</option>
                        <option value="1">1 Star & Up</option>
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
