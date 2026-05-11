<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) die("Transaction ID missing");

$db = new Database();
$collections = $db->getFeeCollections(['id' => $id]);
if (empty($collections)) die("Transaction not found");

$payment = $collections[0];
$student = $db->getStudentByGrNo($payment['gr_no']);
$settings = $db->getSchoolSettings();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt - <?php echo $id; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; margin: 0; padding: 20px; color: #333; }
        .receipt-container { max-width: 400px; margin: auto; border: 1px dashed #ccc; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .school-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .school-info { font-size: 12px; color: #666; }
        .receipt-title { font-size: 16px; font-weight: bold; margin: 15px 0; text-decoration: underline; text-align: center; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .label { font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; border-top: 1px solid #eee; padding-top: 10px; }
        .amount-section { background: #f9f9f9; padding: 10px; margin-top: 15px; border: 1px solid #eee; }
        .amount-total { font-size: 18px; font-weight: bold; border-top: 1px solid #333; margin-top: 5px; padding-top: 5px; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .receipt-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #4f46e5; color: white; border: none; rounded: 5px;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #eee; border: none; rounded: 5px;">Close</button>
    </div>

    <div class="receipt-container">
        <div class="header">
            <div class="school-name"><?php echo htmlspecialchars($settings['school_name']); ?></div>
            <div class="school-info">
                <?php echo htmlspecialchars($settings['school_address']); ?><br>
                Contact: <?php echo htmlspecialchars($settings['school_contact']); ?>
            </div>
        </div>

        <div class="receipt-title">FEE RECEIPT</div>

        <div class="info-row">
            <span class="label">Receipt No:</span>
            <span>#<?php echo $id; ?></span>
        </div>
        <div class="info-row">
            <span class="label">Date:</span>
            <span><?php echo $payment['payment_date']; ?></span>
        </div>

        <div style="margin: 20px 0; border-top: 1px solid #eee; padding-top: 10px;">
            <div class="info-row">
                <span class="label">Student:</span>
                <span><?php echo htmlspecialchars($student['student_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">GR No:</span>
                <span><?php echo htmlspecialchars($student['gr_no']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Class:</span>
                <span><?php echo htmlspecialchars($student['current_class']); ?></span>
            </div>
            <div class="info-row" style="background: #eee; padding: 5px; margin: 5px 0;">
                <span class="label">Fee for Month:</span>
                <span style="font-weight: bold;"><?php echo date('F Y', strtotime($payment['month_for'] . "-01")); ?></span>
            </div>
        </div>

        <div class="amount-section">
            <div class="info-row">
                <?php 
                $tuition = (float)($payment['tuition_fee'] ?: ((float)$payment['amount_paid'] - (float)($payment['admission_fee'] ?? 0) - (float)($payment['exam_fee'] ?? 0) - (float)($payment['other_fee'] ?? 0))); 
                $discount = (float)($payment['discount'] ?? 0);
                ?>
                <span>Tuition Fee:</span>
                <span>Rs. <?php echo number_format($tuition + $discount, 2); ?></span>
            </div>

            <?php if (!empty($payment['admission_fee']) && $payment['admission_fee'] > 0): ?>
            <div class="info-row">
                <span>Admission Fee:</span>
                <span>Rs. <?php echo number_format($payment['admission_fee'], 2); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($payment['exam_fee']) && $payment['exam_fee'] > 0): ?>
            <div class="info-row">
                <span>Exam Fee:</span>
                <span>Rs. <?php echo number_format($payment['exam_fee'], 2); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($payment['other_fee']) && $payment['other_fee'] > 0): ?>
            <div class="info-row">
                <span><?php echo htmlspecialchars($payment['other_label'] ?: 'Other Fee'); ?>:</span>
                <span>Rs. <?php echo number_format($payment['other_fee'], 2); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($payment['discount'] > 0): ?>
            <div class="info-row" style="color: red;">
                <span>Discount:</span>
                <span>- Rs. <?php echo number_format($payment['discount'], 2); ?></span>
            </div>
            <?php endif; ?>

            <div class="info-row amount-total">
                <span>GRAND TOTAL:</span>
                <span>Rs. <?php echo number_format($payment['amount_paid'], 2); ?></span>
            </div>
        </div>

        <div class="info-row" style="margin-top: 10px; font-size: 12px;">
            <span class="label">Payment Method:</span>
            <span><?php echo $payment['payment_method']; ?></span>
        </div>

        <div class="footer">
            Computer Generated Receipt.<br>
            Thank you for your payment!
            
            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc; font-size: 10px; color: #888;">
                Software Developed by <strong>Abdul Rafay</strong><br>
                Contact: +92 371 0273699
            </div>
        </div>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>
