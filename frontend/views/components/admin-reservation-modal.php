<!-- Reservation Details Modal -->
<div class="modal-overlay" id="reservationModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Reservation Details</h3>
            <button class="modal-close" onclick="closeReservationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="modal-section">
                <h4 class="section-title">Service Details</h4>
                <div class="detail-group">
                    <label>Service</label>
                    <p id="modalService">-</p>
                </div>
                <div class="detail-group">
                    <label>Category</label>
                    <p id="modalCategory">-</p>
                </div>
                <div class="detail-group">
                    <label>Description</label>
                    <p id="modalDescription">-</p>
                </div>
                <div class="detail-group">
                    <label>Duration</label>
                    <p id="modalDuration">-</p>
                </div>
                <div class="detail-group">
                    <label>Price</label>
                    <p id="modalPrice" class="price-text">-</p>
                </div>
                <div class="detail-group">
                    <label>Date</label>
                    <p id="modalDate">-</p>
                </div>
                <div class="detail-group">
                    <label>Time</label>
                    <p id="modalTime">-</p>
                </div>
                <div class="detail-group">
                    <label>Price</label>
                    <p id="modalPrice" class="price-text">-</p>
                </div>
            </div>

            <div class="modal-section">
                <h4 class="section-title">Customer Information</h4>
                <div class="detail-group">
                    <label>Name</label>
                    <p id="modalCustomerName">-</p>
                </div>
                <div class="detail-group">
                    <label>Email</label>
                    <p id="modalEmail">-</p>
                </div>
                <div class="detail-group">
                    <label>Contact Number</label>
                    <p id="modalContact">-</p>
                </div>
            </div>

            <div class="modal-section">
                <h4 class="section-title">Status</h4>
                <div class="detail-group">
                    <label>Reservation Status</label>
                    <p id="modalStatus">-</p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeReservationModal()">Close</button>
            <button class="btn-primary" onclick="editReservation()">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
    </div>
</div>
