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

$db = new Database();
$settings = $db->getSchoolSettings(); // Fetch settings for dynamic values
$allStudents = $db->readData();

// Filter students by class
$students = array_filter($allStudents, function($student) use ($class) {
    // Only active students
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    return !$isAlumni && isset($student['current_class']) && $student['current_class'] === $class;
});

// Sort by GR No
usort($students, function($a, $b) {
    return (int)$a['gr_no'] - (int)$b['gr_no'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Slips - Class <?php echo htmlspecialchars($class); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 0.5cm;
            }
            body {
                background: white;
                font-family: 'Times New Roman', Times, serif;
            }
            .no-print {
                display: none !important;
            }
            /* Aim for 2 slips per page */
            .slip-container {
                min-height: 48vh; /* Use min-height to allow expansion */
                margin-bottom: 2vh;
                page-break-inside: avoid;
                border: 2px solid #000;
                position: relative;
                display: flex;
                flex-direction: column;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        .slip-container {
            border: 2px solid #000;
            padding: 1.5rem;
            background: white;
            margin-bottom: 2rem;
            max-width: 21cm;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            min-height: 14cm; /* Approximate half A4 height for screen view */
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            width: 300px;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="bg-gray-100 p-8 min-h-screen">

    <div class="max-w-4xl mx-auto mb-8 no-print flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Previewing Slips</h1>
        <button onclick="window.print()" class="bg-indigo-600 text-white px-6 py-2 rounded shadow hover:bg-indigo-700 font-bold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Slips
        </button>
    </div>

    <?php 
    $count = 0;
    foreach ($students as $student): 
        $count++;
    ?>
    
    <div class="slip-container relative overflow-hidden">
        <!-- Watermark -->
        <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" class="watermark">

        <!-- Header -->
        <div class="flex items-center gap-4 mb-4 border-b-2 border-black pb-4 relative z-10">
            <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="Logo" class="w-20 h-20 object-contain">
            <div class="flex-1 text-center">
                <h1 class="text-3xl font-bold uppercase tracking-wide font-serif"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                <p class="text-sm font-semibold uppercase tracking-widest mt-1"><?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                <p class="text-sm font-bold uppercase tracking-widest mt-1">SEMIS CODE: <?php echo htmlspecialchars($settings['semis_code']); ?></p>
            </div>
            <div class="w-20"></div> <!-- Spacer for centering -->
        </div>

        <div class="text-center mb-6 relative z-10">
            <h2 class="inline-block bg-black text-white px-8 py-1 font-bold text-xl uppercase tracking-wider rounded-sm">
                Examination Slip
            </h2>
            <h3 class="text-lg font-bold mt-2 text-gray-800 uppercase border-b border-gray-400 inline-block px-4">
                <?php echo htmlspecialchars($examName); ?>
            </h3>
        </div>

        <!-- Student Info Grid -->
        <div class="grid grid-cols-[1fr_auto] gap-6 relative z-10">
            <div class="space-y-3">
                <div class="grid grid-cols-[120px_1fr] gap-2 items-end">
                    <span class="font-bold text-lg">Name:</span>
                    <div class="border-b-2 border-black border-dotted font-serif text-xl capitalize px-2">
                        <?php echo htmlspecialchars($student['student_name']); ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-[120px_1fr] gap-2 items-end">
                    <span class="font-bold text-lg">Father Name:</span>
                    <div class="border-b-2 border-black border-dotted font-serif text-xl capitalize px-2">
                        <?php echo htmlspecialchars($student['father_name']); ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid grid-cols-[80px_1fr] gap-2 items-end">
                        <span class="font-bold text-lg">Class:</span>
                        <div class="border-b-2 border-black border-dotted font-serif text-xl px-2">
                            <?php echo htmlspecialchars($student['current_class']); ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-[80px_1fr] gap-2 items-end">
                        <span class="font-bold text-lg">GR No:</span>
                        <div class="border-b-2 border-black border-dotted font-serif text-xl px-2 font-mono">
                            <?php echo htmlspecialchars($student['gr_no']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photo Area -->
            <div class="w-32 h-36 border-2 border-black flex items-center justify-center bg-gray-50">
                <?php 
                $imagePath = '';
                if (!empty($student['profile_image'])) {
                    $possiblePaths = [
                        '../' . $student['profile_image'],
                        $student['profile_image'],
                        '../pages/' . $student['profile_image'],
                        '../../' . $student['profile_image']
                    ];
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $imagePath = $path;
                            break;
                        }
                    }
                }
                
                if ($imagePath): ?>
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" class="w-full h-full object-cover object-top">
                <?php else: ?>
                    <span class="text-xs text-center text-gray-400 font-bold uppercase">Attach<br>Photo</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Instructions / Blank Table -->
        <div class="mt-6 relative z-10">
            <table class="w-full border-2 border-black">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-black">
                        <th class="border-r border-black py-1 px-2 text-left text-sm w-1/4">Subject</th>
                        <th class="border-r border-black py-1 px-2 text-center text-sm w-24">Date</th>
                        <th class="border-r border-black py-1 px-2 text-center text-sm w-24">Day</th>
                        <th class="border-r border-black py-1 px-2 text-center text-sm w-24">Time</th>
                        <th class="py-1 px-2 text-left text-sm">Invigilator Sign</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subjects = isset($_GET['subjects']) ? $_GET['subjects'] : [];
                    $dates = isset($_GET['dates']) ? $_GET['dates'] : [];
                    $days = isset($_GET['days']) ? $_GET['days'] : [];
                    $times = isset($_GET['times']) ? $_GET['times'] : [];
                    
                    // Default rows if empty (for preview)
                    if (empty($subjects)) {
                        for($i=0; $i<4; $i++) {
                            echo '<tr class="border-b border-black h-8"><td class="border-r border-black"></td><td class="border-r border-black"></td><td class="border-r border-black"></td><td class="border-r border-black"></td><td></td></tr>';
                        }
                    } else {
                        for($i=0; $i < count($subjects); $i++) {
                            $sub = isset($subjects[$i]) ? htmlspecialchars($subjects[$i]) : '';
                            $date = isset($dates[$i]) ? date('d-m-Y', strtotime($dates[$i])) : '';
                            $day = isset($days[$i]) ? htmlspecialchars($days[$i]) : '';
                            $time = isset($times[$i]) ? htmlspecialchars($times[$i]) : '';
                            
                            echo '<tr class="border-b border-black h-7 text-sm">';
                            echo '<td class="border-r border-black px-2 py-1 font-semibold">' . $sub . '</td>';
                            echo '<td class="border-r border-black px-2 py-1 text-center">' . $date . '</td>';
                            echo '<td class="border-r border-black px-2 py-1 text-center">' . $day . '</td>';
                            echo '<td class="border-r border-black px-2 py-1 text-center">' . $time . '</td>';
                            echo '<td class="px-2 py-1"></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-auto pt-6 flex justify-between items-end z-10 w-full">
            <div class="text-xs">
                <p><strong>Note:</strong> 1. Bring this slip daily.</p>
                <p class="ml-[34px]">2. Mobile phones are not allowed.</p>
            </div>
            <div class="text-center">
                <div class="w-40 border-b-2 border-black mb-1"></div>
                <span class="font-bold text-sm uppercase">Headmaster Signature</span>
            </div>
        </div>
    </div>

    <?php 
        // Page break after every 2 slips
        if ($count % 2 == 0 && $count < count($students)) {
            echo '<div class="page-break"></div>';
        }
    endforeach; 
    
    if (empty($students)):
    ?>
        <div class="text-center py-20">
            <h2 class="text-2xl font-bold text-gray-400">No students found in Class <?php echo htmlspecialchars($class); ?></h2>
        </div>
    <?php endif; ?>

</body>
</html>
