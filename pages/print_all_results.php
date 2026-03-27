<?php
require_once '../includes/auth_session.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_GET['class']) || !isset($_GET['exam_type'])) {
    die("Invalid Request");
}

$database = new Database();
$settings = $database->getSchoolSettings();
$class = $_GET['class'];
$examType = $_GET['exam_type'];
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

if (isEditor()) {
    $assigned = getAssignedClasses();
    if (!in_array($class, $assigned)) {
        die("Unauthorized access to print results for this class.");
    }
}

// Fetch results and students
$results = $database->getResults($class, $examType, $year);
$resultStudentIds = array_keys($results);

$allStudents = $database->readData();
$students = array_filter($allStudents, function($student) use ($class, $resultStudentIds) {
    $isCurrent = ($student['current_class'] === $class && 
                 (!isset($student['student_status']) || $student['student_status'] !== 'Alumni'));
    $hasResult = in_array($student['id'], $resultStudentIds);
    return $isCurrent || $hasResult;
});

// Sort by GR No
usort($students, function($a, $b) {
    return (int)$a['gr_no'] - (int)$b['gr_no'];
});

if (empty($students)) {
    die("No students found for this class.");
}

// --- STATISTICS CALCULATION ---
$stats = [
    'total' => 0, 'passed' => 0, 'failed' => 0,
    'male_total' => 0, 'male_passed' => 0, 'male_failed' => 0,
    'female_total' => 0, 'female_passed' => 0, 'female_failed' => 0
];
$studentScores = [];

foreach ($students as $student) {
    $stats['total']++;
    $stId = $student['id'];
    $gender = isset($student['gender']) ? $student['gender'] : 'Other'; 
    $isPassed = false;
    $totalMarks = 0;

    if (isset($results[$stId])) {
        $res = $results[$stId];
        $totalMarks = (int)$res['total_obtained'];
        if ($res['grade'] !== 'F') $isPassed = true;
    }
    
    if ($gender === 'Male') {
        $stats['male_total']++;
        if ($isPassed) $stats['male_passed']++; else $stats['male_failed']++;
    } elseif ($gender === 'Female') {
        $stats['female_total']++;
        if ($isPassed) $stats['female_passed']++; else $stats['female_failed']++;
    }

    if ($isPassed) $stats['passed']++; else $stats['failed']++;

    if ($isPassed) {
        $studentScores[] = [
            'name' => $student['student_name'],
            'father_name' => $student['father_name'],
            'gender' => $gender,
            'marks' => $totalMarks,
            'percentage' => isset($results[$stId]) ? $results[$stId]['percentage'] : 0,
            'image' => $student['profile_image']
        ];
    }
}

usort($studentScores, function($a, $b) { return (int)$b['marks'] - (int)$a['marks']; });
$top3 = array_slice($studentScores, 0, 3);

