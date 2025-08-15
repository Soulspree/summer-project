<?php
/**
 * Payment Class - Payment Tracking System
 * Handles payment records, status updates, and financial management
 * 
 * @author Subham Shrestha
 * @project Musician Booking System - Prime College BIM
 * @version 1.0
 */

require_once __DIR__ . '/Database.php';

class Payment extends Database {
    
    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    
    // Payment type constants
    const TYPE_BOOKING = 'booking';
    const TYPE_ADVANCE = 'advance';
    const TYPE_FINAL = 'final';
    const TYPE_REFUND = 'refund';
    const TYPE_BONUS = 'bonus';
    
    // Payment method constants
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_ESEWA = 'esewa';
    const METHOD_KHALTI = 'khalti';
    const METHOD_IME_PAY = 'ime_pay';
    const METHOD_FONEPAY = 'fonepay';
    const METHOD_CHEQUE = 'cheque';
    
    private $table = 'payments';
    private $errors = [];
    
    /**
     * Create a new payment record
     * 
     * @param array $paymentData Payment information
     * @return array Result with status and payment_id or errors
     */
    public function createPayment($paymentData) {
        $this->clearErrors();
        
        // Validate required fields
        $requiredFields = ['booking_id', 'amount', 'payment_type', 'payment_method'];
        if (!$this->validateRequiredFields($paymentData, $requiredFields)) {
            return $this->returnError('Missing required payment fields');
        }
        
        // Validate payment data
        if (!$this->validatePaymentData($paymentData)) {
            return $this->returnError('Invalid payment data', $this->errors);
        }
        
        // Check if booking exists
        if (!$this->bookingExists($paymentData['booking_id'])) {
            return $this->returnError('Booking not found');
        }
        
        try {
            $this->beginTransaction();
            
            // Prepare payment data
            $data = [
                'booking_id' => $paymentData['booking_id'],
                'amount' => $paymentData['amount'],
                'payment_type' => $paymentData['payment_type'],
                'payment_method' => $paymentData['payment_method'],
                'payment_status' => $paymentData['payment_status'] ?? self::STATUS_PENDING,
                'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d H:i:s'),
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'reference_number' => $paymentData['reference_number'] ?? $this->generateReferenceNumber(),
                'notes' => $paymentData['notes'] ?? null,
                'received_by' => $paymentData['received_by'] ?? null,
                'verified_by' => $paymentData['verified_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $paymentId = $this->create($this->table, $data);
            
            if (!$paymentId) {
                throw new Exception('Failed to create payment record');
            }
            
            // Update booking payment status if payment is completed
            if ($data['payment_status'] === self::STATUS_PAID) {
                $this->updateBookingPaymentStatus($paymentData['booking_id']);
            }
            
            // Log activity
            $this->logPaymentActivity($paymentId, 'payment_created', $data['amount']);
            
            $this->commit();
            
            return [
                'success' => true,
                'message' => 'Payment record created successfully',
                'payment_id' => $paymentId,
                'reference_number' => $data['reference_number']
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return $this->returnError('Failed to create payment: ' . $e->getMessage());
        }
    }
    
    /**
     * Update payment status
     * 
     * @param int $paymentId Payment ID
     * @param string $status New status
     * @param array $additionalData Additional update data
     * @return array Result with success status
     */
    public function updatePaymentStatus($paymentId, $status, $additionalData = []) {
        $this->clearErrors();
        
        if (!$this->isValidStatus($status)) {
            return $this->returnError('Invalid payment status');
        }
        
        try {
            $this->beginTransaction();
            
            // Get current payment
            $payment = $this->getPaymentById($paymentId);
            if (!$payment['success']) {
                throw new Exception('Payment not found');
            }
            
            $updateData = array_merge([
                'payment_status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], $additionalData);
            
            // Add verification data if status is paid
            if ($status === self::STATUS_PAID && !isset($updateData['verified_at'])) {
                $updateData['verified_at'] = date('Y-m-d H:i:s');
            }
            
            $updated = $this->update($this->table, $updateData, ['payment_id' => $paymentId]);
            
            if (!$updated) {
                throw new Exception('Failed to update payment status');
            }
            
            // Update booking payment status
            $this->updateBookingPaymentStatus($payment['data']['booking_id']);
            
            // Log activity
            $this->logPaymentActivity($paymentId, 'status_updated', $payment['data']['amount'], $status);
            
            $this->commit();
            
            return [
                'success' => true,
                'message' => 'Payment status updated successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return $this->returnError('Failed to update payment status: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payment by ID with booking details
     * 
     * @param int $paymentId Payment ID
     * @return array Payment data or error
     */
    public function getPaymentById($paymentId) {
        $this->clearErrors();
        
        try {
            $query = "
                SELECT p.*, 
                       b.event_title,
                       b.event_date,
                       b.total_amount as booking_amount,
                       u.full_name as client_name,
                       m.full_name as musician_name
                FROM {$this->table} p
                JOIN bookings b ON p.booking_id = b.booking_id
                JOIN users u ON b.client_id = u.user_id
                JOIN users m ON b.musician_id = m.user_id
                WHERE p.payment_id = ?
            ";
            
            $payment = $this->query($query, [$paymentId])->fetch();
            
            if (!$payment) {
                return $this->returnError('Payment not found');
            }
            
            return [
                'success' => true,
                'data' => $payment
            ];
            
        } catch (Exception $e) {
            return $this->returnError('Failed to retrieve payment: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payments by booking ID
     * 
     * @param int $bookingId Booking ID
     * @return array List of payments
     */
    public function getPaymentsByBooking($bookingId) {
        $this->clearErrors();
        
        try {
            $query = "
                SELECT * FROM {$this->table}
                WHERE booking_id = ?
                ORDER BY created_at DESC
            ";
            
            $payments = $this->query($query, [$bookingId])->fetchAll();
            
            return [
                'success' => true,
                'data' => $payments,
                'total' => count($payments)
            ];
            
        } catch (Exception $e) {
            return $this->returnError('Failed to retrieve payments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payments for a musician with filtering and pagination
     * 
     * @param int $musicianId Musician ID
     * @param array $filters Filtering options
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Paginated payments data
     */
    public function getMusicianPayments($musicianId, $filters = [], $page = 1, $limit = 20) {
        $this->clearErrors();
        
        try {
            $conditions = ['b.musician_id = ?'];
            $params = [$musicianId];
            
            // Add filters
            if (!empty($filters['status'])) {
                $conditions[] = 'p.payment_status = ?';
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['payment_type'])) {
                $conditions[] = 'p.payment_type = ?';
                $params[] = $filters['payment_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = 'DATE(p.payment_date) >= ?';
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = 'DATE(p.payment_date) <= ?';
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions[] = 'p.amount >= ?';
                $params[] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions[] = 'p.amount <= ?';
                $params[] = $filters['amount_max'];
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // Get total count
            $countQuery = "
                SELECT COUNT(*) as total
                FROM {$this->table} p
                JOIN bookings b ON p.booking_id = b.booking_id
                WHERE {$whereClause}
            ";
            
            $totalResult = $this->query($countQuery, $params)->fetch();
            $total = $totalResult['total'];
            
            // Get paginated data
            $offset = ($page - 1) * $limit;
            $dataQuery = "
                SELECT p.*,
                       b.event_title,
                       b.event_date,
                       b.total_amount as booking_amount,
                       u.full_name as client_name,
                       u.email as client_email
                FROM {$this->table} p
                JOIN bookings b ON p.booking_id = b.booking_id
                JOIN users u ON b.client_id = u.user_id
                WHERE {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $payments = $this->query($dataQuery, $params)->fetchAll();
            
            return [
                'success' => true,
                'data' => $payments,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            return $this->returnError('Failed to retrieve musician payments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payment statistics for musician
     * 
     * @param int $musicianId Musician ID
     * @param array $dateRange Date range filter
     * @return array Payment statistics
     */
    public function getMusicianPaymentStats($musicianId, $dateRange = []) {
        $this->clearErrors();
        
        try {
            $conditions = ['b.musician_id = ?'];
            $params = [$musicianId];
            
            if (!empty($dateRange['from'])) {
                $conditions[] = 'DATE(p.payment_date) >= ?';
                $params[] = $dateRange['from'];
            }
            
            if (!empty($dateRange['to'])) {
                $conditions[] = 'DATE(p.payment_date) <= ?';
                $params[] = $dateRange['to'];
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            $query = "
                SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN p.payment_status = 'paid' THEN p.amount ELSE 0 END) as total_earned,
                    SUM(CASE WHEN p.payment_status = 'pending' THEN p.amount ELSE 0 END) as pending_amount,
                    AVG(CASE WHEN p.payment_status = 'paid' THEN p.amount ELSE NULL END) as avg_payment,
                    COUNT(CASE WHEN p.payment_status = 'paid' THEN 1 END) as completed_payments,
                    COUNT(CASE WHEN p.payment_status = 'pending' THEN 1 END) as pending_payments,
                    COUNT(CASE WHEN p.payment_status = 'failed' THEN 1 END) as failed_payments
                FROM {$this->table} p
                JOIN bookings b ON p.booking_id = b.booking_id
                WHERE {$whereClause}
            ";
            
            $stats = $this->query($query, $params)->fetch();
            
            // Get monthly earnings
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(p.payment_date, '%Y-%m') as month,
                    SUM(CASE WHEN p.payment_status = 'paid' THEN p.amount ELSE 0 END) as monthly_earnings
                FROM {$this->table} p
                JOIN bookings b ON p.booking_id = b.booking_id
                WHERE {$whereClause}
                GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12
            ";
            
            $monthlyStats = $this->query($monthlyQuery, $params)->fetchAll();
            
            return [
                'success' => true,
                'data' => [
                    'overview' => $stats,
                    'monthly_earnings' => $monthlyStats
                ]
            ];
            
        } catch (Exception $e) {
            return $this->returnError('Failed to retrieve payment statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Process payment refund
     * 
     * @param int $paymentId Payment ID
     * @param float $refundAmount Refund amount
     * @param string $reason Refund reason
     * @return array Result with success status
     */
    public function processRefund($paymentId, $refundAmount, $reason = '') {
        $this->clearErrors();
        
        try {
            $this->beginTransaction();
            
            // Get original payment
            $payment = $this->getPaymentById($paymentId);
            if (!$payment['success']) {
                throw new Exception('Payment not found');
            }
            
            $originalPayment = $payment['data'];
            
            // Validate refund amount
            if ($refundAmount <= 0 || $refundAmount > $originalPayment['amount']) {
                throw new Exception('Invalid refund amount');
            }
            
            // Create refund payment record
            $refundData = [
                'booking_id' => $originalPayment['booking_id'],
                'amount' => -$refundAmount, // Negative amount for refund
                'payment_type' => self::TYPE_REFUND,
                'payment_method' => $originalPayment['payment_method'],
                'payment_status' => self::STATUS_PAID,
                'payment_date' => date('Y-m-d H:i:s'),
                'reference_number' => 'REFUND-' . $originalPayment['reference_number'],
                'notes' => 'Refund for payment #' . $paymentId . '. Reason: ' . $reason,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $refundId = $this->create($this->table, $refundData);
            
            if (!$refundId) {
                throw new Exception('Failed to create refund record');
            }
            
            // Update original payment status if full refund
            if ($refundAmount == $originalPayment['amount']) {
                $this->update($this->table, 
                    ['payment_status' => self::STATUS_REFUNDED],
                    ['payment_id' => $paymentId]
                );
            }
            
            // Update booking payment status
            $this->updateBookingPaymentStatus($originalPayment['booking_id']);
            
            // Log activity
            $this->logPaymentActivity($refundId, 'refund_processed', $refundAmount);
            
            $this->commit();
            
            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_id' => $refundId
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return $this->returnError('Failed to process refund: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payment methods available in Nepal
     * 
     * @return array List of payment methods
     */
    public function getPaymentMethods() {
        return [
            self::METHOD_CASH => 'Cash Payment',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_ESEWA => 'eSewa',
            self::METHOD_KHALTI => 'Khalti',
            self::METHOD_IME_PAY => 'IME Pay',
            self::METHOD_FONEPAY => 'FonePay',
            self::METHOD_CHEQUE => 'Cheque'
        ];
    }
    
    /**
     * Validate payment data
     * 
     * @param array $data Payment data to validate
     * @return bool Validation result
     */
    private function validatePaymentData($data) {
        $this->clearErrors();
        
        // Validate amount
        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $this->errors[] = 'Amount must be a positive number';
        }
        
        // Validate payment type
        if (!in_array($data['payment_type'], [
            self::TYPE_BOOKING, self::TYPE_ADVANCE, self::TYPE_FINAL, 
            self::TYPE_REFUND, self::TYPE_BONUS
        ])) {
            $this->errors[] = 'Invalid payment type';
        }
        
        // Validate payment method
        if (!in_array($data['payment_method'], array_keys($this->getPaymentMethods()))) {
            $this->errors[] = 'Invalid payment method';
        }
        
        // Validate status if provided
        if (isset($data['payment_status']) && !$this->isValidStatus($data['payment_status'])) {
            $this->errors[] = 'Invalid payment status';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Check if payment status is valid
     * 
     * @param string $status Status to check
     * @return bool Valid status
     */
    private function isValidStatus($status) {
        return in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_REFUNDED,
            self::STATUS_PARTIALLY_PAID
        ]);
    }
    
    /**
     * Check if booking exists
     * 
     * @param int $bookingId Booking ID
     * @return bool Booking exists
     */
    private function bookingExists($bookingId) {
        try {
            $count = $this->query(
                "SELECT COUNT(*) as count FROM bookings WHERE booking_id = ?",
                [$bookingId]
            )->fetch();
            
            return $count['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update booking payment status based on payments
     * 
     * @param int $bookingId Booking ID
     * @return void
     */
    private function updateBookingPaymentStatus($bookingId) {
        try {
            // Get booking total amount
            $booking = $this->query(
                "SELECT total_amount FROM bookings WHERE booking_id = ?",
                [$bookingId]
            )->fetch();
            
            if (!$booking) return;
            
            // Calculate total paid amount
            $paidAmount = $this->query(
                "SELECT COALESCE(SUM(amount), 0) as total_paid 
                 FROM {$this->table} 
                 WHERE booking_id = ? AND payment_status = ?",
                [$bookingId, self::STATUS_PAID]
            )->fetch();
            
            $totalPaid = $paidAmount['total_paid'];
            $totalAmount = $booking['total_amount'];
            
            // Determine payment status
            $paymentStatus = 'unpaid';
            if ($totalPaid >= $totalAmount) {
                $paymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partial';
            }
            
            // Update booking
            $this->query(
                "UPDATE bookings SET payment_status = ? WHERE booking_id = ?",
                [$paymentStatus, $bookingId]
            );
            
        } catch (Exception $e) {
            // Log error but don't throw exception
            error_log("Failed to update booking payment status: " . $e->getMessage());
        }
    }
    
    /**
     * Generate unique reference number
     * 
     * @return string Reference number
     */
    private function generateReferenceNumber() {
        return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    /**
     * Log payment activity
     * 
     * @param int $paymentId Payment ID
     * @param string $activity Activity type
     * @param float $amount Payment amount
     * @param string $extra Extra information
     * @return void
     */
    private function logPaymentActivity($paymentId, $activity, $amount, $extra = '') {
        try {
            $description = "Payment #{$paymentId} - Amount: Rs. {$amount}";
            if ($extra) {
                $description .= " - {$extra}";
            }
            
            $this->query(
                "INSERT INTO activity_logs (user_id, activity_type, description, created_at) 
                 VALUES (?, ?, ?, ?)",
                [
                    $_SESSION['user_id'] ?? null,
                    $activity,
                    $description,
                    date('Y-m-d H:i:s')
                ]
            );
        } catch (Exception $e) {
            // Log error but don't throw exception
            error_log("Failed to log payment activity: " . $e->getMessage());
        }
    }
    
    /**
     * Clear errors array
     * 
     * @return void
     */
    private function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Return error response
     * 
     * @param string $message Error message
     * @param array $errors Additional errors
     * @return array Error response
     */
    private function returnError($message, $errors = []) {
        return [
            'success' => false,
            'message' => $message,
            'errors' => empty($errors) ? $this->errors : $errors
        ];
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data Data to validate
     * @param array $required Required fields
     * @return bool Validation result
     */
    private function validateRequiredFields($data, $required) {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errors[] = "Field '{$field}' is required";
            }
        }
        return empty($this->errors);
    }
}
?> 
