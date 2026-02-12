<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$gr_nos_raw = isset($_GET['gr_no']) ? trim($_GET['gr_no']) : '';
$gr_nos = !empty($gr_nos_raw) ? explode(',', $gr_nos_raw) : [];

if (empty($gr_nos)) {
    die("GR Number(s) are required.");
}

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();
$students = [];

foreach ($gr_nos as $gr) {
    $gr = trim($gr);
    foreach ($allStudents as $s) {
        if (isset($s['gr_no']) && $s['gr_no'] == $gr) {
            $students[] = $s;
            break;
        }
    }
}

if (empty($students)) {
    echo '<div style="text-align:center; padding: 20px; font-family: sans-serif;">';
    echo '<h2 style="color: red;">Students Not Found</h2>';
    echo '<p>No students found with provided GR Number(s).</p>';
    echo '<a href="print_id_card.php">Go Back</a>';
    echo '</div>';
    exit;
}

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
            ? '../' . $settings['school_logo'] 
            : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo count($students) === 1 ? 'ID Card - ' . htmlspecialchars($students[0]['student_name']) : 'Bulk ID Cards (' . count($students) . ')'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        @page {
            size: 85.6mm 53.98mm; /* Standard ID-1 Credit Card Size */
            margin: 0;
        }
        @media print {
            html, body {
                width: 85.6mm;
                height: 53.98mm;
                margin: 0 !important;
                padding: 0 !important;
                background-color: white;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                /* Disable Flex Centering for Print */
                display: block !important; 
                min-height: 0 !important;
                justify-content: start !important;
                align-items: start !important;
            }
            .no-print {
                display: none !important;
            }
            .card-container {
                border: none !important; /* Remove border for actual print if bleed isn't an issue, or keep thin */
                box-shadow: none !important;
                page-break-inside: avoid;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
            }
        }
        
        .id-card {
            width: 85.6mm; /* ISO/IEC 7810 ID-1 standard width */
            height: 53.98mm; /* ISO/IEC 7810 ID-1 standard height */
            background: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4">

    <!-- Controls -->
    <div class="no-print mb-8 flex gap-4">
        <a href="print_id_card.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow transition">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow transition font-bold">
            <i class="fas fa-print"></i> Print Cards (<?php echo count($students); ?>)
        </button>
    </div>

    <div class="flex flex-col items-center gap-8">
    <?php foreach ($students as $student): ?>
        <!-- ID Card Container -->
        <div class="card-container bg-white shadow-xl rounded-lg overflow-hidden relative border border-gray-200" style="width: 86mm; height: 54mm;">
            <div class="id-card">
                <!-- Background Watermark -->
                <img src="<?php echo $logoPath; ?>?v=<?php echo time(); ?>" class="watermark" alt="Watermark">

                <!-- Header -->
                <header class="bg-primary text-white p-1 flex items-center justify-between border-b-2 border-secondary z-10" style="background-color: #15803d; border-color: #f59e0b;">
                    <div class="flex items-center gap-2">
                        <img src="<?php echo $logoPath; ?>?v=<?php echo time(); ?>" alt="Logo" class="h-8 w-8 bg-white rounded-full p-0.5 object-contain">
                        <div>
                            <h1 class="text-[9px] font-bold uppercase leading-tight tracking-wide"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                            <p class="text-[6px] uppercase tracking-widest leading-none"><?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                        </div>
                    </div>
                    <div class="text-[7px] font-bold bg-green-700 text-white px-1 py-0.5 rounded uppercase" style="background-color: #15803d !important; color: white !important; -webkit-print-color-adjust: exact;">
                        Student Identity Card
                    </div>
                </header>

                <!-- Content -->
                <div class="flex-1 flex px-3 items-center gap-2 z-10">
                    <!-- Details -->
                    <div class="flex-1">
                        <div class="grid grid-cols-[68px_1fr] gap-x-1 gap-y-1 text-[9px] leading-tight items-baseline">
                            <span class="font-bold text-gray-700 uppercase">Name:</span>
                            <span class="font-bold text-gray-900 uppercase truncate"><?php echo htmlspecialchars($student['student_name']); ?></span>

                            <span class="font-bold text-gray-700 uppercase">Father Name:</span>
                            <span class="font-bold text-gray-900 uppercase truncate"><?php echo htmlspecialchars($student['father_name']); ?></span>

                            <span class="font-bold text-gray-700 uppercase">Class:</span>
                            <span class="font-bold text-gray-900 uppercase truncate lowercase capitalize"><?php echo htmlspecialchars($student['current_class']); ?></span>

                            <span class="font-bold text-gray-700 uppercase">GR No:</span>
                            <span class="font-bold text-red-600 uppercase truncate"><?php echo htmlspecialchars($student['gr_no']); ?></span>
                            
                            <span class="font-bold text-gray-700 uppercase">DOB:</span>
                            <span class="font-bold text-gray-900 truncate"><?php echo !empty($student['date_of_birth']) ? date('d-m-Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></span>

                            <span class="font-bold text-gray-700 uppercase">Gender:</span>
                            <span class="font-bold text-gray-900 uppercase truncate"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></span>

                            <span class="font-bold text-gray-700 uppercase">Contact:</span>
                            <span class="font-bold text-gray-900 uppercase truncate"><?php echo htmlspecialchars($student['father_contact'] ?? 'N/A'); ?></span>
                        </div>
                    </div>

                    <!-- Photo -->
                    <div class="flex-shrink-0">
                        <div class="w-[20mm] h-[24mm] border border-gray-300 bg-gray-50 overflow-hidden flex items-center justify-center shadow-sm">
                            <?php 
                            $imagePath = '';
                            if (!empty($student['profile_image'])) {
                                $possiblePaths = [
                                    '../' . $student['profile_image'],
                                    $student['profile_image'],
                                    '../pages/' . $student['profile_image']
                                ];

                                foreach ($possiblePaths as $path) {
                                    if (file_exists($path)) {
                                        $imagePath = $path;
                                        break;
                                    }
                                }
                            }
                            
                            if ($imagePath): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" class="w-full h-full object-cover object-top" style="object-position: top center;">
                            <?php else: ?>
                                <span class="text-[8px] text-gray-400 text-center p-1">No Photo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="px-2 pb-1 z-10 mt-auto flex justify-between items-end">
                    <div class="text-[6px] text-gray-500 italic">
                        <p>Valid during study period.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-20 border-b border-black mb-0.5"></div>
                        <p class="text-[6px] font-bold uppercase">PRINCIPAL: <?php echo htmlspecialchars($settings['headmaster_name']); ?></p>
                    </div>
                </footer>
                
                <!-- Bottom Stripe -->
                <div class="h-1 bg-green-700 w-full z-10"></div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

</body>
</html>
