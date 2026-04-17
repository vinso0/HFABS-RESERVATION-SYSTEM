<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/EmailService.php';

class NotificationService
{
    private $db;
    private $emailService;
    
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->emailService = new EmailService();
    }
    
    /**
     * Notify admins about a new reservation based on branch assignment
     */
    public function notifyAdminsNewReservation($reservationData)
    {
        error_log('[NotificationService] Notifying admins about new reservation: ' . json_encode($reservationData));
        
        // Get branch_id from reservation data
        $branchId = $reservationData['branch_id'] ?? null;
        if (!$branchId) {
            error_log('[NotificationService] No branch_id found in reservation data');
            return false;
        }
        
        // Get admins assigned to this specific branch
        $admins = $this->getActiveAdminsByBranch($branchId);
        
        if (empty($admins)) {
            error_log("[NotificationService] No active admins found for branch ID: $branchId");
            return false;
        }
        
        $successCount = 0;
        
        foreach ($admins as $admin) {
            // Send email notification
            if ($this->sendAdminEmailNotification($admin, $reservationData)) {
                $successCount++;
            }
            
            // Store in-app notification
            $this->storeInAppNotification($admin['user_id'], $reservationData);
        }
        
        error_log("[NotificationService] Successfully notified $successCount out of " . count($admins) . " admins for branch $branchId");
        return $successCount > 0;
    }
    
    /**
     * Get active admin and cashier users assigned to a specific branch
     */
    private function getActiveAdminsByBranch($branchId)
    {
        $query = "
            SELECT user_id, username, email, role, branch_id
            FROM users 
            WHERE role IN ('admin', 'cashier') 
            AND deleted_at IS NULL
            AND (branch_id = ? OR branch_id IS NULL)
            ORDER BY role DESC, username ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        
        return $admins;
    }
    
    /**
     * Get all active admin and cashier users (fallback method)
     */
    private function getActiveAdmins()
    {
        $query = "
            SELECT user_id, username, email, role, branch_id
            FROM users 
            WHERE role IN ('admin', 'cashier') 
            AND deleted_at IS NULL
            ORDER BY role DESC, username ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        
        return $admins;
    }
    
    /**
     * Send email notification to admin
     */
    private function sendAdminEmailNotification($admin, $reservationData)
    {
        try {
            error_log('[NotificationService] Sending email notification to admin: ' . $admin['email']);
            
            // Create a new EmailService instance for this email
            $emailService = new EmailService();
            
            $subject = 'New Reservation Received - Happy Face & Body Spa';
            $template = $this->getAdminNotificationEmailTemplate($admin, $reservationData);
            $textTemplate = $this->getAdminNotificationTextTemplate($admin, $reservationData);
            
            // Use reflection to access the private mailer property
            $reflection = new ReflectionClass($emailService);
            $mailerProperty = $reflection->getProperty('mailer');
            $mailerProperty->setAccessible(true);
            $mailer = $mailerProperty->getValue($emailService);
            
            $mailer->addAddress($admin['email'], $admin['username']);
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $template;
            $mailer->AltBody = $textTemplate;
            
            $result = $mailer->send();
            
            if ($result) {
                error_log('[NotificationService] Email notification sent successfully to: ' . $admin['email']);
            } else {
                error_log('[NotificationService] Failed to send email to: ' . $admin['email'] . ' - Error: ' . $mailer->ErrorInfo);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[NotificationService] Error sending email notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Store in-app notification for admin
     */
    private function storeInAppNotification($adminId, $reservationData)
    {
        try {
            $query = "
                INSERT INTO admin_notifications (
                    user_id, 
                    reservation_id, 
                    notification_type, 
                    title, 
                    message, 
                    is_read, 
                    created_at
                ) VALUES (?, ?, 'new_reservation', ?, ?, 0, NOW())
            ";
            
            $title = 'New Reservation Received';
            $message = "Customer {$reservationData['customer_name']} has made a reservation for {$reservationData['services_count']} service(s) on {$reservationData['reservation_date']}.";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('iiss', $adminId, $reservationData['reservation_id'], $title, $message);
            
            $result = $stmt->execute();
            
            if ($result) {
                error_log("[NotificationService] In-app notification stored for admin ID: $adminId");
            } else {
                error_log("[NotificationService] Failed to store in-app notification for admin ID: $adminId - Error: " . $stmt->error);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[NotificationService] Error storing in-app notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get HTML email template for admin notification
     */
    private function getAdminNotificationEmailTemplate($admin, $reservationData)
    {
        $reservationDate = date('F j, Y', strtotime($reservationData['reservation_date']));
        $customerName = htmlspecialchars($reservationData['customer_name']);
        $customerEmail = htmlspecialchars($reservationData['customer_email']);
        $branchName = htmlspecialchars($reservationData['branch_name']);
        $totalPrice = number_format($reservationData['total_price'], 2);
        $servicesCount = $reservationData['services_count'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>New Reservation - Happy Face & Body Spa</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #d91a7e; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #ffffff; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .reservation-details { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d91a7e; }
                .detail-row { margin: 10px 0; }
                .detail-label { font-weight: bold; color: #495057; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                .btn { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>🎉 New Reservation Alert!</h2>
                    <p>Happy Face & Body Spa - Admin Notification</p>
                </div>
                <div class=\"content\">
                    <p>Hi <strong>" . htmlspecialchars($admin['username']) . "</strong>,</p>
                    <p>A new reservation has been received and is ready for your attention:</p>
                    
                    <div class=\"reservation-details\">
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Customer:</span> {$customerName}
                        </div>
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Email:</span> {$customerEmail}
                        </div>
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Branch:</span> {$branchName}
                        </div>
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Date:</span> {$reservationDate}
                        </div>
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Services:</span> {$servicesCount} service(s)
                        </div>
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Total Amount:</span> ₱{$totalPrice}
                        </div>
                        <div class=\"detail-row\">
                            <span class=\"detail-label\">Status:</span> <span style=\"color: #ffc107; font-weight: bold;\">Pending</span>
                        </div>
                    </div>
                    
                    <p>Please log in to the admin panel to review and manage this reservation.</p>
                    
                    <div style=\"text-align: center; margin: 30px 0;\">
                        <a href=\"https://undappled-bea-schemeful.ngrok-free.dev/HFABS/frontend/views/admin-dashboard.php\" class=\"btn\">Go to Admin Panel</a>
                    </div>
                    
                    <p><em>This is an automated notification. Please do not reply to this email.</em></p>
                </div>
                <div class=\"footer\">
                    <p>&copy; 2026 Happy Face & Body Spa. All rights reserved.</p>
                    <p>Reservation ID: #" . htmlspecialchars($reservationData['reservation_id']) . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get text email template for admin notification
     */
    private function getAdminNotificationTextTemplate($admin, $reservationData)
    {
        $reservationDate = date('F j, Y', strtotime($reservationData['reservation_date']));
        $customerName = $reservationData['customer_name'];
        $customerEmail = $reservationData['customer_email'];
        $branchName = $reservationData['branch_name'];
        $totalPrice = number_format($reservationData['total_price'], 2);
        $servicesCount = $reservationData['services_count'];
        
        return "
        HAPPY FACE & BODY SPA - NEW RESERVATION ALERT
        
        Hi {$admin['username']},
        
        A new reservation has been received:
        
        ================================
        RESERVATION DETAILS
        ================================
        Customer: {$customerName}
        Email: {$customerEmail}
        Branch: {$branchName}
        Date: {$reservationDate}
        Services: {$servicesCount} service(s)
        Total Amount: ₱{$totalPrice}
        Status: Pending
        Reservation ID: #{$reservationData['reservation_id']}
        ================================
        
        Please log in to the admin panel to review and manage this reservation.
        
        Admin Panel: https://undappled-bea-schemeful.ngrok-free.dev/HFABS/frontend/views/admin-dashboard.php
        
        This is an automated notification. Please do not reply to this email.
        
        © 2026 Happy Face & Body Spa. All rights reserved.
        ";
    }
    
    /**
     * Get unread notifications for admin
     */
    public function getAdminNotifications($adminId, $limit = 10)
    {
        $query = "
            SELECT 
                notification_id,
                reservation_id,
                notification_type,
                title,
                message,
                is_read,
                created_at
            FROM admin_notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $adminId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notificationId, $adminId)
    {
        $query = "
            UPDATE admin_notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $notificationId, $adminId);
        
        return $stmt->execute();
    }
    
    /**
     * Get unread notification count for admin
     */
    public function getUnreadNotificationCount($adminId)
    {
        $query = "
            SELECT COUNT(*) as unread_count
            FROM admin_notifications
            WHERE user_id = ? AND is_read = 0
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['unread_count'] ?? 0;
    }
}