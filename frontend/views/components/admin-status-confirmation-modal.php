<!-- Status Confirmation Modal -->
<div class="modal-overlay" id="statusConfirmationModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">Confirm Status Change</h3>
            <button class="modal-close" onclick="closeStatusConfirmationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="confirmation-content">
                <div class="confirmation-icon" id="modalIcon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <p id="modalMessage">Are you sure you want to change the status of this reservation?</p>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeStatusConfirmationModal()">Cancel</button>
            <button class="btn-primary" id="modalConfirmBtn" onclick="confirmStatusChange()">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
</div>
