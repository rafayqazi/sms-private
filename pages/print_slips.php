<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Verify Admin/Teacher permissions
if (!canAccessPage('students.php')) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['class']) || !isset($_GET['exam_name'])) {
    die("Invalid request. Class and Exam Name are required.");
}

$class = $_GET['class'];
$examName = $_GET['exam_name'];
$slipsPerPage = isset($_GET['slips_per_page']) && $_GET['slips_per_page'] == '2' ? 2 : 1;

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();

// Filter students by class
$students = array_filter($allStudents, function($student) use ($class) {
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    return !$isAlumni && isset($student['current_class']) && $student['current_class'] === $class;
});

// Sort by GR No
usort($students, function($a, $b) {
    return intval($a['gr_no']) - intval($b['gr_no']);
});

$subjects = $_GET['subjects'] ?? [];
$dates = $_GET['dates'] ?? [];
$days = $_GET['days'] ?? [];
$times = $_GET['times'] ?? [];

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
    ? '../' . $settings['school_logo'] 
    : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Slips - <?php echo htmlspecialchars($class); ?> (<?php echo $slipsPerPage; ?> Per Page)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --school-indigo: #4f46e5;
        }
        
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* 1 Slip Per Page Container */
        .page-container {
            width: 210mm;
            height: 296.5mm;
            background: white;
            margin: 0 auto 40px;
            padding: 10mm;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            overflow: hidden;
            page-break-after: always;
        }

        .slip-border {
            border: 4px double var(--school-indigo);
            padding: 8mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            flex: 1;
        }

        /* 2 Slips Per Page Specifics */
        .page-container-two {
            width: 210mm;
            height: 296.5mm;
            background: white;
            margin: 0 auto 40px;
            padding: 6mm 8mm;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            overflow: hidden;
            page-break-after: always;
        }

        .half-slip-wrapper {
            height: 137mm;
            max-height: 137mm;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .half-slip-border {
            border: 2.5px solid var(--school-indigo);
            border-radius: 12px;
            padding: 4mm 6mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            background: white;
        }

        .cut-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 6mm;
            margin: 2px 0;
            position: relative;
        }

        .cut-line {
            flex: 1;
            border-top: 1.5px dashed #94a3b8;
        }

        .cut-badge {
            font-size: 8.5px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 9999px;
            border: 1px dashed #cbd5e1;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Watermarks */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.035;
            width: 420px;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }

        .watermark-compact {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.035;
            width: 250px;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }

        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .page-container {
                width: 210mm !important;
                height: 296.5mm !important;
                margin: 0 !important;
                padding: 10mm !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: always;
                page-break-inside: avoid;
            }
            .page-container-two {
                width: 210mm !important;
                height: 296.5mm !important;
                margin: 0 !important;
                padding: 6mm 8mm !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: always;
                page-break-inside: avoid;
            }
        }

        .school-color-text { color: var(--school-indigo); }
        .school-color-bg { background-color: var(--school-indigo); }
        .school-color-border { border-color: var(--school-indigo); }
    </style>
