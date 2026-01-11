<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

if (!isset($_GET['class']) || !isset($_GET['exam_type'])) {
    die("Invalid Request");
}

$database = new Database();
$settings = $database->getSchoolSettings();
$class = $_GET['class'];
$examType = $_GET['exam_type'];
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// reusing logic from view_results.php to get students
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Cards - Class <?= htmlspecialchars($class) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Precision A4 Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 0 !important;
            }
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white;
                -webkit-print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            
            /* The core "A4 Page" container */
            .print-page {
                width: 210mm;
                height: 297mm;
                page-break-after: always;
                display: flex !important;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                overflow: hidden;
                position: relative;
                box-sizing: border-box;
            }
        }

        /* Screen Preview Styles */
        body { 
            font-family: 'Times New Roman', Times, serif; 
            background-color: #f3f4f6;
        }
        
        .print-page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .marksheet-card {
            width: 190mm;
            border: 2px solid #1f2937;
            padding: 8mm;
            position: relative;
            background: white;
            box-sizing: border-box;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 120mm;
            pointer-events: none;
            z-index: 0;
        }

        .content-relative {
            position: relative;
            z-index: 10;
        }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #1f2937; padding: 6px 10px; }
        .border-dotted { border-bottom: 2px dotted #1f2937; }
    </style>
</head>
<body class="bg-white p-8">
    <div class="fixed top-4 right-4 no-print z-50">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Report
        </button>
    </div>
    
    <?php 
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
        $gender = $student['gender']; 
        $isPassed = false;
        $totalMarks = 0;

        if (isset($results[$stId])) {
            $res = $results[$stId];
            $totalMarks = $res['total_obtained'];
            if ($res['grade'] !== 'F') $isPassed = true;
        }
        
        if ($gender === 'Male') {
            $stats['male_total']++;
            if ($isPassed) $stats['male_passed']++; else $stats['male_failed']++;
        } elseif ($gender === 'Female') {
            $stats['female_total']++;
            if ($isPassed) $stats['female_passed']++; else $stats['female_failed']++;
        } else {
            if ($isPassed) $stats['passed']++;
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

    usort($studentScores, function($a, $b) { return $b['marks'] - $a['marks']; });
    $top3 = array_slice($studentScores, 0, 3);
    ?>

    <!-- SUMMARY PAGE -->
    <div class="print-page">
        <div class="marksheet-card">
         <!-- Header -->
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-6">
            <div class="flex items-center justify-center gap-4 mb-2">
                <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="Logo" class="h-20 w-20 object-contain">
                <div>
                    <h1 class="text-2xl font-bold uppercase"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                    <p class="text-sm font-bold uppercase"><?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                </div>
            </div>
            <h2 class="text-xl font-bold uppercase underline decoration-2 mt-4">Result Summary Report</h2>
            <p class="text-base font-semibold mt-1">
                Class: <span class="uppercase"><?php echo htmlspecialchars($class); ?></span> | 
                Exam: <span class="uppercase"><?php echo htmlspecialchars($examType); ?></span> | 
                Session: <?php echo $year . '-' . ($year+1); ?>
            </p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8">
            <!-- Stats Table -->
            <div>
                <h3 class="text-lg font-bold border-b border-gray-400 mb-2">Class Statistics</h3>
                <table class="w-full text-sm border-collapse border border-gray-300">
                    <tr class="bg-gray-100"><th class="border border-gray-300 px-2 py-1 text-left">Metric</th><th class="border border-gray-300 px-2 py-1 text-center">Count</th></tr>
                    <tr><td class="border border-gray-300 px-2 py-1">Total Students</td><td class="border border-gray-300 px-2 py-1 text-center font-bold"><?= $stats['total'] ?></td></tr>
                    <tr><td class="border border-gray-300 px-2 py-1 text-green-700 font-semibold">Passed</td><td class="border border-gray-300 px-2 py-1 text-center font-bold text-green-700"><?= $stats['passed'] ?></td></tr>
                    <tr><td class="border border-gray-300 px-2 py-1 text-red-700 font-semibold">Failed</td><td class="border border-gray-300 px-2 py-1 text-center font-bold text-red-700"><?= $stats['failed'] ?></td></tr>
                    <tr><td class="border border-gray-300 px-2 py-1">Pass Percentage</td><td class="border border-gray-300 px-2 py-1 text-center font-bold"><?= $stats['total'] > 0 ? round(($stats['passed']/$stats['total'])*100, 1) : 0 ?>%</td></tr>
                </table>

                <h3 class="text-lg font-bold border-b border-gray-400 mb-2 mt-6">Gender-wise Breakdown</h3>
                <table class="w-full text-sm border-collapse border border-gray-300">
                    <tr class="bg-gray-100"><th class="border border-gray-300 px-2 py-1 text-left">Gender</th><th class="border border-gray-300 px-2 py-1 text-center">Total</th><th class="border border-gray-300 px-2 py-1 text-center">Pass</th><th class="border border-gray-300 px-2 py-1 text-center">Fail</th></tr>
                    <tr>
                        <td class="border border-gray-300 px-2 py-1">Boys</td>
                        <td class="border border-gray-300 px-2 py-1 text-center"><?= $stats['male_total'] ?></td>
                        <td class="border border-gray-300 px-2 py-1 text-center text-green-700"><?= $stats['male_passed'] ?></td>
                        <td class="border border-gray-300 px-2 py-1 text-center text-red-700"><?= $stats['male_failed'] ?></td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-2 py-1">Girls</td>
                        <td class="border border-gray-300 px-2 py-1 text-center"><?= $stats['female_total'] ?></td>
                        <td class="border border-gray-300 px-2 py-1 text-center text-green-700"><?= $stats['female_passed'] ?></td>
                        <td class="border border-gray-300 px-2 py-1 text-center text-red-700"><?= $stats['female_failed'] ?></td>
                    </tr>
                </table>
            </div>

            <!-- Charts -->
            <div class="flex flex-col items-center justify-center">
                <div class="h-48 w-full relative mb-4">
                    <canvas id="printSummaryChart"></canvas>
                </div>
                <div class="text-center text-xs font-bold text-gray-500">Pass vs Fail Ratio</div>
            </div>
        </div>

        <!-- Top Position Holders -->
        <div class="mb-4">
            <h3 class="text-lg font-bold border-b border-gray-400 mb-4 bg-gray-100 p-2">🏆 Top 3 Position Holders</h3>
            <div class="space-y-2">
                 <?php foreach ($top3 as $index => $winner): 
                    $medal = match($index) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
                ?>
                <div class="flex items-center border border-gray-200 p-3 rounded shadow-sm">
                    <div class="text-2xl mr-4"><?= $medal ?></div>
                    <div class="h-12 w-12 rounded-full overflow-hidden border border-gray-300 mr-4 bg-gray-100">
                        <?php 
                            $imagePath = $winner['image'];
                            if ($imagePath && file_exists($imagePath)): 
                        ?>
                            <img src="<?= htmlspecialchars($imagePath) ?>" class="w-full h-full object-cover object-top">
                        <?php else: ?>
                            <div class="h-full w-full flex items-center justify-center text-gray-400"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-base"><?= htmlspecialchars($winner['name']) ?> <span class="text-xs font-normal text-gray-500">(s/o <?= htmlspecialchars($winner['father_name']) ?>)</span></div>
                        <div class="text-sm text-gray-600">Marks: <strong><?= $winner['marks'] ?></strong> | Percentage: <strong><?= $winner['percentage'] ?>%</strong></div>
                    </div>
                    <div class="text-xl font-bold text-indigo-700">
                        <?= ($index + 1) ?><sup><?= match($index) { 0=>'st', 1=>'nd', 2=>'rd', default=>'th' } ?></sup> Pos.
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($top3)): ?><p class="text-center text-gray-500 italic">No results available yet.</p><?php endif; ?>
            </div>
        </div>

            <div class="mt-8 pt-4 border-t border-gray-400 flex justify-between text-xs font-bold">
                <div>Printed on: <?php echo date('d-M-Y h:i A'); ?></div>
                <div>Principal Signature: _______________________</div>
            </div>
        </div>
    </div>

    <!-- Chart JS Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const stats = <?= json_encode($stats) ?>;
        // Wait for fonts/images?
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('printSummaryChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [stats.passed, stats.failed],
                        backgroundColor: ['#16a34a', '#dc2626'], // Green-600, Red-600
                        borderWidth: 1
                    }]
                },
                options: {
                    animation: false, // Critical for printing immediately
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });

            // Delay print slightly to ensure canvas render
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
    
    <!-- STUDENT MARKSHEETS -->
    <?php foreach ($students as $student): 
        $studentId = $student['id'];
        $result = isset($results[$studentId]) ? $results[$studentId] : null;
        
        // If result not found, initialize empty structure
        if (!$result) {
            $result = [
                'english' => 0, 'math' => 0, 'social_studies' => 0, 'general_science' => 0, 
                'mt' => 0, 'islamiyat' => 0, 'nmt' => 0,
                'total_max' => 700, 'total_obtained' => 0, 'percentage' => 0, 'grade' => '-',
                'other_subjects' => '{}' 
            ];
        }
    ?>
    
    <div class="print-page">
        <!-- Watermark -->
        <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" class="watermark">
        
        <div class="marksheet-card content-relative">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="w-24 h-24 flex-shrink-0">
                <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex-1 text-center px-4">
                <h1 class="text-2xl font-bold text-gray-900 uppercase leading-tight"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                <h2 class="text-lg font-bold text-gray-800 uppercase mt-1 whitespace-nowrap"><?php echo htmlspecialchars($settings['address_tagline']); ?></h2>
                <p class="text-sm font-bold uppercase tracking-widest mt-1">SEMIS CODE: <?php echo htmlspecialchars($settings['semis_code']); ?></p>
                <h3 class="text-lg font-semibold text-gray-600 mt-2 uppercase">Result Card - <?= $examType ?> Examination</h3>
                <p class="text-sm text-gray-500 mt-1">Session <?= $year ?>-<?= $year + 1 ?></p>
            </div>
            <!-- Student Profile Image -->
            <div class="w-24 h-24 flex-shrink-0 border border-gray-300 flex items-center justify-center bg-gray-50 overflow-hidden">
                <?php if (!empty($student['profile_image']) && file_exists($student['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($student['profile_image']) ?>" alt="Student Photo" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-xs text-gray-400 text-center">Photo</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
            <div class="flex">
                <span class="font-bold w-24">Name:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['student_name']) ?></span>
            </div>
            <div class="flex">
                <span class="font-bold w-24">Father Name:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['father_name']) ?></span>
            </div>
            <div class="flex">
                <span class="font-bold w-24">Class:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['current_class']) ?></span>
            </div>
            <div class="flex">
                <span class="font-bold w-24">GR No:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['gr_no']) ?></span>
            </div>
        </div>

        <!-- Marks Table -->
        <table class="w-full border-collapse border border-gray-800 mb-8 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-800 px-4 py-2 text-left">Subject</th>
                    <th class="border border-gray-800 px-4 py-2 text-center w-24">Max Marks</th>
                    <th class="border border-gray-800 px-4 py-2 text-center w-24">Obtained</th>
                    <th class="border border-gray-800 px-4 py-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $subjects = [
                    'English' => 'english',
                    'Mathematics' => 'math',
                    'Social Studies' => 'social_studies',
                    'General Science' => 'general_science',
                    'Mother Tongue (MT)' => 'mt',
                    'Islamiyat' => 'islamiyat',
                    'N.M.T' => 'nmt'
                ];
                foreach ($subjects as $label => $key):
                    $marks = $result[$key];
                    $max = 100; 
                    
                    $displayMarks = $marks;
                    $remark = '';
                    if (strtoupper($marks) === 'A') {
                        $displayMarks = 'A';
                        $remark = 'Absent';
                    } else {
                        $remark = ((float)$marks >= 33) ? 'Pass' : 'Fail';
                    }
                ?>
                <tr>
                    <td class="border border-gray-800 px-4 py-2"><?= $label ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-center"><?= $max ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-center font-bold"><?= $displayMarks ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-sm italic">
                        <?= $remark ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Display Extra Subjects if any -->
                <?php 
                $extra = isset($result['other_subjects']) ? json_decode($result['other_subjects'], true) : [];
                if (is_array($extra)) {
                    foreach ($extra as $subject => $mark) {
                        $displayMark = $mark;
                        $remark = '';
                        if (strtoupper($mark) === 'A') {
                            $displayMark = 'A';
                            $remark = 'Absent';
                        } else {
                            $remark = ((float)$mark >= 33) ? 'Pass' : 'Fail';
                        }
                         ?>
                        <tr>
                            <td class="border border-gray-800 px-4 py-2"><?= ucfirst($subject) ?></td>
                            <td class="border border-gray-800 px-4 py-2 text-center">100</td>
                            <td class="border border-gray-800 px-4 py-2 text-center font-bold"><?= $displayMark ?></td>
                            <td class="border border-gray-800 px-4 py-2 text-sm italic">
                                <?= $remark ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>

                <tr class="bg-gray-100 font-bold">
                    <td class="border border-gray-800 px-4 py-2 text-right">Total</td>
                    <td class="border border-gray-800 px-4 py-2 text-center"><?= $result['total_max'] ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-center"><?= $result['total_obtained'] ?></td>
                    <td class="border border-gray-800 px-4 py-2">
                        <?= $result['percentage'] ?>% (<?= $result['grade'] ?>)
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Signatures -->
        <div class="flex justify-between items-end mt-12 px-2 text-sm font-bold">
            <div class="text-center">
                <div class="w-48 border-b-2 border-gray-800 mb-1"></div>
                <p>Class Teacher : <?= empty($_GET['teacher_name']) ? '________________' : htmlspecialchars($_GET['teacher_name']) ?></p>
            </div>
            <div class="text-center">
                <div class="w-48 border-b-2 border-gray-800 mb-1"></div>
                <p>Headmaster : <?= htmlspecialchars($settings['headmaster_name']) ?></p>
            </div>
        </div>
    </div>
</div>
    
    <?php endforeach; ?>

</body>
</html>
