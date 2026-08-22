<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$class = isset($_GET['class']) ? trim($_GET['class']) : '';
$examName = isset($_GET['exam_name']) ? trim($_GET['exam_name']) : '';
$subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
$maxMarks = isset($_GET['max_marks']) ? trim($_GET['max_marks']) : '100';
$startingSeatNo = isset($_GET['starting_seat_no']) && is_numeric($_GET['starting_seat_no']) ? intval($_GET['starting_seat_no']) : null;

if (!$class) {
    die("Class is required to generate the Award List.");
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
    <title>Award List - Class <?= htmlspecialchars($class) ?> - <?= htmlspecialchars($subject) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 10mm 10mm;
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11pt;
                color: #000 !important;
            }

            .no-print {
                display: none !important;
            }

            .award-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 auto !important;
                width: 100% !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f3f4f6;
            color: #000;
        }

        .award-sheet {
            width: 210mm;
            min-height: 297mm;
            padding: 12mm 15mm;
            margin: 20px auto;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            box-sizing: border-box;
        }

        .award-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }

        .award-table th,
        .award-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            font-size: 11px;
            color: #000;
        }

        .award-table th {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #fff;
            padding: 6px 8px;
        }

        .award-table td {
            height: 28px;
            vertical-align: middle;
        }

        .award-table tr:nth-child(even) {
            background-color: #fff;
        }
    </style>
</head>

<body class="p-4 md:p-8 min-h-screen">

    <!-- Control Bar (Hidden on Print) -->
    <div
        class="max-w-[210mm] mx-auto mb-6 no-print flex flex-wrap justify-between items-center bg-white p-4 rounded-2xl shadow-md border border-gray-200 gap-3">
        <div class="flex items-center gap-3">
            <a href="award_list.php"
                class="p-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" title="Back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-black text-gray-800 uppercase tracking-tight">Award List Sheet</h1>
                <p class="text-xs text-gray-500 font-medium">Class: <?= htmlspecialchars($class) ?> | Subject:
                    <?= htmlspecialchars($subject) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()"
                class="bg-slate-900 hover:bg-black text-white px-8 py-3 rounded-xl font-bold flex items-center gap-2 text-xs uppercase tracking-widest transition-all shadow-md active:scale-95">
                <i class="fas fa-print"></i>
                Print Now
            </button>
        </div>
    </div>

    <!-- Award List Printable Container -->
    <div id="award-sheet" class="award-sheet">
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
                    <h2 class="text-lg font-black uppercase tracking-wide underline decoration-2 underline-offset-2">
                        AWARD LIST
                    </h2>
                </div>
                <?php if (!empty($examName)): ?>
                    <div class="mt-0.5">
                        <p class="text-xs md:text-sm font-bold uppercase tracking-wide text-black">
                            <?= htmlspecialchars($examName) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Meta Information Row -->
        <div class="flex justify-between items-center text-xs font-black uppercase tracking-wider mb-2 mt-4 pb-1">
            <div>
                CLASS: <span class="font-normal font-bold"><?= htmlspecialchars($class) ?></span>
            </div>
            <div>
                SUBJECT: <span class="font-normal font-bold"><?= htmlspecialchars($subject) ?></span>
            </div>
            <div>
                MAX. MARKS: <span class="font-normal font-bold"><?= htmlspecialchars($maxMarks) ?></span>
            </div>
            <?php if (!empty($date)): ?>
                <div>
                    DATE: <span class="font-normal font-bold"><?= date('d/m/Y', strtotime($date)) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Award List Table -->
        <table class="award-table">
            <thead>
                <tr>
                    <th style="width: 7%; text-align: center;">S.NO</th>
                    <th style="width: 12%; text-align: center;">SEAT#</th>
                    <th style="width: 41%; text-align: left; padding-left: 10px;">NAME</th>
                    <th style="width: 25%; text-align: center;">SIGNATURE</th>
                    <th style="width: 15%; text-align: center;">MARKS OBT:</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 25px 10px; color: #666; font-style: italic;">
                            No active students found in Class <?= htmlspecialchars($class) ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $counter = 1;
                    foreach ($students as $student):
                        $sNoFormatted = str_pad($counter, 2, '0', STR_PAD_LEFT);

                        // Seat Number Calculation
                        if ($startingSeatNo !== null) {
                            $seatNoDisplay = $startingSeatNo + ($counter - 1);
                        } else {
                            $seatNoDisplay = !empty($student['gr_no']) ? $student['gr_no'] : $counter;
                        }
                        ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $sNoFormatted ?></td>
                            <td style="text-align: center; font-weight: 800;"><?= htmlspecialchars($seatNoDisplay) ?></td>
                            <td style="text-align: left; padding-left: 10px; font-weight: 600;">
                                <?= htmlspecialchars($student['student_name'] ?? '') ?>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
                        $counter++;
                    endforeach;
                    ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Footer / Signatures Section -->
        <div class="mt-8 pt-4 flex justify-between items-end text-xs font-bold uppercase tracking-wider text-black">
            <div>
                <p>Total Students: <span class="font-normal font-bold"><?= count($students) ?></span></p>
            </div>
            <div class="text-center">
                <div class="w-44 border-b border-black mb-1"></div>
                <p>Subject Teacher</p>
            </div>
            <div class="text-center">
                <div class="w-44 border-b border-black mb-1"></div>
                <p>Principal / Incharge</p>
            </div>
        </div>
    </div>
</body>
</html>