$brandColor = "#0c0784";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Result Cards - <?= htmlspecialchars($class) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');
        
        body { 
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .brand-text { color: <?= $brandColor ?>; }
        .brand-bg { background-color: <?= $brandColor ?>; }
        .brand-border { border-color: <?= $brandColor ?>; }

        /* A4 Page Container */
        .print-page {
            width: 210mm;
            height: 296.5mm; /* Slightly less than 297mm to avoid blank page overflow */
            margin: 0 auto;
            background: white;
            box-sizing: border-box;
            position: relative;
            padding: 10mm;
            overflow: hidden;
            page-break-after: always;
        }

        .inner-border {
            border: 3px solid <?= $brandColor ?>;
            height: 100%;
            padding: 8mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.04;
            width: 140mm;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        table { border-collapse: collapse; width: 100%; }
        th { 
            background-color: <?= $brandColor ?>; 
            color: white;
            text-transform: uppercase;
            font-size: 11px;
            padding: 10px;
        }
        th, td { border: 1.2px solid <?= $brandColor ?>; padding: 8px 12px; }

        .field-label { font-weight: 700; color: #000; font-size: 13px; text-transform: uppercase; }
        .field-value { border-bottom: 2px solid #e2e8f0; font-weight: 600; font-size: 15px; margin-left: 8px; flex: 1; color: #000; }

        @media print {
            body { background: transparent; }
            .print-page { margin: 0; box-shadow: none; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <!-- Controls -->
    <div class="fixed top-4 right-4 flex gap-2 no-print z-50">
        <button id="downloadBtn" onclick="generateAllPDFs()" class="brand-bg hover:opacity-90 text-white font-bold py-2.5 px-6 rounded-lg shadow-xl flex items-center gap-2">
            <i class="fas fa-file-pdf"></i> Download PDF
        </button>
        <button onclick="window.print()" class="bg-gray-800 text-white font-bold py-2.5 px-6 rounded-lg">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div id="bulkPrintContent">
        <!-- 1. SUMMARY PAGE -->
        <div class="print-page">
            <div class="inner-border">
                <div class="content">
                    <div class="text-center mb-6">
                        <h1 class="text-3xl font-black brand-text uppercase px-2"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                        <p class="text-[13px] font-bold text-slate-600 uppercase mt-1"><?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                        <div class="mt-4 border-y-2 border-slate-100 py-3">
                            <h2 class="text-xl font-extrabold text-black uppercase tracking-widest">RESULT SUMMARY - CLASS <?= htmlspecialchars($class) ?></h2>
                            <p class="text-xs font-bold text-slate-500 mt-1 uppercase"><?= $examType ?> Examination | Session <?= $year ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-sm font-black brand-text border-b-2 brand-border mb-3 pb-1 uppercase tracking-wider">Class Statistics</h3>
                            <table class="w-full text-sm">
                                <tr><td class="bg-slate-50 font-bold text-black text-xs">Total Students</td><td class="text-center font-black text-black"><?= $stats['total'] ?></td></tr>
                                <tr><td class="bg-white font-bold text-emerald-600 text-xs">Passed</td><td class="text-center font-black text-emerald-600"><?= $stats['passed'] ?></td></tr>
                                <tr><td class="bg-slate-50 font-bold text-red-600 text-xs">Failed</td><td class="text-center font-black text-red-600"><?= $stats['failed'] ?></td></tr>
                                <tr><td class="bg-white font-bold text-black text-xs">Pass Percentage</td><td class="text-center font-black text-black"><?= $stats['total'] > 0 ? round(($stats['passed']/$stats['total'])*100, 1) : 0 ?>%</td></tr>
                            </table>

                            <h3 class="text-sm font-black brand-text border-b-2 brand-border mt-8 mb-3 pb-1 uppercase tracking-wider text-xs">Gender Breakdown</h3>
                            <table class="w-full text-sm">
                                <tr class="bg-slate-100 font-bold text-[9px] uppercase text-black">
                                    <td>Gender</td><td class="text-center">Total</td><td class="text-center">Pass</td><td class="text-center">Fail</td>
                                </tr>
                                <tr>
                                    <td class="font-bold text-black text-xs">Boys</td>
                                    <td class="text-center text-black font-bold"><?= $stats['male_total'] ?></td>
                                    <td class="text-center font-bold text-emerald-600"><?= $stats['male_passed'] ?></td>
                                    <td class="text-center font-bold text-red-600"><?= $stats['male_failed'] ?></td>
                                </tr>
                                <tr>
                                    <td class="font-bold text-black text-xs">Girls</td>
                                    <td class="text-center text-black font-bold"><?= $stats['female_total'] ?></td>
                                    <td class="text-center font-bold text-emerald-600"><?= $stats['female_passed'] ?></td>
                                    <td class="text-center font-bold text-red-600"><?= $stats['female_failed'] ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="flex flex-col items-center justify-center bg-slate-50 rounded-xl p-4 border border-slate-100">
                            <div class="h-48 w-full relative">
                                <canvas id="printSummaryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex-1">
                        <h3 class="text-sm font-black brand-text border-b-2 brand-border mb-4 pb-1 uppercase tracking-wider">🏆 Top 3 Position Holders</h3>
                        <div class="space-y-3">
                            <?php foreach ($top3 as $index => $winner): 
                                $medal = match($index) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
                            ?>
                            <div class="flex items-center bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                <div class="text-3xl mr-4"><?= $medal ?></div>
                                <div class="h-14 w-14 rounded-full overflow-hidden border-2 brand-border mr-4 bg-slate-100 p-0.5">
                                    <?php 
                                        $imagePath = $winner['image'];
                                        if ($imagePath && file_exists('../' . $imagePath)): 
                                    ?>
                                        <img src="<?= '../' . htmlspecialchars($imagePath) ?>" class="w-full h-full object-cover rounded-full">
                                    <?php else: ?>
                                        <div class="h-full w-full flex items-center justify-center text-slate-300"><i class="fas fa-user text-2xl"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="font-black text-black text-lg uppercase"><?= htmlspecialchars($winner['name']) ?></div>
                                    <div class="text-[10px] text-slate-500 font-bold uppercase">MARKS: <strong><?= $winner['marks'] ?></strong> | PERCENTAGE: <strong><?= $winner['percentage'] ?>%</strong></div>
                                </div>
                                <div class="text-xl font-black brand-text italic uppercase">
                                    <?= ($index + 1) ?><sup><?= match($index) { 0=>'st', 1=>'nd', 2=>'rd', default=>'th' } ?></sup> POS
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-end">
                        <div class="text-[10px] font-bold text-slate-400">PRINTED ON: <?php echo date('d-M-Y h:i A'); ?></div>
                        <div class="text-center">
                            <div class="w-56 border-b-2 border-slate-900 mb-2"></div>
                            <p class="text-xs font-black brand-text uppercase">PRINCIPAL SIGNATURE</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. STUDENT MARKSHEETS -->
        <?php foreach ($students as $student): 
            $studentId = $student['id'];
            $result = isset($results[$studentId]) ? $results[$studentId] : null;
            if (!$result) {
                $result = [
                    'english' => 0, 'math' => 0, 'social_studies' => 0, 'general_science' => 0, 
                    'mt' => 0, 'islamiyat' => 0, 'nmt' => 0,
                    'total_max' => 700, 'total_obtained' => 0, 'percentage' => 0, 'grade' => '-',
                    'other_subjects' => '{}' 
                ];
            }
            $logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
                        ? '../' . $settings['school_logo'] 
                        : '../assets/branding/logo.png';
        ?>
        <div class="print-page">
            <div class="inner-border">
                <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark">
                <div class="content">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-slate-100">
                        <!-- Left: School Logo -->
                        <div class="w-24 h-24 flex-shrink-0">
                            <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <!-- Center: School Details -->
                        <div class="flex-1 text-center px-4">
                            <h1 class="text-2xl font-black brand-text uppercase leading-tight"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                            <p class="text-[13px] font-bold text-slate-600 uppercase mt-1">
                                <?php echo htmlspecialchars($settings['address_tagline']); ?>
                            </p>
                            <div class="mt-4">
                                <h2 class="text-lg font-extrabold text-black underline decoration-2 decoration-indigo-300 uppercase tracking-widest">
                                    <?= $examType ?> EXAMINATION - SESSION <?= $year ?>
                                </h2>
                            </div>
                        </div>
                        <!-- Right: Student Photo -->
                        <?php 
                            $imagePath = $student['profile_image'];
                            if (!$imagePath || !file_exists('../' . $imagePath)) {
                                $imagePath = 'https://ui-avatars.com/api/?name=' . urlencode($student['student_name']) . '&background=0c0784&color=fff&size=200&bold=true';
                            } else {
                                $imagePath = '../' . $imagePath;
                            }
                        ?>
                        <div class="w-24 h-28 border-2 brand-border bg-white flex-shrink-0 rounded-lg overflow-hidden p-1 shadow-sm">
                            <img src="<?= htmlspecialchars($imagePath) ?>" class="w-full h-full object-cover object-top rounded-md">
                        </div>
                    </div>

                    <!-- Identifiers -->
                    <div class="grid grid-cols-2 gap-x-10 gap-y-4 mb-8">
                        <div class="flex items-end">
                            <span class="field-label">Student:</span>
                            <span class="field-value uppercase text-black"><?= htmlspecialchars($student['student_name']) ?></span>
                        </div>
                        <div class="flex items-end">
                            <span class="field-label">Father Name:</span>
                            <span class="field-value uppercase text-black"><?= htmlspecialchars($student['father_name']) ?></span>
                        </div>
                        <div class="flex items-end">
                            <span class="field-label">Class:</span>
                            <span class="field-value uppercase text-black">
                                <?= htmlspecialchars(!empty($result['class']) ? $result['class'] : $student['current_class']) ?>
                            </span>
                        </div>
                        <div class="flex items-end">
                            <span class="field-label">G.R. No:</span>
                            <span class="field-value font-black text-black"><?= htmlspecialchars($student['gr_no']) ?></span>
                        </div>
                    </div>

                    <!-- Result Table -->
                    <div class="mb-8 border-2 brand-border">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="text-left py-3">Subject Name</th>
                                    <th class="text-center w-28">Max Marks</th>
                                    <th class="text-center w-28">Obtained</th>
                                    <th class="text-left">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php
                                $subjects = [
                                    'English' => 'english', 'Mathematics' => 'math',
                                    'Social Studies' => 'social_studies', 'General Science' => 'general_science',
                                    'Mother Tongue (MT)' => 'mt', 'Islamiyat' => 'islamiyat', 'N.M.T' => 'nmt'
                                ];
                                $rowCount = 0;
                                foreach ($subjects as $label => $key):
                                    $marks = $result[$key];
                                    $passed = ((float)$marks >= 33);
                                    $rowBg = ($rowCount++ % 2 == 0) ? 'bg-white' : 'bg-slate-50';
                                ?>
                                <tr class="<?= $rowBg ?> border-b">
                                    <td class="font-bold py-3 px-4 text-black"><?= $label ?></td>
                                    <td class="text-center text-slate-500">100</td>
                                    <td class="text-center font-black text-black <?= $passed ? '' : 'text-red-500' ?>"><?= $marks ?></td>
                                    <td class="font-bold italic text-xs <?= $passed ? 'text-emerald-600' : 'text-red-500' ?>"><?= $passed ? 'PASS' : 'FAIL' ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <?php 
                                $extra = isset($result['other_subjects']) ? json_decode($result['other_subjects'], true) : [];
                                if (is_array($extra)) {
                                    foreach ($extra as $subject => $mark) {
                                        $passed = ((float)$mark >= 33);
                                        $rowBg = ($rowCount++ % 2 == 0) ? 'bg-white' : 'bg-slate-50';
                                        ?>
                                        <tr class="<?= $rowBg ?> border-b">
                                            <td class="font-bold py-3 px-4 text-black"><?= ucfirst($subject) ?></td>
                                            <td class="text-center text-slate-500">100</td>
                                            <td class="text-center font-black text-black <?= $passed ? '' : 'text-red-500' ?>"><?= $mark ?></td>
                                            <td class="font-bold italic text-xs <?= $passed ? 'text-emerald-600' : 'text-red-500' ?>"><?= $passed ? 'PASS' : 'FAIL' ?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>

                                <tr class="brand-bg text-white font-bold h-14">
                                    <td class="text-right px-6 uppercase tracking-widest text-xs">Total Marks Obtained</td>
                                    <td class="text-center border-l border-white/20"><?= $result['total_max'] ?></td>
                                    <td class="text-center text-xl border-l border-white/20"><?= $result['total_obtained'] ?></td>
                                    <td class="px-4 border-l border-white/20">
                                         <div class="flex items-center justify-between">
                                            <span><?= $result['percentage'] ?>%</span>
                                            <span class="bg-white/20 px-3 py-1 rounded text-xs uppercase">Grade: <?= $result['grade'] ?></span>
                                         </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Signatures -->
                    <div class="mt-auto flex justify-between items-end pb-4">
                        <div class="text-center">
                            <div class="w-56 border-b-2 border-slate-900 mb-2 h-10"></div>
                            <p class="text-xs font-bold text-black uppercase">Class Teacher Signature</p>
                        </div>
                        <div class="text-center">
                            <div class="w-56 border-b-2 border-slate-900 mb-2 h-10"></div>
                            <p class="text-xs font-bold brand-text uppercase">PRINCIPAL SIGNATURE</p>
                        </div>
                    </div>

                    <!-- Footer Tagline -->
                    <div class="text-center border-t border-slate-100 mt-6 pt-3">
                        <p class="text-[9px] uppercase tracking-[0.4em] text-slate-300 font-bold">AQSA School Management System</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Chart JS and PDF Logic -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        const stats = <?= json_encode($stats) ?>;
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('printSummaryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [stats.passed, stats.failed],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    animation: false,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Outfit', weight: 'bold', size: 10 } } } },
                    cutout: '70%'
                }
            });
        });

        function generateAllPDFs() {
            // Reset scroll to top to ensure html2canvas starts from the beginning
            window.scrollTo(0, 0);
            
            const element = document.getElementById('bulkPrintContent');
            const btn = document.getElementById('downloadBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing Bulk PDF...';
            btn.disabled = true;

            // Small delay to allow any scroll adjustments or re-renders to settle
            setTimeout(() => {
                const opt = {
                    margin: 0,
                    filename: 'Bulk_Results_Class_<?= $class ?>_<?= $year ?>.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { 
                        scale: 2, // Reduced from 3 to prevent canvas memory issues on bulk
                        useCORS: true, 
                        letterRendering: true,
                        scrollY: 0,
                        windowWidth: element.scrollWidth,
                        backgroundColor: '#ffffff'
                    },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                    pagebreak: { mode: 'css' }
                };

                html2pdf().set(opt).from(element).save().then(() => {
                    btn.innerHTML = '<i class="fas fa-file-pdf"></i> Download PDF';
                    btn.disabled = false;
                }).catch(err => {
                    console.error(err);
                    btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    btn.disabled = false;
                });
            }, 500);
        }
    </script>
</body>
</html>
