<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Sample CSV Download - MUST BE BEFORE ANY HTML OUTPUT
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_import_sample.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['GR Number', 'Student Name', 'Father Name', 'Gender', 'Date of Birth', 'Admission Date', 'Class', 'B-Form No', 'Father CNIC', 'Father Contact', 'District', 'Taluka', 'Previous School', 'Repeater (Yes/No)']);
    fputcsv($output, ['101', 'Ali Khan', 'Ahmed Khan', 'Male', '2015-05-12', '2024-04-01', 'Class 1', '42401-1234567-1', '42401-7654321-1', '03001234567', 'Ghotki', 'Ubauro', 'N/A', 'No']);
    fclose($output);
    exit;
}

require_once '../includes/header.php';

$db = new Database();
$errorMsg = '';
$successMsg = '';

// Step 1: Upload CSV
// Step 2: Map Columns
// Step 3: Process

$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$csvPath = isset($_SESSION['bulk_import_file']) ? $_SESSION['bulk_import_file'] : '';

$fieldsToMap = [
    'gr_no' => 'GR Number',
    'student_name' => 'Student Name',
    'father_name' => 'Father Name',
    'gender' => 'Gender',
    'date_of_birth' => 'Date of Birth (YYYY-MM-DD)',
    'admission_date' => 'Admission Date (YYYY-MM-DD)',
    'admission_class' => 'Admission Class',
    'current_class' => 'Class',
    'b_form_no' => 'B-Form / ID Number',
    'father_cnic' => 'Father CNIC',
    'father_contact' => 'Father Contact',
    'district' => 'District',
    'taluka' => 'Taluka',
    'previous_school' => 'Previous School',
    'student_status' => 'Status (Active/Inactive)',
    'is_repeater' => 'Is Repeater? (Yes/No)'
];

// Handle Step transitions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    if ($step == 1 && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorCodes = [
                UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
                UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
                UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
                UPLOAD_ERR_NO_FILE => "No file was uploaded",
                UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
            ];
            $errorMsg = $errorCodes[$file['error']] ?? "Unknown upload error (Code: " . $file['error'] . ")";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($ext !== 'csv') {
                $errorMsg = "Please upload a valid CSV file.";
            } else {
            $tmpName = time() . '_' . bin2hex(random_bytes(4)) . '.csv';
            $dest = '../data/tmp/' . $tmpName;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $_SESSION['bulk_import_file'] = $dest;
                $csvPath = $dest;
                $step = 2;
            } else {
                $errorMsg = "Failed to save uploaded file.";
            }
        }
    }
}
    elseif ($step == 2 && isset($_POST['map_columns'])) {
        $mappings = $_POST['mappings']; // system_field => csv_index
        $csvFile = $_SESSION['bulk_import_file'] ?? '';
        
        if (empty($csvFile) || !file_exists($csvFile)) {
            $errorMsg = "Session expired or file missing. Please start over.";
            $step = 1;
        } else {
            $handle = fopen($csvFile, "r");
            $headers = fgetcsv($handle); // Skip header row
            
            $studentsToImport = [];
            while (($row = fgetcsv($handle)) !== FALSE) {
                $student = [];
                // Initialize all fields with defaults
                foreach ($db->headers as $h) { $student[$h] = ''; }
                
                // Apply mappings
                foreach ($mappings as $sysField => $csvIdx) {
                    if ($csvIdx !== "") {
                        $student[$sysField] = trim($row[$csvIdx] ?? '');
                    }
                }
                
                // Basic cleanup/validation logic
                if (empty($student['student_name'])) continue; // Skip rows without name
                
                // Handle Repeater Yes/No to 1/0
                if (isset($student['is_repeater'])) {
                    $val = strtolower($student['is_repeater']);
                    $student['is_repeater'] = ($val === 'yes' || $val === '1' || $val === 'y') ? 1 : 0;
                }

                $studentsToImport[] = $student;
            }
            fclose($handle);
            
            if (!empty($studentsToImport)) {
                if ($db->bulkAddStudents($studentsToImport)) {
                    $successMsg = count($studentsToImport) . " students imported successfully!";
                    unlink($csvFile);
                    unset($_SESSION['bulk_import_file']);
                    $step = 3;
                } else {
                    $errorMsg = "Bulk import failed. Check file permissions.";
                }
            } else {
                $errorMsg = "No valid student data found in CSV.";
            }
        }
    }
}

