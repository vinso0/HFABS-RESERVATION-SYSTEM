<?php
require_once __DIR__ . '/../../backend/app/core/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
  header("Location: customer-login.html");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Reservations | Happy Face And Body Spa</title>

  <link rel="stylesheet" href="../public/css/landing.css" />
  <link rel="stylesheet" href="../public/css/customer-dashboard.css" />
  <link rel="stylesheet" href="../public/css/pagination.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="bubble-background">
    <div class="bubble bubble-1"></div>
    <div class="bubble bubble-2"></div>
    <div class="bubble bubble-3"></div>
    <div class="bubble bubble-4"></div>
    <div class="bubble bubble-5"></div>
    <div class="bubble bubble-6"></div>
    <div class="bubble bubble-7"></div>
    <div class="bubble bubble-8"></div>
  </div>

  <!-- Dynamic Navbar Container -->
  <div id="navbar-container"></div>

  <main class="dashboard-main">
    <section class="reservations-section">
      <div class="container">
        <h1 class="page-title">My Reservations</h1>
        <p class="page-subtitle">View your reservation history and upcoming appointments</p>

        <div class="filter-section">
          <div class="filter-dropdown">
            <i class="fas fa-filter"></i>
            <select id="filterStatus">
              <option value="all">All Reservations</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="rescheduled">Rescheduled</option>
            </select>
          </div>
        </div>

        <div class="reservations-container">
          <div id="reservationsList" class="reservations-list">
            <!-- Loading state -->
            <div class="loading-container">
              <div class="loading-spinner"></div>
              <p>Loading reservations...</p>
            </div>
          </div>

          <!-- Pagination -->
          <div id="paginationContainer" class="pagination-container">
            <!-- Pagination will be inserted here -->
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Reservation Details Modal -->
  <div id="reservationModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h2 class="modal-title">Reservation Details</h2>

      <div class="modal-body" id="modalBody">
        <!-- Modal content will be populated by JavaScript -->
      </div>
      
      <div class="modal-actions">
        <button class="btn-close" onclick="closeReservationModal()">
          <i class="fas fa-times"></i>
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- Reschedule Modal -->
  <div id="rescheduleModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h2 class="modal-title">Reschedule Reservation</h2>

      <div class="modal-body">
        <input type="hidden" id="rescheduleReservationId">
        
        <div class="form-group">
          <label for="newDate">New Date:</label>
          <div class="date-picker">
            <div class="calendar-header">
              <button class="calendar-nav" id="prevMonth">
                <i class="fas fa-chevron-left"></i>
              </button>
              <h3 id="currentMonth"></h3>
              <button class="calendar-nav" id="nextMonth">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
            <div class="calendar-grid">
              <div class="calendar-weekdays">
                <div class="weekday">Sun</div>
                <div class="weekday">Mon</div>
                <div class="weekday">Tue</div>
                <div class="weekday">Wed</div>
                <div class="weekday">Thu</div>
                <div class="weekday">Fri</div>
                <div class="weekday">Sat</div>
              </div>
              <div id="calendarDays" class="calendar-days"></div>
            </div>
            <div id="selectedDateDisplay" class="selected-date-display"></div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="newTime">New Time:</label>
          <div class="time-picker">
            <div id="timeSlots" class="time-slots"></div>
            <div id="selectedTimeDisplay" class="selected-time-display"></div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="rescheduleReason">Reason (optional):</label>
          <textarea id="rescheduleReason" rows="3" placeholder="Please enter reason for rescheduling"></textarea>
        </div>
        
        <div class="modal-actions">
          <button class="btn-primary" onclick="rescheduleReservation()">
            <i class="fas fa-check"></i>
            Confirm Reschedule
          </button>
          <button class="btn-cancel" onclick="closeRescheduleModal()">
            <i class="fas fa-times"></i>
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Review Modal -->
  <div id="reviewModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h2 class="modal-title">Leave a Review</h2>

      <div class="modal-body">
        <input type="hidden" id="reviewReservationId">
        <input type="hidden" id="reviewBranchId">
        
        <div class="form-group">
          <label for="reviewRating">Rating:</label>
          <div class="rating-stars">
              <input type="radio" id="star5" name="rating" value="5" checked>
              <label for="star5" title="Excellent">★</label>
              <input type="radio" id="star4" name="rating" value="4">
              <label for="star4" title="Very Good">★</label>
              <input type="radio" id="star3" name="rating" value="3">
              <label for="star3" title="Good">★</label>
              <input type="radio" id="star2" name="rating" value="2">
              <label for="star2" title="Fair">★</label>
              <input type="radio" id="star1" name="rating" value="1">
              <label for="star1" title="Poor">★</label>
          </div>
          <input type="hidden" id="reviewRating" value="5">
        </div>
        
        <div class="form-group">
          <label for="reviewComment">Comment:</label>
          <textarea id="reviewComment" rows="4" placeholder="Please share your experience..." required></textarea>
        </div>
        
        <div class="modal-actions">
          <button class="btn-primary" onclick="submitReview()">
            <i class="fas fa-check"></i>
            Submit Review
          </button>
          <button class="btn-cancel" onclick="closeReviewModal()">
            <i class="fas fa-times"></i>
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Review Modal -->
  <div id="viewReviewModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h2 class="modal-title">Reservation Review</h2>

      <div class="modal-body" id="viewReviewBody">
        <!-- Modal content will be populated by JavaScript -->
      </div>
      
      <div class="modal-actions">
        <button class="btn-close" onclick="closeViewReviewModal()">
          <i class="fas fa-times"></i>
          Close
        </button>
      </div>
    </div>
  </div>

  <script src="../public/js/navbar-loader.js"></script>
  <script src="../public/js/pagination.js"></script>
  <script src="../public/js/customer-dashboard.js"></script>
  <script>
    // Rating stars functionality
    document.querySelectorAll('.rating-stars input[type="radio"]').forEach(radio => {
      radio.addEventListener('change', function() {
        document.getElementById('reviewRating').value = this.value;
      });
    });
  </script>
</body>

</html>
