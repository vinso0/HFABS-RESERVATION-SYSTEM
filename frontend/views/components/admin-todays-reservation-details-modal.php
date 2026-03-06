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

            <div class="modal-section">
                <h4 class="section-title">Services</h4>
                <div id="todaysModalServices" class="services-list">
                    <!-- Services will be dynamically populated -->
                </div>
            </div>

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
                <div class="detail-group">
                    <label>Total Price</label>
                    <p id="todaysModalTotalPrice" class="price-text">-</p>
                </div>
            </div>

            <div class="modal-section">
                <h4 class="section-title">Status</h4>
                <div class="detail-group">
                    <label>Reservation Status</label>
                    <p id="todaysModalStatus">-</p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeTodaysReservationDetailsModal()">Close</button>
        </div>
    </div>
</div>
