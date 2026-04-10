<!-- Today's Reservation Details Modal -->
<div class="modal-overlay" id="todaysReservationDetailsModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Reservation Details</h3>
            <button class="modal-close" onclick="closeTodaysReservationDetailsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <!-- Customer Info -->
            <div class="modal-section">
                <h4 class="section-title">Customer Information</h4>
                <div class="detail-group">
                    <label>Name</label>
                    <p id="todaysModalCustomerName">-</p>
                </div>
                <div class="detail-group">
                    <label>Email</label>
                    <p id="todaysModalEmail">-</p>
                </div>
                <div class="detail-group">
                    <label>Contact Number</label>
                    <p id="todaysModalContact">-</p>
                </div>
            </div>

            <!-- Services -->
            <div class="modal-section">
                <h4 class="section-title">Services</h4>
                <div id="todaysModalServices" class="services-list"></div>
            </div>

            <!-- Reservation Details -->
            <div class="modal-section">
                <h4 class="section-title">Reservation Details</h4>
                <div class="detail-group">
                    <label>Reservation ID</label>
                    <p id="todaysModalReservationId">-</p>
                </div>
                <div class="detail-group">
                    <label>Schedule Date</label>
                    <p id="todaysModalScheduleDate">-</p>
                </div>
                <div class="detail-group">
                    <label>Reservation Date</label>
                    <p id="todaysModalReservationDate">-</p>
                </div>
                <div class="detail-group">
                    <label>Time</label>
                    <p id="todaysModalTime">-</p>
                </div>
                <div class="detail-group">
                    <label>Branch</label>
                    <p id="todaysModalBranch">-</p>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="modal-section">
                <h4 class="section-title">Payment Summary</h4>
                <div class="payment-summary-box">
                    <div class="payment-row">
                        <span class="payment-label">Total Price</span>
                        <span class="payment-value" id="todaysModalTotalPrice">-</span>
                    </div>
                    <div class="payment-row payment-row-balance">
                        <span class="payment-label">Remaining Balance</span>
                        <span class="payment-value payment-balance" id="todaysModalRemainingBalance">-</span>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="modal-section">
                <h4 class="section-title">Status</h4>
                <div class="detail-group">
                    <label>Current Status</label>
                    <p id="todaysModalStatus">-</p>
                </div>
            </div>

            <!-- Update Status Actions -->
            <div class="modal-section" id="todaysModalStatusActions">
                <h4 class="section-title">Update Status</h4>
                <div class="status-action-btns">
                    <button class="status-action-btn btn-mark-completed"
                            onclick="handleStatusChange(window._modalReservationId, 'completed')">
                        <i class="fas fa-check-circle"></i> Mark as Completed
                    </button>
                    <button class="status-action-btn btn-mark-noshow"
                            onclick="handleStatusChange(window._modalReservationId, 'no-show')">
                        <i class="fas fa-user-slash"></i> Mark as No-Show
                    </button>
                    <button class="status-action-btn btn-mark-cancelled"
                            onclick="handleStatusChange(window._modalReservationId, 'cancelled')">
                        <i class="fas fa-times-circle"></i> Cancel Reservation
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeTodaysReservationDetailsModal()">Close</button>
        </div>
    </div>
</div>