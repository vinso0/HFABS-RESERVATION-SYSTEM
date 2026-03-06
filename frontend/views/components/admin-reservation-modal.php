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
                    <label>Schedule Date</label>
                    <p id="modalScheduleDate">-</p>
                </div>
                <div class="detail-group">
                    <label>Reservation Date</label>
                    <p id="modalReservationDate">-</p>
                </div>
                <div class="detail-group">
                    <label>Time</label>
                    <p id="modalTime">-</p>
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

<!-- Edit Reservation Modal -->
<div class="modal-overlay" id="editReservationModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Edit Reservation</h3>
            <button class="modal-close" onclick="closeEditReservationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editReservationForm" class="modal-body">
            <input type="hidden" id="editReservationId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="editReservationDate">
                        <i class="fas fa-calendar"></i>
                        Reservation Date *
                    </label>
                    <input type="date" id="editReservationDate" required>
                </div>
                
                <div class="form-group">
                    <label for="editReservationTime">
                        <i class="fas fa-clock"></i>
                        Reservation Time *
                    </label>
                    <input type="time" id="editReservationTime" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="editReservationStatus">
                    <i class="fas fa-flag"></i>
                    Status *
                </label>
                <select id="editReservationStatus" required>
                    <option value="">Select status</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rescheduled">Rescheduled</option>
                    <option value="no-show">No-Show</option>
                </select>
            </div>
            
            <div class="modal-info-box">
                <i class="fas fa-info-circle"></i>
                <p>Changing the date/time will mark the reservation as "Rescheduled".</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditReservationModal()">Cancel</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <span>Update Reservation</span>
                </button>
            </div>
        </form>
    </div>
</div>