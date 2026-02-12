<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$class = isset($_GET['class']) ? $_GET['class'] : '';
$examName = isset($_GET['exam_name']) ? $_GET['exam_name'] : '';

if (!$class || !$examName) {
    die("Class and Exam Name are required.");
}

$defaultSubjects = ['ENG', 'MATH', 'Social Studies', 'G.Science', 'MT', 'Islamyat', 'NMT'];

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->getStudentsByClass($class);
$schedule = $db->getExamSchedule($examName, $class);

// Merge default subjects with dynamic ones found in schedule
$savedSubjects = array_keys($schedule);
$allSubjects = array_unique(array_merge($defaultSubjects, $savedSubjects));

// Sort students by GR No
usort($allStudents, function($a, $b) {
    return $a['gr_no'] - $b['gr_no'];
});

// Fetch Attendance Data
$attendanceMap = [];
foreach ($allSubjects as $sub) {
    $subAttendance = $db->getExamAttendance($examName, $class, $sub);
    // Merge into map: student_id => [subject => status]
    foreach ($subAttendance as $stuId => $status) {
        $attendanceMap[$stuId][$sub] = $status;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Attendance Sheet - Class <?php echo htmlspecialchars($class); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
                background: white;
                font-family: 'Times New Roman', Times, serif;
            }
            .no-print { 
                display: none; 
            }
        }
        .sheet-container {
            width: 100%;
            /* max-width removed for print to prevent scaling issues */
            background: white;
        }
        @media print {
            .sheet-container {
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 print:p-0">

    <div class="no-print fixed top-4 right-4 z-50">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-blue-700 font-bold flex items-center gap-2">
            <i class="fas fa-print"></i> Print Sheet
        </button>
    </div>

    <div class="sheet-container mx-auto shadow-xl print:shadow-none bg-white p-4 print:p-2 relative z-10">
        <!-- Watermark -->
        <div class="absolute inset-0 flex items-center justify-center z-0 pointer-events-none overflow-hidden">
            <img src="../GBPS_LOGO.png" alt="Watermark" class="w-[500px] h-[500px] opacity-[0.1] object-contain">
        </div>

        <!-- Header -->
        <div class="text-center border-b-2 border-gray-800 pb-2 mb-2 relative z-20">
            <div class="flex items-center justify-between px-8">
                <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>_2" alt="Logo" class="h-32 w-32 object-contain">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold uppercase"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                    <p class="text-sm font-mono">SEMIS CODE: <?php echo htmlspecialchars($settings['semis_code']); ?> | <?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                    <h2 class="text-xl font-bold uppercase mt-2 underline decoration-2">Online Examination Attendance Record</h2>
                    <p class="text-base font-semibold mt-1">
                        Exam: <span class="uppercase"><?php echo htmlspecialchars($examName); ?></span> | 
                        Class: <span class="uppercase"><?php echo htmlspecialchars($class); ?></span>
                    </p>
                </div>
                <!-- Spacer -->
                <div class="w-32"></div> 
            </div>
        </div>

        <!-- Content Wrapper to stay above watermark -->
        <div class="relative z-20">
            <!-- Table -->
            <table class="w-full border-collapse border border-gray-800 text-[10px]">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-600 px-1 py-1 w-8 text-center">S.No</th>
                        <th class="border border-gray-600 px-1 py-1 w-12 text-center">GR No</th>
                        <th class="border border-gray-600 px-2 py-1 text-left w-32">Student Details</th>
                        
                        <?php foreach ($allSubjects as $sub): 
                            $info = isset($schedule[$sub]) ? $schedule[$sub] : ['date' => '', 'time' => ''];
                            $dateStr = $info['date'] ? date('d/m', strtotime($info['date'])) : '-';
                        ?>
                        <th class="border border-gray-600 px-1 py-1 text-center min-w-[60px]">
                            <div class="font-bold uppercase leading-tight"><?php echo htmlspecialchars($sub); ?></div>
                            <div class="text-[9px] font-normal mt-0.5 text-gray-600"><?php echo $dateStr; ?></div>
                            <div class="text-[8px] font-normal text-gray-500"><?php echo htmlspecialchars($info['time']); ?></div>
                        </th>
                        <?php endforeach; ?>
                        <th class="border border-gray-600 px-1 py-1 w-24 text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    foreach ($allStudents as $student): 
                    ?>
                    <tr>
                        <td class="border border-gray-400 px-1 py-2 text-center bg-gray-50"><?php echo $count++; ?></td>
                        <td class="border border-gray-400 px-1 py-2 text-center font-bold"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                        <td class="border border-gray-400 px-2 py-1 align-middle">
                            <div class="font-bold uppercase truncate"><?php echo htmlspecialchars($student['student_name']); ?></div>
                            <div class="text-[9px] uppercase text-gray-500 truncate"><?php echo htmlspecialchars($student['father_name']); ?></div>
                        </td>
                        
                        <?php foreach ($allSubjects as $sub): ?>
                    <td class="border border-gray-400 px-1 py-1 text-center font-bold">
                            <?php 
                            $status = isset($attendanceMap[$student['id']][$sub]) ? $attendanceMap[$student['id']][$sub] : '-';
                            if ($status === 'A') {
                                echo '<span class="text-red-600 font-extrabold">A</span>';
                            } elseif ($status === 'L') {
                                echo '<span class="text-yellow-600 font-extrabold">L</span>';
                            } else {
                                echo $status;
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="border border-gray-400 px-1 py-1"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Footer -->
            <div class="mt-4 flex justify-between items-end text-xs font-semibold px-4 pb-4">
                <div>
                    <p>Total Students: <?php echo count($allStudents); ?></p>
                    <p class="mt-1 text-[9px] text-gray-500">Generated on: <?php echo date('d-M-Y h:i A'); ?></p>
                </div>
                <div class="text-center">
                    <div class="h-8 border-b border-black w-32 mb-1"></div>
                    <p>PRINCIPAL : <?php echo htmlspecialchars($settings['headmaster_name']); ?></p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