// Get CSV Headers for Step 2
$csvHeaders = [];
if ($step == 2 && file_exists($csvPath)) {
    $handle = fopen($csvPath, "r");
    $csvHeaders = fgetcsv($handle);
    fclose($handle);
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-indigo-100 rounded-lg text-indigo-600">
                    <i class="fas fa-file-import"></i>
                </div>
                Bulk Student Admission
            </h2>
            <a href="students.php" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>

        <!-- Stepper -->
        <div class="flex items-center justify-center mb-10">
            <div class="flex items-center w-full max-w-lg">
                <div class="relative flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 <?php echo $step >= 1 ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'; ?>">1</div>
                    <span class="absolute top-12 text-xs font-bold uppercase <?php echo $step >= 1 ? 'text-indigo-600' : 'text-gray-400'; ?>">Upload</span>
                </div>
                <div class="flex-1 h-px <?php echo $step >= 2 ? 'bg-indigo-600' : 'bg-gray-300'; ?>"></div>
                <div class="relative flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 <?php echo $step >= 2 ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'; ?>">2</div>
                    <span class="absolute top-12 text-xs font-bold uppercase <?php echo $step >= 2 ? 'text-indigo-600' : 'text-gray-400'; ?>">Map Fields</span>
                </div>
                <div class="flex-1 h-px <?php echo $step >= 3 ? 'bg-indigo-600' : 'bg-gray-300'; ?>"></div>
                <div class="relative flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 <?php echo $step >= 3 ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'; ?>">3</div>
                    <span class="absolute top-12 text-xs font-bold uppercase <?php echo $step >= 3 ? 'text-indigo-600' : 'text-gray-400'; ?>">Finish</span>
                </div>
            </div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <span><?php echo $errorMsg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-6 mb-6 rounded-lg text-center">
                <i class="fas fa-check-circle text-5xl mb-4 block"></i>
                <h3 class="text-xl font-bold mb-2">Import Complete!</h3>
                <p><?php echo $successMsg; ?></p>
                <div class="mt-6 flex justify-center gap-4">
                    <a href="students.php" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">View Students</a>
                    <a href="bulk_admission.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold hover:bg-gray-300">Import More</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            
            <?php if ($step == 1): ?>
                <!-- STEP 1: UPLOAD -->
                <div class="p-10 text-center">
                    <div class="mb-8">
                        <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-cloud-upload-alt text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Choose CSV File</h3>
                        <p class="text-gray-500">Upload your student list in .csv format. You'll map columns in the next step.</p>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="step" value="1">
                        
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:border-indigo-400 transition-colors cursor-pointer group" onclick="document.getElementById('csv_file').click()" id="uploadZone">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" class="hidden" onchange="handleFileSelect(this)">
                            <div id="fileInfo" class="hidden text-indigo-600 font-bold mb-4">
                                <i class="fas fa-file-csv text-2xl"></i> <span id="fileName"></span>
                            </div>
                            <div id="uploadPrompt">
                                <p class="text-indigo-600 font-bold group-hover:scale-105 transition-transform"><i class="fas fa-search mr-2"></i>Click to select file or drag & drop</p>
                                <p class="text-gray-400 text-sm mt-2">Max file size: 5MB</p>
                            </div>
                        </div>

                        <div id="submitBtnContainer" class="hidden mt-6">
                            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-upload"></i> Start Uploading
                            </button>
                        </div>

                        <script>
                            function handleFileSelect(input) {
                                if (input.files && input.files[0]) {
                                    document.getElementById('uploadPrompt').classList.add('hidden');
                                    document.getElementById('fileInfo').classList.remove('hidden');
                                    document.getElementById('fileName').textContent = input.files[0].name;
                                    document.getElementById('submitBtnContainer').classList.remove('hidden');
                                    document.getElementById('uploadZone').classList.remove('p-8');
                                    document.getElementById('uploadZone').classList.add('p-12', 'bg-indigo-50', 'border-indigo-400');
                                }
                            }
                        </script>

                        <div class="bg-blue-50 p-4 rounded-xl text-left border border-blue-100">
                            <h4 class="text-blue-800 font-bold text-sm mb-2 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i> CSV Preparation Tips
                            </h4>
                            <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside opacity-90">
                                <li>Ensure the first row contains column headers (Name, Father, etc).</li>
                                <li>Format dates as <code class="bg-blue-100 px-1 rounded">YYYY-MM-DD</code>.</li>
                                <li>The importer will ignore rows without a "Student Name".</li>
                            </ul>
                            <div class="mt-4">
                                <a href="?download_sample=1" class="text-blue-600 font-bold text-xs hover:underline flex items-center gap-1">
                                    <i class="fas fa-download"></i> Download Sample CSV Template
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- STEP 2: MAPPING -->
                <div class="p-8">
                    <div class="mb-6 flex justify-between items-end">
                        <h3 class="text-xl font-bold text-gray-800">Map Columns</h3>
                        <p class="text-sm text-gray-500 italic">Match system fields with your CSV columns</p>
                    </div>

                    <form action="" method="POST">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="step" value="2">
                        <input type="hidden" name="map_columns" value="1">

                        <div class="space-y-4">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="p-4 text-xs uppercase text-gray-500 font-bold">System Field</th>
                                        <th class="p-4 text-xs uppercase text-gray-500 font-bold">Your CSV Column</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($fieldsToMap as $key => $label): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="p-4">
                                                <span class="font-bold text-gray-700"><?php echo $label; ?></span>
                                                <?php if($key == 'student_name'): ?><span class="text-red-500">*</span> <?php endif; ?>
                                            </td>
                                            <td class="p-4">
                                                <select name="mappings[<?php echo $key; ?>]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 appearance-none bg-white">
                                                    <option value="">-- Ignore / Skip --</option>
                                                    <?php foreach ($csvHeaders as $index => $header): ?>
                                                        <?php 
                                                            // Auto-match logic
                                                            $selected = '';
                                                            $hLower = strtolower(trim($header));
                                                            $kLower = strtolower($key);
                                                            $labelLower = strtolower($label);
                                                            if (str_contains($hLower, $kLower) || str_contains($hLower, $labelLower) || str_contains($labelLower, $hLower)) {
                                                                $selected = 'selected';
                                                            }
                                                        ?>
                                                        <option value="<?php echo $index; ?>" <?php echo $selected; ?>>
                                                            Col <?php echo $index + 1; ?>: <?php echo htmlspecialchars($header); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-8 pt-6 border-t flex justify-end gap-4">
                            <a href="bulk_admission.php" class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200">Start Over</a>
                            <button type="submit" class="px-10 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition-all active:scale-95 flex items-center gap-2">
                                <i class="fas fa-check-double"></i> Confirm & Import Now
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
