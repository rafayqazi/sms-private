<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$class = isset($_GET['class']) ? trim($_GET['class']) : '';
$examName = isset($_GET['exam_name']) ? trim($_GET['exam_name']) : '';
$subjects = isset($_GET['subjects']) && is_array($_GET['subjects']) ? $_GET['subjects'] : [];
$maxMarks = isset($_GET['max_marks']) && is_array($_GET['max_marks']) ? $_GET['max_marks'] : [];
$startingSeatNo = isset($_GET['starting_seat_no']) && is_numeric($_GET['starting_seat_no']) ? intval($_GET['starting_seat_no']) : null;

if (!$class) {
    die("Class is required to generate the Marking Award List.");
}

// Clean up subjects and map them with max marks
$subjectList = [];
for ($i = 0; $i < count($subjects); $i++) {
    $subjName = trim($subjects[$i] ?? '');
    if ($subjName !== '') {
        $marksVal = isset($maxMarks[$i]) && is_numeric($maxMarks[$i]) ? intval($maxMarks[$i]) : 100;
        $subjectList[] = [
            'name' => strtoupper($subjName),
            'max_marks' => $marksVal
        ];
    }
}

if (empty($subjectList)) {
    $subjectList[] = ['name' => 'ALL SUBJECTS', 'max_marks' => 100];
}

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();

// Filter students for the selected class
$students = array_filter($allStudents, function ($student) use ($class) {
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    return !$isAlumni && isset($student['current_class']) && $student['current_class'] == $class;
});

// Sort students alphabetically by Name (A to Z)
usort($students, function ($a, $b) {
    return strcasecmp(trim($a['student_name'] ?? ''), trim($b['student_name'] ?? ''));
});

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
    ? '../' . $settings['school_logo'] 
    : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marking Award List - Class <?= htmlspecialchars($class) ?> - <?= htmlspecialchars($examName) ?></title>
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
            color: #000;
        }

        @page {
            size: A4 landscape;
            margin: 10mm 10mm 10mm 10mm;
        }

        @media print {
            body { 
                background: white !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                color: #000 !important;
            }
            .no-print { 
                display: none !important; 
            }
            #pdf-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .page-container {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: auto !important;
                page-break-inside: auto !important;
            }
            .marking-table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1.5px solid #000 !important;
            }
            .marking-table th {
                border-top: 1.5px solid #000 !important;
                border-bottom: 1.5px solid #000 !important;
                border-left: 1px solid #000 !important;
                border-right: 1px solid #000 !important;
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .marking-table td {
                border: 1px solid #000 !important;
            }
            thead {
                display: table-header-group !important;
            }
            tfoot {
                display: table-footer-group !important;
            }
            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }
            .signatures-block {
                page-break-inside: avoid !important;
                margin-top: 20px !important;
            }
        }

        .page-container {
            width: 287mm; /* A4 Landscape printable width */
            min-height: 195mm;
            background: white;
            margin: 0 auto 40px;
            padding: 10mm 12mm;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            box-sizing: border-box;
        }

        .marking-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }

        .marking-table th, .marking-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10.5px;
            color: #000;
        }

        .marking-table th {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            background-color: #e2e8f0;
            text-align: center;
            vertical-align: middle;
        }

        .marking-table td {
            height: 26px;
            vertical-align: middle;
        }

        .marking-table tr:nth-child(even) {
            background-color: #fff;
        }
    </style>
