<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
$db = new Database();

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: students.php");
    exit;
}

$student = $db->getStudent($id);
if (!$student) {
    header("Location: students.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Card - <?php echo htmlspecialchars($student['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    screens: {
                        'print': {'raw': 'print'},
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-black p-8 print:p-0">
    <div class="print:hidden text-center mb-8">
        <button onclick="window.print()" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition-colors">Print Result Card</button>
        <a href="students.php" class="ml-4 text-indigo-600 hover:underline">Back to List</a>
    </div>

    <div class="max-w-4xl mx-auto border-2 border-black p-8 print:border-none print:p-0">
        <div class="text-center border-b-2 border-black pb-4 mb-8">
            <h1 class="text-3xl font-bold uppercase">GBPS Ali Bux Jarwar</h1>
            <h2 class="text-xl font-medium mt-2">Result Sheet of Terminal Examination 2024-25</h2>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="flex gap-2">
                <span class="font-bold min-w-[120px]">Name:</span>
                <span><?php echo htmlspecialchars($student['name']); ?></span>
            </div>
            <div class="flex gap-2">
                <span class="font-bold min-w-[120px]">Father's Name:</span>
                <span><?php echo htmlspecialchars($student['father_name']); ?></span>
            </div>
            <div class="flex gap-2">
                <span class="font-bold min-w-[120px]">Class:</span>
                <span><?php echo htmlspecialchars($student['class']); ?></span>
            </div>
            <div class="flex gap-2">
                <span class="font-bold min-w-[120px]">GR No:</span>
                <span><?php echo htmlspecialchars($student['gr_no']); ?></span>
            </div>
            <div class="flex gap-2">
                <span class="font-bold min-w-[120px]">Date of Birth:</span>
                <span><?php echo date('d-m-Y', strtotime($student['dob'])); ?></span>
            </div>
        </div>

        <table class="w-full border-collapse mb-8">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black p-3 text-center">Subject</th>
                    <th class="border border-black p-3 text-center">Total Marks</th>
                    <th class="border border-black p-3 text-center">Obtained Marks</th>
                    <th class="border border-black p-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $subjects = [
                    'mt' => 'M.T',
                    'english' => 'English',
                    'math' => 'Math',
                    'g_science' => 'G.Science',
                    'islamiyat' => 'Islamiyat',
                    's_studies' => 'S.Studies',
                    'nmt' => 'N.M.T'
                ];
                
                foreach ($subjects as $key => $label) {
                    $marks = isset($student[$key]) ? (int)$student[$key] : 0;
                    $status = $marks >= 33 ? 'PASS' : 'FAIL';
                    echo "<tr>
                        <td class='border border-black p-3 text-left'>$label</td>
                        <td class='border border-black p-3 text-center'>100</td>
                        <td class='border border-black p-3 text-center'>$marks</td>
                        <td class='border border-black p-3 text-center'>$status</td>
                    </tr>";
                }
                ?>
                <tr class="font-bold bg-gray-100">
                    <td class="border border-black p-3 text-left">Total</td>
                    <td class="border border-black p-3 text-center">700</td>
                    <td class="border border-black p-3 text-center"><?php echo htmlspecialchars($student['total_marks']); ?></td>
                    <td class="border border-black p-3 text-center"><?php echo htmlspecialchars($student['status']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-between mt-16">
            <div class="text-center border-t border-black pt-2 w-48">Class Teacher</div>
            <div class="text-center border-t border-black pt-2 w-48">Head Master</div>
        </div>
    </div>
</body>
</html>
