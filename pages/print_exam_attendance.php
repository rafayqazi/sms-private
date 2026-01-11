<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$class = isset($_GET['class']) ? $_GET['class'] : '';
$examName = isset($_GET['exam_name']) ? $_GET['exam_name'] : '';
$subjects = isset($_GET['subjects']) ? $_GET['subjects'] : [];
$dates = isset($_GET['dates']) ? $_GET['dates'] : [];
$days = isset($_GET['days']) ? $_GET['days'] : [];
$times = isset($_GET['times']) ? $_GET['times'] : [];

if (!$class) {
    die("Class is required.");
}

// Clean up empty subjects
$cleanSubjects = [];
for($i=0; $i<count($subjects); $i++) {
    if(!empty($subjects[$i])) {
        $cleanSubjects[] = [
            'name' => $subjects[$i],
            'date' => isset($dates[$i]) ? $dates[$i] : '',
            'time' => isset($times[$i]) ? $times[$i] : ''
        ];
    }
}

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();
$students = [];
foreach ($allStudents as $student) {
    if (isset($student['current_class']) && $student['current_class'] == $class) {
        if (isset($student['student_status']) && $student['student_status'] === 'Alumni') continue;
        $students[] = $student;
    }
}

usort($students, function($a, $b) {
    return $a['gr_no'] - $b['gr_no'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Attendance - Class <?php echo htmlspecialchars($class); ?></title>
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
            max-width: 297mm; /* A4 Landscape width */
            background: white;
        }
        table {
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 print:p-0">

    <div class="no-print fixed top-4 right-4 z-50">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-blue-700 font-bold flex items-center gap-2">
            <i class="fas fa-print"></i> Print Sheet
        </button>
    </div>

    <div class="sheet-container mx-auto shadow-xl print:shadow-none bg-white p-4 print:p-2">
        <!-- Header -->
        <div class="text-center border-b-2 border-gray-800 pb-2 mb-2">
            <div class="flex items-center justify-between px-8">
                <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="Logo" class="h-14 w-14">
                <div class="flex-1">
                    <h1 class="text-xl font-bold uppercase"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                    <p class="text-xs font-mono">SEMIS CODE: <?php echo htmlspecialchars($settings['semis_code']); ?> | <?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                    <h2 class="text-lg font-bold uppercase mt-1 underline decoration-2">Consolidated Examination Attendance Sheet</h2>
                    <p class="text-sm font-semibold mt-1">
                        Exam: <span class="uppercase"><?php echo htmlspecialchars($examName); ?></span> | 
                        Class: <span class="uppercase"><?php echo htmlspecialchars($class); ?></span>
                    </p>
                </div>
                <div class="w-14"></div> <!-- Spacer for balance -->
            </div>
        </div>

        <!-- Table -->
        <table class="w-full border-collapse border border-gray-800 text-[10px]">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-600 px-1 py-1 w-8 text-center">S.No</th>
                    <th class="border border-gray-600 px-1 py-1 w-12 text-center">GR No</th>
                    <th class="border border-gray-600 px-2 py-1 text-left w-32">Student Details</th>
                    
                    <!-- Dynamic Subject Headers -->
                    <?php 
                    $fetchAttendance = isset($_GET['fetch_attendance']) && $_GET['fetch_attendance'] == '1';
                    $attendanceData = [];
                    if ($fetchAttendance) {
                        foreach ($cleanSubjects as $sub) {
                            $attendanceData[$sub['name']] = $db->getExamAttendance($examName, $class, $sub['name'], $sub['date']);
                        }
                    }
                    
                    foreach ($cleanSubjects as $sub): ?>
                    <th class="border border-gray-600 px-1 py-1 text-center min-w-[60px]">
                        <div class="font-bold uppercase leading-tight"><?php echo htmlspecialchars($sub['name']); ?></div>
                        <div class="text-[9px] font-normal text-gray-600 mt-0.5">
                            <?php echo date('d/m/Y', strtotime($sub['date'])); ?>
                            <br>
                            <?php echo htmlspecialchars($sub['time']); ?>
                        </div>
                    </th>
                    <?php endforeach; ?>
                    <th class="border border-gray-600 px-1 py-1 w-16 text-center">Remarks</th>
                    <th class="border border-gray-600 px-1 py-1 w-20 text-center">Invigilator<br>Signature</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1;
                foreach ($students as $student): 
                ?>
                <tr>
                    <td class="border border-gray-400 px-1 py-2 text-center bg-gray-50"><?php echo $count++; ?></td>
                    <td class="border border-gray-400 px-1 py-2 text-center font-bold"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                    <td class="border border-gray-400 px-2 py-1 align-middle">
                        <div class="font-bold uppercase truncate"><?php echo htmlspecialchars($student['student_name']); ?></div>
                        <div class="text-[9px] uppercase text-gray-500 truncate"><?php echo htmlspecialchars($student['father_name']); ?></div>
                    </td>
                    
                    <!-- Signature Cells or Marked Attendance -->
                    <?php foreach ($cleanSubjects as $sub): ?>
                    <td class="border border-gray-400 px-1 py-1 text-center font-bold">
                        <?php 
                        if ($fetchAttendance && isset($attendanceData[$sub['name']][$student['id']])) {
                            echo $attendanceData[$sub['name']][$student['id']];
                        }
                        ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="border border-gray-400 px-1 py-1"></td>
                    <td class="border border-gray-400 px-1 py-1"></td>
                </tr>
                <?php endforeach; ?>

                <!-- Extra Rows -->
                <?php for($k=0; $k<3; $k++): ?>
                <tr>
                    <td class="border border-gray-400 px-1 py-3 bg-gray-50"></td>
                    <td class="border border-gray-400"></td>
                    <td class="border border-gray-400"></td>
                    <?php foreach ($cleanSubjects as $sub): ?>
                    <td class="border border-gray-400"></td>
                    <?php endforeach; ?>
                    <td class="border border-gray-400"></td>
                    <td class="border border-gray-400"></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Footer -->
        <div class="mt-4 flex justify-between items-end text-xs font-semibold px-4 pb-4">
            <div>
                <p>Total Students: <?php echo count($students); ?></p>
            </div>
            <div class="text-center">
                <div class="h-8 border-b border-black w-32 mb-1"></div>
                <p><?php echo htmlspecialchars($settings['headmaster_name']); ?></p>
            </div>
        </div>

    </div>

</body>
</html>
