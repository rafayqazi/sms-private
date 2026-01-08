<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

if (!isset($_GET['id']) || !isset($_GET['exam_type'])) {
    die("Invalid Request");
}

$database = new Database();
$settings = $database->getSchoolSettings();
$studentId = $_GET['id'];
$examType = $_GET['exam_type'];
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$student = $database->getStudent($studentId);
$result = $database->getStudentResult($studentId, $examType, $year);

if (!$student) {
    die("Student not found.");
}

// If result not found, initialize empty structure
if (!$result) {
    $result = [
        'english' => 0, 'math' => 0, 'social_studies' => 0, 'general_science' => 0, 
        'mt' => 0, 'islamiyat' => 0, 'nmt' => 0,
        'total_max' => 700, 'total_obtained' => 0, 'percentage' => 0, 'grade' => '-',
        'other_subjects' => '{}' // Empty JSON
    ];
    // Add extra subjects logic if needed, but for now basic is fine
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Card - <?= htmlspecialchars($student['student_name']) ?></title>
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
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white">
    <div class="fixed top-4 right-4 no-print">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Result
        </button>
    </div>

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
                    <p class="text-sm text-gray-500 mt-1">Session <?= $year ?>-<?= $year+1 ?></p>
                </div>
                <!-- Student Profile Image -->
                <?php 
                    $imagePath = $student['profile_image'];
                    if (!$imagePath || !file_exists($imagePath)) {
                        $imagePath = 'https://ui-avatars.com/api/?name=' . urlencode($student['student_name']) . '&background=random&size=128';
                    }
                ?>
                <div class="w-24 h-24 flex-shrink-0 border border-gray-300 flex items-center justify-center bg-gray-50 overflow-hidden">
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Student Photo" class="w-full h-full object-cover object-top">
                </div>
            </div>

            <!-- Student Info -->
            <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                <div class="flex">
                    <span class="font-bold w-24 text-gray-700">Name:</span>
                    <span class="border-b border-gray-400 flex-1 font-semibold"><?= htmlspecialchars($student['student_name']) ?></span>
                </div>
                <div class="flex">
                    <span class="font-bold w-24 text-gray-700">Father Name:</span>
                    <span class="border-b border-gray-400 flex-1 font-semibold"><?= htmlspecialchars($student['father_name']) ?></span>
                </div>
                <div class="flex">
                    <span class="font-bold w-24 text-gray-700">Class:</span>
                    <span class="border-b border-gray-400 flex-1 font-semibold"><?= htmlspecialchars($student['current_class']) ?></span>
                </div>
                <div class="flex">
                    <span class="font-bold w-24 text-gray-700">GR No:</span>
                    <span class="border-b border-gray-400 flex-1 font-semibold"><?= htmlspecialchars($student['gr_no']) ?></span>
                </div>
            </div>

            <!-- Marks Table -->
            <table class="w-full mb-8 text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">Subject</th>
                        <th class="px-4 py-2 text-center w-24">Max Marks</th>
                        <th class="px-4 py-2 text-center w-24">Obtained</th>
                        <th class="px-4 py-2 text-left">Remarks</th>
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
                        $remark = '';
                        $displayMarks = $marks;
                        if (strtoupper($marks) === 'A') {
                            $displayMarks = 'A';
                            $remark = 'Absent';
                        } else {
                            $remark = ((float)$marks >= 33) ? 'Pass' : 'Fail';
                        }
                    ?>
                    <tr>
                        <td class="px-4 py-2"><?= $label ?></td>
                        <td class="px-4 py-2 text-center">100</td>
                        <td class="px-4 py-2 text-center font-bold font-mono"><?= $displayMarks ?></td>
                        <td class="px-4 py-2 text-sm italic"><?= $remark ?></td>
                    </tr>
                    <?php endforeach; ?>

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
                                <td class="px-4 py-2"><?= ucfirst($subject) ?></td>
                                <td class="px-4 py-2 text-center">100</td>
                                <td class="px-4 py-2 text-center font-bold font-mono"><?= $displayMark ?></td>
                                <td class="px-4 py-2 text-sm italic"><?= $remark ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>

                    <tr class="bg-gray-50 font-bold">
                        <td class="px-4 py-2 text-right">Total</td>
                        <td class="px-4 py-2 text-center"><?= $result['total_max'] ?></td>
                        <td class="px-4 py-2 text-center text-lg"><?= $result['total_obtained'] ?></td>
                        <td class="px-4 py-2">
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

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
