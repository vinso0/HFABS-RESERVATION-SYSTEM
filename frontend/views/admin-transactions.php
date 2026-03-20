<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header("Location: admin-login.html");
    exit;
}

$branchName = $_SESSION['branch_name'] ?? 'Your Branch';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-transactions.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="transactions-section">
            <div class="section-header">
                <div>
                    <h2>Transactions</h2>
                    <p class="subtitle-text">Payment records for <?php echo $branchName; ?></p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card total-card">
                    <div class="card-icon"><i class="fas fa-receipt"></i></div>
                    <div class="card-info">
                        <span class="card-label">Total Payments</span>
                        <span class="card-value" id="totalCount">0</span>
                    </div>
                </div>
                <div class="summary-card paid-card">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-info">
                        <span class="card-label">Total Collected</span>
                        <span class="card-value" id="totalCollected">₱0.00</span>
                    </div>
                </div>
                <div class="summary-card unpaid-card">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-info">
                        <span class="card-label">Unpaid</span>
                        <span class="card-value" id="unpaidCount">0</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by customer, reservation ID...">
                </div>

                <div class="filter-controls">
                    <div class="filter-dropdown">
                        <i class="fas fa-filter"></i>
                        <select id="filterStatus">
                            <option value="all">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>

                    <div class="filter-dropdown">
                        <i class="fas fa-credit-card"></i>
                        <select id="filterMethod">
                            <option value="all">All Methods</option>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                        </select>
                    </div>

                    <div class="date-filter">
                        <label for="startDate">From:</label>
                        <input type="date" id="startDate">
                        <label for="endDate">To:</label>
                        <input type="date" id="endDate">
                        <button id="applyDateFilter" class="btn-filter">Apply</button>
                        <button id="clearFilters" class="btn-clear">Clear</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Reservation ID</th>
                            <th>Customer Name</th>
                            <th>Amount Paid</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>PayMongo Ref</th>
                            <th>Date Paid</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                        <tr class="loading-row">
                            <td colspan="8">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading transactions...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php include 'components/pagination.php'; ?>
            </div>
        </section>
    </main>

    <!-- Payment Detail Modal -->
    <div id="transactionModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Payment Details</h3>
                <button class="modal-close" id="closeTransactionModal">&times;</button>
            </div>
            <div class="modal-body" id="transactionModalBody">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/transactions-data.js"></script>
</body>
</html>
