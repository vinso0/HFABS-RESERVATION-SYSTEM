<!-- Customer Reservations Modal -->
<div class="modal" id="customerReservationsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalCustomerName">Customer Reservations</h3>
            <button class="modal-close" id="closeReservationsModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="modal-section">
                <h4>Customer Details</h4>
                <div class="customer-details">
                    <div class="detail-item">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value" id="modalCustomerFullName">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Contact:</span>
                        <span class="detail-value" id="modalCustomerContact">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value" id="modalCustomerEmail">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Reservations:</span>
                        <span class="detail-value" id="modalTotalReservations">0</span>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <h4>Reservation History</h4>
                <div class="reservations-list" id="reservationsList">
                    <!-- Reservation items will be populated by JavaScript -->
                </div>
                
                <div class="no-reservations" id="noReservations" style="display: none;">
                    <i class="fas fa-calendar-times"></i>
                    <p>No reservations found for this customer</p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-primary" id="closeReservationsModalBtn">Close</button>
        </div>
    </div>
</div>

<!-- Reservation Details Modal -->
<div class="modal" id="reservationDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalReservationId">Reservation Details</h3>
            <button class="modal-close" id="closeReservationDetailsModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="reservation-details" id="reservationDetails">
                <!-- Reservation details will be populated by JavaScript -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-primary" id="closeReservationDetailsModalBtn">Close</button>
        </div>
    </div>
</div>

<style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        animation: fadeIn 0.3s ease;
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: #fff;
        border-radius: 12px;
        max-width: 800px;
        width: 90%;
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.3s ease;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #f0f0f0;
    }

    .modal-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: #d91a7e;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #999;
        cursor: pointer;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .modal-close:hover {
        background-color: #f5f5f5;
        color: #333;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .modal-section {
        margin-bottom: 24px;
    }

    .modal-section h4 {
        font-size: 16px;
        font-weight: 600;
        color: #d91a7e;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f0f0f0;
    }

    /* Customer Details */
    .customer-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 12px;
        color: #666;
        font-weight: 500;
    }

    .detail-value {
        font-size: 14px;
        color: #333;
        font-weight: 600;
    }

    /* Reservations List */
    .reservations-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .reservation-item {
        background-color: #fafafa;
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .reservation-item:hover {
        background-color: #fef1f8;
        border-color: #d91a7e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(217, 26, 126, 0.1);
    }

    .reservation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .reservation-id {
        font-size: 13px;
        font-weight: 600;
        color: #333;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
    }

    .status-confirmed {
        background-color: #d91a7e;
        color: #fff;
    }

    .status-completed {
        background-color: #0d894f;
        color: #fff;
    }

    .status-no-show {
        background-color: #d82626;
        color: #fff;
    }

    .status-cancelled {
        background-color: #d5d5d5;
        color: #666;
    }

    .status-rescheduled {
        background-color: #fff4e6;
        color: #c77700;
    }

    .reservation-service {
        font-size: 14px;
        color: #666;
        margin-bottom: 4px;
    }

    .reservation-date {
        font-size: 13px;
        color: #333;
        font-weight: 500;
    }

    /* No Reservations */
    .no-reservations {
        text-align: center;
        padding: 3rem 1rem;
        color: #999;
    }

    .no-reservations i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #d5d5d5;
    }

    .no-reservations p {
        font-size: 14px;
        margin: 0;
    }

    /* Reservation Details */
    .reservation-details {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .reservation-detail-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
    }

    .reservation-detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .reservation-detail-label {
        font-size: 12px;
        color: #666;
        font-weight: 500;
    }

    .reservation-detail-value {
        font-size: 14px;
        color: #333;
        font-weight: 600;
    }

    /* Modal Footer */
    .modal-footer {
        padding: 20px 24px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-primary {
        background-color: #d91a7e;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #b01565;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(217, 26, 126, 0.3);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            max-height: 90vh;
        }
        
        .modal-header {
            padding: 16px 20px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 16px 20px;
            flex-direction: column;
        }
        
        .customer-details {
            grid-template-columns: 1fr;
        }
        
        .reservation-detail-group {
            grid-template-columns: 1fr;
        }
    }
</style>
