<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$gr_no = $_GET['gr_no'] ?? '';
if (!$gr_no) die("GR Number missing");

$db = new Database();
$student = $db->getStudentByGrNo($gr_no);
if (!$student) die("Student not found");

$collections = $db->getFeeCollections(['gr_no' => $gr_no]);
$settings = $db->getSchoolSettings();

// Branding Color
$brandColor = "#0c0784";

// Logo Logic
$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
            ? '../' . $settings['school_logo'] 
            : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee History - <?php echo htmlspecialchars($student['student_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap');
        
        body { 
            font-family: 'Outfit', sans-serif; 
            font-size: 13px; 
            margin: 0; 
            padding: 30px; 
            color: #1e293b; 
            background: #f1f5f9; 
        }
        
        .print-container { 
            max-width: 850px; 
            margin: auto; 
            background: white; 
            padding: 50px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            position: relative;
            overflow: hidden;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.03;
            width: 500px;
            pointer-events: none;
            z-index: 0;
        }

        .header { 
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px; 
            border-bottom: 3px solid <?= $brandColor ?>; 
            padding-bottom: 25px; 
            position: relative;
            z-index: 10;
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            object-contain;
        }

        .school-details {
            flex: 1;
        }

        .school-name { 
            font-size: 28px; 
            font-weight: 900; 
            color: <?= $brandColor ?>; 
            text-transform: uppercase; 
            margin-bottom: 5px; 
            line-height: 1;
        }
        
        .school-info { 
            font-size: 14px; 
            color: #64748b; 
            font-weight: 500;
        }
        
        .title-section { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            background: #f8fafc; 
            padding: 15px 25px; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            position: relative;
            z-index: 10;
        }
        
        .report-title { 
            font-size: 20px; 
            font-weight: 800; 
            color: #0f172a; 
            letter-spacing: 1px;
        }
        
        .print-date { 
            font-size: 11px; 
            color: #94a3b8; 
            font-weight: 600;
            text-transform: uppercase;
        }

        .student-card { 
            display: grid; 
            grid-template-columns: 1.5fr 1.5fr 1fr 1fr; 
            gap: 20px; 
            background: #f8fafc;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            position: relative;
            z-index: 10;
        }
        
        .info-group { margin-bottom: 0; }
        .label { 
            font-size: 10px; 
            font-weight: 800; 
            color: #94a3b8; 
            text-transform: uppercase; 
            display: block; 
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .value { 
            font-size: 16px; 
            font-weight: 700; 
            color: #1e293b; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; position: relative; z-index: 10; }
        th { 
            background: <?= $brandColor ?>; 
            color: white; 
            font-weight: 700; 
            text-align: left; 
            padding: 15px; 
            text-transform: uppercase; 
            font-size: 11px; 
            letter-spacing: 1px;
        }
        
        th:first-child { border-radius: 10px 0 0 0; }
        th:last-child { border-radius: 0 10px 0 0; }
        
        td { 
            padding: 15px; 
            border-bottom: 1px solid #f1f5f9; 
            font-size: 14px;
        }
        
        tr:nth-child(even) { background: #f8fafc; }
        
        .month-highlight {
            font-weight: 800;
            color: <?= $brandColor ?>;
        }
        
        .receipt-id {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            color: #64748b;
        }
        
        .amount { 
            font-weight: 800; 
            text-align: right; 
            color: #0f172a;
        }

        .summary-box { 
            margin-top: 40px; 
            display: flex; 
            justify-content: flex-end; 
            position: relative;
            z-index: 10;
        }
        
        .total-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            width: 300px; 
            background: <?= $brandColor ?>; 
            color: white; 
            padding: 20px; 
            border-radius: 15px; 
            font-size: 18px; 
            font-weight: 900; 
            box-shadow: 0 10px 15px -3px rgba(12, 7, 132, 0.3);
        }

        .footer { 
            margin-top: 80px; 
            text-align: center; 
            border-top: 1px dashed #e2e8f0; 
            padding-top: 30px; 
            position: relative;
            z-index: 10;
        }
        
        .dev-info {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            background: #f8fafc;
            padding: 10px 25px;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            margin-top: 20px;
        }
        
        .dev-label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        .dev-name { font-size: 13px; font-weight: 800; color: #1e293b; }
        .dev-contact { font-size: 12px; color: <?= $brandColor ?>; font-weight: 600; text-decoration: none; }

        @media print {
            body { background: white; padding: 0; }
            .print-container { box-shadow: none; border-radius: 0; width: 100%; max-width: 100%; padding: 0; }
            .no-print { display: none !important; }
            .total-row { box-shadow: none; border: 2px solid <?= $brandColor ?>; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 30px; display: flex; justify-content: center; gap: 15px;">
        <button onclick="window.print()" style="padding: 12px 30px; cursor: pointer; background: <?= $brandColor ?>; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.2s;">
            <i class="fas fa-print"></i> PRINT HISTORY
        </button>
        <button onclick="window.close()" style="padding: 12px 30px; cursor: pointer; background: #fff; color: #64748b; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; font-weight: 800; transition: all 0.2s;">
            <i class="fas fa-times-circle"></i> CLOSE
        </button>
    </div>

    <div class="print-container">
        <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark">

        <div class="header">
            <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo" class="logo-img">
            <div class="school-details">
                <div class="school-name"><?php echo htmlspecialchars($settings['school_name']); ?></div>
                <div class="school-info">
                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($settings['school_address'] ?? $settings['address'] ?? ''); ?><br>
                    <i class="fas fa-phone-alt mr-1"></i> Contact: <?php echo htmlspecialchars($settings['school_contact']); ?> 
                    <?php if(!empty($settings['semis_code'])): ?>
                        | <i class="fas fa-id-card mr-1"></i> SEMIS: <?php echo htmlspecialchars($settings['semis_code']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="title-section">
            <div class="report-title">
                <i class="fas fa-file-invoice-dollar mr-2 text-indigo-500"></i> FEE STATEMENT
            </div>
            <div class="print-date">Generated: <?php echo date('d M, Y | h:i A'); ?></div>
        </div>

        <div class="student-card" style="margin-bottom: 35px;">
            <div class="info-group">
                <span class="label">Student Name</span>
                <span class="value"><?php echo htmlspecialchars($student['student_name']); ?></span>
            </div>
            <div class="info-group">
                <span class="label">Father's Name</span>
                <span class="value"><?php echo htmlspecialchars($student['father_name']); ?></span>
            </div>
            <div class="info-group">
                <span class="label">GR Number</span>
                <span class="value" style="color: <?= $brandColor ?>;">#<?php echo htmlspecialchars($student['gr_no']); ?></span>
            </div>
            <div class="info-group">
                <span class="label">Class</span>
                <span class="value"><?php echo htmlspecialchars($student['current_class']); ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Fee Month</th>
                    <th>Method</th>
                    <th>Receipt ID</th>
                    <th style="text-align:right">Discount</th>
                    <th style="text-align:right">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalPaid = 0;
                $totalDiscount = 0;
                if (empty($collections)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">No payment records found in the system.</td></tr>
                <?php else: ?>
                    <?php foreach ($collections as $p): 
                        $totalPaid += (float)$p['amount_paid'];
                        $totalDiscount += (float)$p['discount'];
                    ?>
                    <tr>
                        <td><i class="far fa-calendar-check mr-1 text-slate-300"></i> <?php echo $p['payment_date']; ?></td>
                        <td class="month-highlight"><?php echo date('F Y', strtotime($p['month_for'] . "-01")); ?></td>
                        <td><span style="font-size: 11px; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: bold;"><?php echo $p['payment_method']; ?></span></td>
                        <td class="receipt-id">#<?php echo $p['id']; ?></td>
                        <td class="amount text-red-500">-Rs. <?php echo number_format($totalDiscount); ?></td>
                        <td class="amount" style="color: <?= $brandColor ?>;">Rs. <?php echo number_format($p['amount_paid']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($collections)): ?>
        <div class="summary-box">
            <div class="total-row">
                <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Balance Cleared</span>
                <span>Rs. <?php echo number_format($totalPaid); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p style="font-weight: bold; color: #64748b; margin-bottom: 5px;">CERTIFICATION</p>
            <p style="margin-bottom: 25px; color: #94a3b8; font-style: italic;">"This fee statement is officially generated by the school management system. All records are verified as of the date of generation."</p>

            <div class="dev-info">
                <span class="dev-label">System Architect</span>
                <span class="dev-name">ABDUL RAFAY</span>
                <span class="dev-label">|</span>
                <a href="tel:+923710273699" class="dev-contact"><i class="fas fa-phone-alt mr-1"></i> +92 371 0273699</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 9px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 2px;">
                AQSA School Management System &bull; Version 2.0 &bull; Secure Digital Records
            </p>
        </div>
    </div>

    <script>
        // Smooth print
        window.onload = function() {
            // setTimeout(() => window.print(), 1000);
        };
    </script>
</body>
</html>