</head>
<body>

    <!-- UI Header -->
    <div class="max-w-[210mm] mx-auto mb-8 no-print flex justify-between items-center bg-white p-6 rounded-3xl shadow-xl border border-slate-100">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Examination Slips</h1>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider <?php echo $slipsPerPage == 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'; ?>">
                    <?php echo $slipsPerPage == 2 ? '2 Slips / Page (Paper Saver)' : '1 Slip / Page (Standard)'; ?>
                </span>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Class <?php echo htmlspecialchars($class); ?> | Total Students: <?php echo count($students); ?></p>
        </div>
        <div class="flex gap-4">
            <button id="downloadPdfBtn" class="bg-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg shadow-indigo-200/50 hover:bg-indigo-700 font-black flex items-center gap-2 transition-all active:scale-95 uppercase tracking-widest text-xs">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </button>
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-xl shadow-lg shadow-slate-200/50 hover:bg-black font-black flex items-center gap-2 transition-all active:scale-95 uppercase tracking-widest text-xs">
                <i class="fas fa-print"></i> Print Now
            </button>
        </div>
    </div>

    <!-- Slips Wrapper -->
    <div id="pdf-content">
        <?php if ($slipsPerPage == 1): ?>
            <!-- 1 SLIP PER PAGE MODE -->
            <?php foreach ($students as $student): ?>
            <div class="page-container">
                <div class="slip-border">
                    <!-- Watermark -->
                    <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark">

                    <!-- Header -->
                    <div class="flex items-center gap-6 mb-4 border-b-4 school-color-border pb-4 relative z-10">
                        <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo" class="w-20 h-20 object-contain">
                        <div class="flex-1 text-center">
                            <h1 class="text-3xl font-black uppercase tracking-tighter school-color-text leading-none">
                                <?php echo htmlspecialchars($settings['school_name']); ?>
                            </h1>
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mt-1">
                                <?php echo htmlspecialchars($settings['address_tagline'] ?? 'Education for all'); ?>
                            </p>
                            <div class="mt-2 inline-block school-color-bg text-white px-8 py-1.5 rounded-lg text-lg font-black uppercase tracking-widest shadow-md">
                                Examination Slip
                            </div>
                        </div>
                    </div>

                    <!-- Exam Title -->
                    <div class="text-center mb-6 relative z-10">
                        <span class="text-xl font-black text-slate-800 uppercase border-b-2 border-dotted school-color-border px-6 pb-0.5">
                            <?php echo htmlspecialchars($examName); ?>
                        </span>
                    </div>

                    <!-- Student Info Row -->
                    <div class="flex gap-8 mb-6 relative z-10">
                        <div class="flex-1 space-y-4">
                            <div class="flex items-end gap-3">
                                <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">Name:</span>
                                <div class="flex-1 border-b-2 border-slate-100 font-black text-xl uppercase text-slate-800 px-2 pb-0.5">
                                    <?php echo htmlspecialchars($student['student_name']); ?>
                                </div>
                            </div>
                            <div class="flex items-end gap-3">
                                <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">Father:</span>
                                <div class="flex-1 border-b-2 border-slate-100 font-bold text-xl uppercase text-slate-800 px-2 pb-0.5">
                                    <?php echo htmlspecialchars($student['father_name']); ?>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-8">
                                <div class="flex items-end gap-3">
                                    <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">Class:</span>
                                    <div class="flex-1 border-b-2 border-slate-100 font-black text-xl uppercase text-slate-800 px-2 pb-0.5">
                                        <?php echo htmlspecialchars($student['current_class']); ?>
                                    </div>
                                </div>
                                <div class="flex items-end gap-3">
                                    <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">GR No:</span>
                                    <div class="flex-1 border-b-2 border-slate-100 font-black text-xl text-indigo-600 px-2 pb-0.5">
                                        #<?php echo htmlspecialchars($student['gr_no']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Photo -->
                        <div class="w-32 h-36 border-4 school-color-border rounded-xl overflow-hidden bg-slate-50 shadow-xl relative flex-shrink-0">
                            <?php 
                            $img = !empty($student['profile_image']) && file_exists('../' . $student['profile_image']) 
                                   ? '../' . $student['profile_image'] 
                                   : null;
                            if($img): ?>
                                <img src="<?php echo $img; ?>?v=<?php echo time(); ?>" class="w-full h-full object-cover object-top">
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-200">
                                    <i class="fas fa-user-circle text-4xl"></i>
                                    <span class="text-[8px] font-black uppercase tracking-widest mt-1">No Photo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Subjects Table -->
                    <div class="flex-1 relative z-10 mb-6">
                        <table class="w-full text-left border-collapse border-b-4 school-color-border overflow-hidden rounded-t-xl">
                            <thead class="school-color-bg text-white">
                                <tr class="text-[10px] font-black uppercase tracking-widest">
                                    <th class="p-3 border-r border-white/20">Examination Subjects</th>
                                    <th class="p-3 border-r border-white/20 text-center w-32">Date</th>
                                    <th class="p-3 border-r border-white/20 text-center w-32">Day</th>
                                    <th class="p-3 border-r border-white/20 text-center w-36">Timing</th>
                                    <th class="p-3 text-center w-40">Invigilator</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-700">
                                <?php 
                                // Ensure at least 6 rows for layout consistency
                                $rowCount = max(6, count($subjects));
                                for($i=0; $i < $rowCount; $i++): 
                                    $sub = htmlspecialchars($subjects[$i] ?? '');
                                    $date = isset($dates[$i]) && $dates[$i] ? date('d-m-Y', strtotime($dates[$i])) : '';
                                    $day = htmlspecialchars($days[$i] ?? '');
                                    $time = htmlspecialchars($times[$i] ?? '');
                                    $bg = ($i % 2 == 0) ? 'bg-white' : 'bg-slate-50/50';
                                ?>
                                <tr class="<?php echo $bg; ?> border-b border-slate-100 font-bold text-xs">
                                    <td class="p-2.5 border-r border-slate-100 uppercase truncate max-w-[200px]"><?php echo $sub; ?></td>
                                    <td class="p-2.5 border-r border-slate-100 text-center"><?php echo $date; ?></td>
                                    <td class="p-2.5 border-r border-slate-100 text-center uppercase"><?php echo $day; ?></td>
                                    <td class="p-2.5 border-r border-slate-100 text-center uppercase whitespace-nowrap"><?php echo $time; ?></td>
                                    <td class="p-2.5"></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Section -->
                    <div class="flex justify-between items-end bg-slate-50 p-4 rounded-2xl border border-slate-100 relative z-10">
                        <div class="space-y-1">
                            <h4 class="font-black text-[10px] uppercase school-color-text flex items-center gap-2">
                                <i class="fas fa-info-circle"></i> Instructions
                            </h4>
                            <ul class="text-[9px] font-bold text-slate-500 uppercase list-none space-y-0.5">
                                <li>1. Bring Original Slip Daily for Verification</li>
                                <li>2. Reach Examination Hall 15 Mins Before</li>
                                <li>3. No Electronic Devices Allowed</li>
                            </ul>
                        </div>
                        <div class="text-center group pr-2">
                            <div class="w-40 border-b-4 school-color-border mb-2 group-hover:scale-110 transition-transform"></div>
                            <p class="text-[10px] font-black uppercase tracking-widest school-color-text">School Principal</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- 2 SLIPS PER PAGE MODE (ECO / PAPER SAVER) -->
            <?php 
            $studentPairs = array_chunk($students, 2);
            foreach ($studentPairs as $pair): 
            ?>
            <div class="page-container-two">
                <!-- First Slip -->
                <?php 
                $student = $pair[0];
                ?>
                <div class="half-slip-wrapper">
                    <div class="half-slip-border">
                        <!-- Watermark -->
                        <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark-compact">

                        <!-- Header -->
                        <div class="flex items-center gap-4 border-b-2 school-color-border pb-2 relative z-10">
                            <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo" class="w-12 h-12 object-contain">
                            <div class="flex-1 text-center">
                                <h2 class="text-xl font-black uppercase tracking-tight school-color-text leading-tight">
                                    <?php echo htmlspecialchars($settings['school_name']); ?>
                                </h2>
                                <p class="text-[8px] font-black uppercase tracking-[0.2em] text-slate-500">
                                    <?php echo htmlspecialchars($settings['address_tagline'] ?? 'Education for all'); ?>
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="school-color-bg text-white px-3 py-0.5 rounded text-[9px] font-black uppercase tracking-widest shadow-sm">
                                    Examination Slip
                                </span>
                                <span class="text-[9px] font-black text-slate-800 uppercase px-2 py-0.5 border border-indigo-200 rounded bg-indigo-50/50">
                                    <?php echo htmlspecialchars($examName); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Student Info & Photo Row -->
                        <div class="flex items-center gap-4 my-1.5 relative z-10">
                            <div class="flex-1 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">Name:</span>
                                    <span class="font-black uppercase text-slate-900 truncate border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        <?php echo htmlspecialchars($student['student_name']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">Father:</span>
                                    <span class="font-bold uppercase text-slate-800 truncate border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        <?php echo htmlspecialchars($student['father_name']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">Class:</span>
                                    <span class="font-black uppercase text-slate-900 border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        <?php echo htmlspecialchars($student['current_class']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">GR No:</span>
                                    <span class="font-black text-indigo-600 border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        #<?php echo htmlspecialchars($student['gr_no']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Student Photo Compact -->
                            <div class="w-16 h-18 border-2 school-color-border rounded-lg overflow-hidden bg-slate-50 relative flex-shrink-0 flex items-center justify-center">
                                <?php 
                                $img = !empty($student['profile_image']) && file_exists('../' . $student['profile_image']) 
                                       ? '../' . $student['profile_image'] 
                                       : null;
                                if($img): ?>
                                    <img src="<?php echo $img; ?>?v=<?php echo time(); ?>" class="w-full h-full object-cover object-top">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                                        <i class="fas fa-user-circle text-2xl"></i>
                                        <span class="text-[6px] font-black uppercase tracking-wider mt-0.5">No Photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Subjects Table Compact -->
                        <div class="relative z-10 my-1">
                            <table class="w-full text-left border-collapse border border-indigo-200 rounded overflow-hidden">
                                <thead class="school-color-bg text-white">
                                    <tr class="text-[8.5px] font-black uppercase tracking-wider">
                                        <th class="p-1.5 border-r border-white/20">Examination Subjects</th>
                                        <th class="p-1.5 border-r border-white/20 text-center w-24">Date</th>
                                        <th class="p-1.5 border-r border-white/20 text-center w-24">Day</th>
                                        <th class="p-1.5 border-r border-white/20 text-center w-24">Timing</th>
                                        <th class="p-1.5 text-center w-28">Invigilator</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-800 text-[9px] font-bold">
                                    <?php 
                                    $rowCount = max(4, count($subjects));
                                    for($i=0; $i < $rowCount; $i++): 
                                        $sub = htmlspecialchars($subjects[$i] ?? '');
                                        $date = isset($dates[$i]) && $dates[$i] ? date('d-m-Y', strtotime($dates[$i])) : '';
                                        $day = htmlspecialchars($days[$i] ?? '');
                                        $time = htmlspecialchars($times[$i] ?? '');
                                        $bg = ($i % 2 == 0) ? 'bg-white' : 'bg-slate-50/70';
                                    ?>
                                    <tr class="<?php echo $bg; ?> border-b border-slate-100">
                                        <td class="p-1 border-r border-slate-100 uppercase truncate max-w-[180px]"><?php echo $sub; ?></td>
                                        <td class="p-1 border-r border-slate-100 text-center"><?php echo $date; ?></td>
                                        <td class="p-1 border-r border-slate-100 text-center uppercase"><?php echo $day; ?></td>
                                        <td class="p-1 border-r border-slate-100 text-center uppercase whitespace-nowrap"><?php echo $time; ?></td>
                                        <td class="p-1"></td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Compact -->
                        <div class="flex justify-between items-center bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 relative z-10 text-[8px]">
                            <div class="font-bold text-slate-500 uppercase flex items-center gap-3">
                                <span><i class="fas fa-check-circle text-emerald-600"></i> Bring Slip Daily</span>
                                <span><i class="fas fa-clock text-indigo-600"></i> Reach 15 Mins Before</span>
                            </div>
                            <div class="text-center flex items-center gap-3">
                                <span class="font-black uppercase tracking-widest text-slate-600">Principal Sign:</span>
                                <div class="w-24 border-b-2 school-color-border"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cut Divider Line -->
                <?php if (isset($pair[1])): ?>
                <div class="cut-divider">
                    <div class="cut-line"></div>
                    <span class="cut-badge"><i class="fas fa-scissors"></i> Cut Here</span>
                    <div class="cut-line"></div>
                </div>

                <!-- Second Slip on same page -->
                <?php 
                $student = $pair[1];
                ?>
                <div class="half-slip-wrapper">
                    <div class="half-slip-border">
                        <!-- Watermark -->
                        <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark-compact">

                        <!-- Header -->
                        <div class="flex items-center gap-4 border-b-2 school-color-border pb-2 relative z-10">
                            <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo" class="w-12 h-12 object-contain">
                            <div class="flex-1 text-center">
                                <h2 class="text-xl font-black uppercase tracking-tight school-color-text leading-tight">
                                    <?php echo htmlspecialchars($settings['school_name']); ?>
                                </h2>
                                <p class="text-[8px] font-black uppercase tracking-[0.2em] text-slate-500">
                                    <?php echo htmlspecialchars($settings['address_tagline'] ?? 'Education for all'); ?>
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="school-color-bg text-white px-3 py-0.5 rounded text-[9px] font-black uppercase tracking-widest shadow-sm">
                                    Examination Slip
                                </span>
                                <span class="text-[9px] font-black text-slate-800 uppercase px-2 py-0.5 border border-indigo-200 rounded bg-indigo-50/50">
                                    <?php echo htmlspecialchars($examName); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Student Info & Photo Row -->
                        <div class="flex items-center gap-4 my-1.5 relative z-10">
                            <div class="flex-1 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">Name:</span>
                                    <span class="font-black uppercase text-slate-900 truncate border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        <?php echo htmlspecialchars($student['student_name']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">Father:</span>
                                    <span class="font-bold uppercase text-slate-800 truncate border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        <?php echo htmlspecialchars($student['father_name']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">Class:</span>
                                    <span class="font-black uppercase text-slate-900 border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        <?php echo htmlspecialchars($student['current_class']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[10px] school-color-text uppercase tracking-wider shrink-0">GR No:</span>
                                    <span class="font-black text-indigo-600 border-b border-slate-200 flex-1 pb-0.5 text-[11px]">
                                        #<?php echo htmlspecialchars($student['gr_no']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Student Photo Compact -->
                            <div class="w-16 h-18 border-2 school-color-border rounded-lg overflow-hidden bg-slate-50 relative flex-shrink-0 flex items-center justify-center">
                                <?php 
                                $img = !empty($student['profile_image']) && file_exists('../' . $student['profile_image']) 
                                       ? '../' . $student['profile_image'] 
                                       : null;
                                if($img): ?>
                                    <img src="<?php echo $img; ?>?v=<?php echo time(); ?>" class="w-full h-full object-cover object-top">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                                        <i class="fas fa-user-circle text-2xl"></i>
                                        <span class="text-[6px] font-black uppercase tracking-wider mt-0.5">No Photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Subjects Table Compact -->
                        <div class="relative z-10 my-1">
                            <table class="w-full text-left border-collapse border border-indigo-200 rounded overflow-hidden">
                                <thead class="school-color-bg text-white">
                                    <tr class="text-[8.5px] font-black uppercase tracking-wider">
                                        <th class="p-1.5 border-r border-white/20">Examination Subjects</th>
                                        <th class="p-1.5 border-r border-white/20 text-center w-24">Date</th>
                                        <th class="p-1.5 border-r border-white/20 text-center w-24">Day</th>
                                        <th class="p-1.5 border-r border-white/20 text-center w-24">Timing</th>
                                        <th class="p-1.5 text-center w-28">Invigilator</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-800 text-[9px] font-bold">
                                    <?php 
                                    $rowCount = max(4, count($subjects));
                                    for($i=0; $i < $rowCount; $i++): 
                                        $sub = htmlspecialchars($subjects[$i] ?? '');
                                        $date = isset($dates[$i]) && $dates[$i] ? date('d-m-Y', strtotime($dates[$i])) : '';
                                        $day = htmlspecialchars($days[$i] ?? '');
                                        $time = htmlspecialchars($times[$i] ?? '');
                                        $bg = ($i % 2 == 0) ? 'bg-white' : 'bg-slate-50/70';
                                    ?>
                                    <tr class="<?php echo $bg; ?> border-b border-slate-100">
                                        <td class="p-1 border-r border-slate-100 uppercase truncate max-w-[180px]"><?php echo $sub; ?></td>
                                        <td class="p-1 border-r border-slate-100 text-center"><?php echo $date; ?></td>
                                        <td class="p-1 border-r border-slate-100 text-center uppercase"><?php echo $day; ?></td>
                                        <td class="p-1 border-r border-slate-100 text-center uppercase whitespace-nowrap"><?php echo $time; ?></td>
                                        <td class="p-1"></td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Compact -->
                        <div class="flex justify-between items-center bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 relative z-10 text-[8px]">
                            <div class="font-bold text-slate-500 uppercase flex items-center gap-3">
                                <span><i class="fas fa-check-circle text-emerald-600"></i> Bring Slip Daily</span>
                                <span><i class="fas fa-clock text-indigo-600"></i> Reach 15 Mins Before</span>
                            </div>
                            <div class="text-center flex items-center gap-3">
                                <span class="font-black uppercase tracking-widest text-slate-600">Principal Sign:</span>
                                <div class="w-24 border-b-2 school-color-border"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty State -->
    <?php if (empty($students)): ?>
        <div class="bg-white rounded-3xl shadow-xl p-20 text-center max-w-2xl mx-auto border border-slate-100 mt-20">
            <i class="fas fa-users-slash text-6xl text-slate-200 mb-6"></i>
            <h2 class="text-3xl font-black text-slate-800 uppercase">No Students Found</h2>
            <p class="text-slate-500 font-bold mt-2">Class: <?php echo htmlspecialchars($class); ?></p>
        </div>
    <?php endif; ?>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';

            const element = document.getElementById('pdf-content');
            
            // Standard A4 settings for high compatibility
            const options = {
                margin: 0,
                filename: 'Exam_Slips_Class_<?php echo addslashes($class); ?>_<?php echo $slipsPerPage; ?>_per_page.pdf',
                image: { type: 'jpeg', quality: 1.0 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    logging: false, 
                    letterRendering: true,
                    backgroundColor: '#ffffff',
                    scrollY: 0,
                    windowWidth: 794 // Exact pixel width of 210mm at 96dpi
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            // Use html2pdf worker for better memory management
            html2pdf().set(options).from(element).toPdf().get('pdf').then(function (pdf) {
                // Rendered
            }).save().then(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }).catch(err => {
                console.error('PDF Error:', err);
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert('PDF Generation encountered an issue. Please use the "Print Now" button to save as PDF via your browser.');
            });
        });
    </script>
</body>
</html>