</head>
<body>

    <!-- UI Header Bar (Matching print_slips.php) -->
    <div class="max-w-[287mm] mx-auto mb-8 no-print flex flex-wrap justify-between items-center bg-white p-6 rounded-3xl shadow-xl border border-slate-100 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Marking Award List</h1>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-100 text-purple-700">
                    Class: <?= htmlspecialchars($class) ?>
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                    <?= count($subjectList) ?> Subjects Configured
                </span>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1"><?= htmlspecialchars($examName) ?> | Total Students: <?= count($students) ?></p>
        </div>
        <div class="flex gap-4">
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-xl shadow-lg shadow-slate-200/50 hover:bg-black font-black flex items-center gap-2 transition-all active:scale-95 uppercase tracking-widest text-xs">
                <i class="fas fa-print"></i> Print Now
            </button>
        </div>
    </div>

    <!-- Printable Content Container -->
    <div id="pdf-content">
        <div class="page-container">
            <!-- Header Section -->
            <div class="relative mb-4 min-h-[115px] flex items-center">
                <!-- Left Logo -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2">
                    <img src="<?= htmlspecialchars($logoPath) ?>?v=<?= time() ?>" alt="School Logo"
                        class="w-28 h-28 object-contain" style="width: 110px !important; height: 110px !important; max-width: none !important;">
                </div>

                <!-- Centered Titles -->
                <div class="w-full text-center" style="padding-left: 125px; padding-right: 125px;">
                    <h1 class="text-xl md:text-2xl font-black uppercase tracking-tight text-black leading-tight">
                        <?= htmlspecialchars($settings['school_name'] ?? 'SCHOOL NAME') ?>
                    </h1>
                    <div class="mt-1">
                        <h2 class="text-base md:text-lg font-black uppercase tracking-wide underline decoration-2 underline-offset-2">
                            MARKING AWARD LIST
                        </h2>
                    </div>
                    <?php if (!empty($examName)): ?>
                    <div class="mt-0.5">
                        <p class="text-xs md:text-sm font-bold uppercase tracking-wider text-black">
                            <?= htmlspecialchars($examName) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Marking Award List Table (Matching the user's photo structure) -->
            <table class="marking-table flex-1">
                <thead>
                    <!-- Row 1: S.NO, SEAT NO, NAME (rowspan 2) and Merged Class Header across all subjects + Total -->
                    <tr>
                        <th rowspan="2" style="width: 4.5%; text-align: center;">S.NO</th>
                        <th rowspan="2" style="width: 7.5%; text-align: center;">SEAT NO.</th>
                        <th rowspan="2" style="width: 21%; text-align: left; padding-left: 8px;">NAME</th>
                        <th colspan="<?= count($subjectList) + 1 ?>" style="text-align: center; font-size: 12px; font-weight: 900; background-color: #e2e8f0; letter-spacing: 1px;">
                            CLASS: <?= htmlspecialchars(strtoupper($class)) ?>
                        </th>
                    </tr>
                    <!-- Row 2: Dynamic Subject Column Headers with Max Marks and Total Marks Column -->
                    <tr>
                        <?php 
                        $grandTotalMaxMarks = 0;
                        foreach ($subjectList as $subj): 
                            $grandTotalMaxMarks += intval($subj['max_marks']);
                        ?>
                        <th style="text-align: center; font-size: 9.5px; padding: 4px 2px;">
                            <?= htmlspecialchars($subj['name']) ?> (<?= htmlspecialchars($subj['max_marks']) ?>)
                        </th>
                        <?php endforeach; ?>
                        <th style="text-align: center; font-size: 9.5px; font-weight: 900; background-color: #cbd5e1; padding: 4px 2px;">
                            TOTAL (<?= $grandTotalMaxMarks ?>)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="<?= 4 + count($subjectList) ?>" style="text-align: center; padding: 30px 10px; color: #666; font-style: italic;">
                            No active students found in Class <?= htmlspecialchars($class) ?>.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $counter = 1;
                        foreach ($students as $student): 
                            // Seat Number Calculation
                            if ($startingSeatNo !== null) {
                                $seatNoDisplay = $startingSeatNo + ($counter - 1);
                            } else {
                                $seatNoDisplay = !empty($student['gr_no']) ? $student['gr_no'] : $counter;
                            }
                        ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $counter ?></td>
                            <td style="text-align: center; font-weight: 800;"><?= htmlspecialchars($seatNoDisplay) ?></td>
                            <td style="text-align: left; padding-left: 8px; font-weight: 700; text-transform: uppercase; font-size: 10px;">
                                <?= htmlspecialchars($student['student_name'] ?? '') ?>
                            </td>
                            <!-- Blank marks cells for each configured subject -->
                            <?php foreach ($subjectList as $subj): ?>
                            <td></td>
                            <?php endforeach; ?>
                            <!-- Total Marks Column Cell -->
                            <td style="background-color: #f8fafc;"></td>
                        </tr>
                        <?php 
                            $counter++;
                        endforeach; 
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Signatures Section -->
            <div class="signatures-block mt-6 pt-3 flex justify-between items-end text-xs font-bold uppercase tracking-wider text-black">
                <div>
                    <p>Total Students: <span class="font-normal font-bold"><?= count($students) ?></span></p>
                </div>
                <div class="text-center">
                    <div class="w-48 border-b border-black mb-1"></div>
                    <p>Subject Teacher(s)</p>
                </div>
                <div class="text-center">
                    <div class="w-48 border-b border-black mb-1"></div>
                    <p>Principal / Incharge</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
