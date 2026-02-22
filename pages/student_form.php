<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('student_form.php')) {
    header("Location: students.php");
    exit;
}

$db = new Database();
$formSettings = $db->getSchoolSettings();

$student = null;
$id = isset($_GET['id']) ? $_GET['id'] : null;
$error = '';

if ($id) {
    $student = $db->getStudent($id);
    if (!$student) {
        header("Location: students.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Verification
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed. Possible attack detected.");
    }

    $grNo = isset($_POST['gr_no']) ? $_POST['gr_no'] : '0';
    $currentClass = $_POST['current_class'];
    
    // Remove leading zeros (e.g., "01" -> "1", "007" -> "7")
    $grNo = ltrim($grNo, '0');
    // If the result is empty (e.g. input was "0" or "00"), set it to "0"
    if ($grNo === '') {
        $grNo = '0';
    }

    // GR No Validation: Required for all classes except those where it's not required in settings
    $classData = $db->getClassByName($currentClass);
    $isGrRequired = $classData ? $classData['is_gr_required'] : 1;

    // Strict Validation for all fields if GR is required
    /*
    if ($isGrRequired) {
        if ($grNo === '0' || empty($_POST['gr_no'])) {
            $error = "Error: GR No is required for $currentClass.";
        } elseif (empty($_POST['student_name'])) {
            $error = "Error: Student Name is required.";
        } elseif (empty($_POST['father_name'])) {
            $error = "Error: Father's Name is required.";
        } elseif (empty($_POST['gender'])) {
            $error = "Error: Gender is required.";
        } elseif (empty($_POST['date_of_birth'])) {
            $error = "Error: Date of Birth is required.";
        } elseif (empty($_POST['admission_date'])) {
            $error = "Error: Admission Date is required.";
        } elseif (empty($_POST['father_cnic'])) {
            $error = "Error: Father's CNIC is required.";
        } elseif (empty($_POST['father_contact'])) {
            $error = "Error: Father's Contact is required.";
        }
    } else {
        // Minimal validation for non-GR classes
        if (empty($_POST['student_name'])) {
            $error = "Error: Student Name is required.";
        } elseif (empty($_POST['father_name'])) {
            $error = "Error: Father's Name is required.";
        }
    }
    */

    if (!empty($error)) {
        $student = $_POST;
        if ($id) $student['id'] = $id;
    }
    $dob = $_POST['date_of_birth'];

    // Age Validation
    if ($dob) {
        $dobDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;

        if ($age < 5) {
            $targetDate = clone $dobDate;
            $targetDate->modify('+5 years');
            $interval = $today->diff($targetDate);
            
            $remaining = [];
            if ($interval->y > 0) $remaining[] = $interval->y . " year" . ($interval->y > 1 ? 's' : '');
            if ($interval->m > 0) $remaining[] = $interval->m . " month" . ($interval->m > 1 ? 's' : '');
            if ($interval->d > 0) $remaining[] = $interval->d . " day" . ($interval->d > 1 ? 's' : '');
            
            $timeString = implode(', ', $remaining);
            $error = "Child age is not 5 years yet. Time remaining to complete 5 years: " . $timeString . ".";
            
            $student = $_POST;
            if ($id) $student['id'] = $id;
        }
    }

    // Check for duplicate GR No (only if no age error and GR No is not '0'/empty)
    if (empty($error) && $grNo !== '0' && $db->isGrNoExists($grNo, $id)) {
        // Check if it's an alumni or just a regular duplicate
        $existing = $db->getStudentByGrNo($grNo);
        if ($existing && isset($existing['student_status']) && $existing['student_status'] === 'Alumni') {
             $error = "GR No '$grNo' belongs to an Alumni student (" . htmlspecialchars($existing['student_name']) . "). <a href='student_form.php?id=" . $existing['id'] . "&restore=1' class='underline font-bold hover:text-indigo-200'>Click here to Edit/Restore this student</a>.";
        } else {
             $error = "Error: GR No '$grNo' is already assigned to another student.";
        }
        
        // Retain submitted values
        $student = $_POST;
        // If updating, keep the ID
        if ($id) $student['id'] = $id;
    }

    // Check for duplicate B-Form No
    $bFormNo = isset($_POST['b_form_no']) ? str_replace('-', '', $_POST['b_form_no']) : '';
    if (empty($error) && !empty($bFormNo)) {
        $existingStudentName = $db->isBFormNoExists($bFormNo, $id);
        if ($existingStudentName) {
            $error = "your B-Form number is same as $existingStudentName student";
            $student = $_POST;
            if ($id) $student['id'] = $id;
        }
    }
    
    if (!empty($error)) {
        // Error is already set, just fall through to display it
    } else {
        // Handle File Uploads
        $uploadDir = '../uploads/students/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileFields = ['profile_image', 'father_cnic_front', 'father_cnic_back', 'b_form_img', 'slc_img'];
        $uploadedFiles = [];

        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                $fileTmpPath = $_FILES[$field]['tmp_name'];
                $fileName = $_FILES[$field]['name'];
                $fileSize = $_FILES[$field]['size'];
                $fileType = $_FILES[$field]['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
                $allowedMimeTypes = array('image/jpeg', 'image/png', 'image/gif');
                
                // Max file size 2MB
                $maxFileSize = 2 * 1024 * 1024;

                if (in_array($fileExtension, $allowedfileExtensions) && $fileSize <= $maxFileSize) {
                    // Double check MIME type for security
                    $check = getimagesize($fileTmpPath);
                    if ($check !== false && in_array($check['mime'], $allowedMimeTypes)) {
                        // Create unique filename: GR-123-field_name-HEX.jpg
                        $newFileName = 'GR-' . preg_replace('/[^a-zA-Z0-9]/', '', $grNo) .'-' . $field . '-' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
                        $dest_path = $uploadDir . $newFileName;

                        if(move_uploaded_file($fileTmpPath, $dest_path)) {
                            $uploadedFiles[$field] = $dest_path;
                        }
                    } else {
                        $error = "Error: Invalid image file type for $field.";
                        break;
                    }
                } elseif ($fileSize > $maxFileSize) {
                    $error = "Error: File size too large for $field. Max limit is 2MB.";
                    break;
                }
            }
        }

        // Handle Existing Parent Images (Autofill)
        if (isset($_POST['existing_father_cnic_front']) && !isset($uploadedFiles['father_cnic_front'])) {
            $uploadedFiles['father_cnic_front'] = $_POST['existing_father_cnic_front'];
        }
        if (isset($_POST['existing_father_cnic_back']) && !isset($uploadedFiles['father_cnic_back'])) {
            $uploadedFiles['father_cnic_back'] = $_POST['existing_father_cnic_back'];
        }

        $data = [
            'gr_no' => $grNo,
            'student_name' => $_POST['student_name'],
            'father_name' => $_POST['father_name'],
            'gender' => $_POST['gender'],
            'date_of_birth' => $_POST['date_of_birth'],
            'admission_date' => $_POST['admission_date'],
            'admission_class' => $_POST['admission_class'] ?? '',
            'current_class' => $_POST['current_class'],
            'b_form_no' => str_replace('-', '', $_POST['b_form_no']),
            'father_cnic' => str_replace('-', '', $_POST['father_cnic']),
            'father_contact' => str_replace('-', '', $_POST['father_contact']),
            'district' => $_POST['district'],
            'taluka' => $_POST['taluka'],
            'caste' => isset($_POST['caste']) ? $_POST['caste'] : '',
            'religion' => isset($_POST['religion']) ? $_POST['religion'] : '',
            'place_of_birth' => isset($_POST['place_of_birth']) ? $_POST['place_of_birth'] : '',
            'school_name' => $_POST['school_name'],
            'previous_school' => isset($_POST['previous_school']) ? $_POST['previous_school'] : '',
            'is_active' => 1
        ];

        // Merge uploaded files into data
        $data = array_merge($data, $uploadedFiles);

        if (isset($_POST['student_status'])) {
            $data['student_status'] = $_POST['student_status'];
        } elseif (isset($_GET['restore']) || (isset($student['student_status']) && $student['student_status'] === 'Alumni')) {
             // If we are editing an alumni but didn't explicitly send status (shouldn't happen if field is there), 
             // but if restore param is there, force it.
             if (isset($_GET['restore'])) {
                 $data['student_status'] = 'Active';
             }
        }

        if ($id) {
            $db->updateStudent($id, $data);
        } else {
            $db->addStudent($data);
        }
        header("Location: students.php");
        exit;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden my-6 md:my-10">
    <div class="bg-gradient-to-r from-primary to-green-900 text-white p-4 md:p-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-center md:text-left">
            <h1 class="text-2xl font-bold"><?php echo $id ? 'Edit Student Record' : 'New Student Admission'; ?></h1>
            <p class="text-green-100 text-sm mt-1">Please fill in the details carefully</p>
        </div>
        <a href="students.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 text-sm font-medium w-full md:w-auto">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="p-4 md:p-8">
        <?php echo csrfInput(); ?>
        
        <!-- Profile Image Upload (Top) -->
        <div class="flex flex-col items-center justify-center mb-8 pb-6 border-b">
            <div class="relative group">
                <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                    <?php if ($student && !empty($student['profile_image'])): ?>
                        <img id="profile_preview" src="<?php echo $student['profile_image']; ?>" alt="Profile Preview" class="w-full h-full object-cover">
                    <?php else: ?>
                        <img id="profile_preview" src="../assets/default_avatar.png" onerror="this.src='https://via.placeholder.com/150?text=No+Image'" alt="Profile Preview" class="w-full h-full object-cover text-gray-400">
                    <?php endif; ?>
                </div>
                <label for="profile_image_input" class="absolute bottom-0 right-0 bg-indigo-600 text-white p-2 rounded-full shadow-md cursor-pointer hover:bg-indigo-700 transition-colors" title="Upload Photo">
                    <i class="fas fa-camera"></i>
                    <input type="file" id="profile_image_input" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(this)">
                </label>
            </div>
            <p class="text-sm font-semibold text-gray-600 mt-2">Upload Student Photo</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Current Class</label>
                <select name="current_class" id="current_class" onchange="toggleFields()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Class</option>
                    <?php
                    $classes = $db->getClassNames();
                    foreach ($classes as $c) {
                        $selected = ($student && $student['current_class'] == $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">GR No</label>
                <?php 
                $defaultGr = ($student) ? htmlspecialchars($student['gr_no']) : $db->getNextGrNo();
                ?>
                <input type="text" name="gr_no" id="gr_no_input" value="<?php echo $defaultGr; ?>" placeholder="e.g. 573" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Admission Date</label>
                <input type="date" name="admission_date" id="admission_date" value="<?php echo $student ? date('Y-m-d', strtotime($student['admission_date'])) : date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">CLASS INTO WHICH ADMITTED</label>
                <select name="admission_class" id="admission_class" onchange="toggleFields()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Class</option>
                    <?php
                    foreach ($classes as $c) {
                        $selected = ($student && ($student['admission_class'] ?? '') == $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Dynamic Section: Previous School Info -->
            <div id="previousSchoolSection" class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-4 mt-2 hidden">
                <div class="col-span-full">
                    <h3 class="text-lg font-semibold text-gray-800">Previous School Information</h3>
                </div>
                <div class="flex flex-col space-y-2">
                    <label class="text-sm font-medium text-gray-700">Previous School Name</label>
                    <input type="text" name="previous_school" id="previous_school" value="<?php echo $student ? htmlspecialchars($student['previous_school'] ?? '') : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                </div>
                <div class="flex flex-col space-y-2">
                    <label class="text-sm font-medium text-gray-700">SLC Image</label>
                    <input type="file" name="slc_img" id="slc_img" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
            </div>

            <!-- Removed duplicated Profile Image upload from here -->

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Student Name</label>
                <input type="text" name="student_name" value="<?php echo $student ? htmlspecialchars($student['student_name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2 relative">
                <label class="text-sm font-medium text-gray-700">Father's Name</label>
                <input type="text" name="father_name" id="father_name" value="<?php echo $student ? htmlspecialchars($student['father_name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out" placeholder="Type to search..." autocomplete="off">
                <div id="father_suggestions" class="absolute z-[100] top-full left-0 right-0 bg-white border border-gray-200 rounded-md shadow-xl mt-1 hidden max-h-60 overflow-y-auto"></div>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father's CNIC</label>
                <input type="text" name="father_cnic" id="father_cnic" value="<?php echo $student ? formatCnic($student['father_cnic']) : ''; ?>" placeholder="xxxxx-xxxxxxx-x" maxlength="15" class="cnic-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                <small id="cnic_status" class="text-xs text-gray-500"></small>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Gender</label>
                <select name="gender" id="gender" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($student && $student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($student && $student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo $student ? date('Y-m-d', strtotime($student['date_of_birth'])) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Date of Birth (in words)</label>
                <input type="text" id="dob_words" readonly disabled class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 italic font-medium cursor-not-allowed">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">B-Form No</label>
                <input type="text" name="b_form_no" id="b_form_no" value="<?php echo $student ? formatCnic($student['b_form_no']) : ''; ?>" placeholder="xxxxx-xxxxxxx-x" maxlength="15" class="cnic-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father's Contact</label>
                <input type="text" name="father_contact" id="father_contact" value="<?php echo $student ? formatContact($student['father_contact']) : ''; ?>" placeholder="xxxx-xxxxxxx" maxlength="12" class="contact-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">District</label>
                <input type="text" name="district" id="district" value="<?php echo $student ? htmlspecialchars($student['district']) : 'Tando Allahyar'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Taluka</label>
                <input type="text" name="taluka" id="taluka" value="<?php echo $student ? htmlspecialchars($student['taluka']) : 'Tando Allahyar'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Caste <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="text" name="caste" id="caste" value="<?php echo $student ? htmlspecialchars($student['caste'] ?? '') : ''; ?>" placeholder="e.g. Rajput" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Religion <span class="text-xs text-gray-500">(Optional)</span></label>
                <select name="religion" id="religion" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Religion</option>
                    <option value="Islam" <?php echo ($student && isset($student['religion']) && $student['religion'] == 'Islam') ? 'selected' : ''; ?>>Islam</option>
                    <option value="Hinduism" <?php echo ($student && isset($student['religion']) && $student['religion'] == 'Hinduism') ? 'selected' : ''; ?>>Hinduism</option>
                    <option value="Christianity" <?php echo ($student && isset($student['religion']) && $student['religion'] == 'Christianity') ? 'selected' : ''; ?>>Christianity</option>
                    <option value="Other" <?php echo ($student && isset($student['religion']) && $student['religion'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Place of Birth <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="text" name="place_of_birth" id="place_of_birth" value="<?php echo $student ? htmlspecialchars($student['place_of_birth'] ?? '') : ''; ?>" placeholder="City/Village Name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2 lg:col-span-2">
                <label class="text-sm font-medium text-gray-700">School Name</label>
                <input type="text" name="school_name" id="school_name" value="<?php echo $student ? htmlspecialchars($student['school_name']) : htmlspecialchars($formSettings['school_name'] ?? 'GBPS Ali Bux Jarwar'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>



            <?php if ($student && (isset($student['student_status']) && $student['student_status'] === 'Alumni') || isset($_GET['restore'])): ?>
            <div class="col-span-full bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h4 class="font-bold text-yellow-800 mb-2"> <i class="fas fa-exclamation-triangle"></i> Account Status</h4>
                <div class="flex flex-col space-y-2">
                    <label class="text-sm font-medium text-gray-700">Student Status</label>
                    <select name="student_status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Active" <?php echo (isset($_GET['restore'])) ? 'selected' : ''; ?>>Active (Restore Student)</option>
                        <option value="Alumni" <?php echo (!isset($_GET['restore'])) ? 'selected' : ''; ?>>Alumni (Graduated/Left)</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documents -->
            <div class="col-span-full border-t pt-4 mt-2">
                <h3 class="text-lg font-semibold text-gray-800">Document Uploads</h3>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father CNIC (Front)</label>
                <input type="file" name="father_cnic_front" id="father_cnic_front" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="father_cnic_front_preview" class="mt-2 text-xs">
                    <?php if ($student && !empty($student['father_cnic_front'])): ?>
                        <a href="<?php echo $student['father_cnic_front']; ?>" target="_blank" class="text-indigo-600 hover:underline">View Existing Front</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father CNIC (Back)</label>
                <input type="file" name="father_cnic_back" id="father_cnic_back" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="father_cnic_back_preview" class="mt-2 text-xs">
                    <?php if ($student && !empty($student['father_cnic_back'])): ?>
                        <a href="<?php echo $student['father_cnic_back']; ?>" target="_blank" class="text-indigo-600 hover:underline">View Existing Back</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">B-Form / CRC Image</label>
                <input type="file" name="b_form_img" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <?php if ($student && !empty($student['b_form_img'])): ?>
                    <small class="text-gray-500">Existing: <a href="<?php echo $student['b_form_img']; ?>" target="_blank" class="text-indigo-600 hover:underline">View</a></small>
                <?php endif; ?>
            </div>

        </div>

        <div class="mt-8">
            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-md hover:bg-indigo-700 transition duration-300 shadow-lg text-lg">
                <?php echo $id ? 'Update Student Record' : 'Submit Admission Form'; ?>
            </button>
        </div>
    </form>
</div>


<script>
    const errorMessage = <?php echo json_encode($error); ?>;
    if (errorMessage) {
        document.addEventListener('DOMContentLoaded', () => showModal('error', 'Error', errorMessage));
    }

    // Helper functions for formatting and number to words
    function formatCnicJS(value) {
        let val = value.replace(/\D/g, '');
        if (val.length > 13) val = val.substring(0, 13);
        if (val.length > 12) {
            return val.substring(0, 5) + '-' + val.substring(5, 12) + '-' + val.substring(12, 13);
        } else if (val.length > 5) {
            return val.substring(0, 5) + '-' + val.substring(5);
        }
        return val;
    }

    function formatContactJS(value) {
        let val = value.replace(/\D/g, '');
        if (val.length > 11) val = val.substring(0, 11);
        if (val.length > 4) {
            return val.substring(0, 4) + '-' + val.substring(4);
        }
        return val;
    }

    function numberToWords(n) {
        const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if (n < 20) return units[n];
        
        const digit = n % 10;
        if (n < 100) return tens[Math.floor(n / 10)] + (digit ? ' ' + units[digit] : '');
        
        if (n < 1000) return units[Math.floor(n / 100)] + ' Hundred' + (n % 100 == 0 ? '' : ' and ' + numberToWords(n % 100));
        
        return numberToWords(Math.floor(n / 1000)) + ' Thousand ' + (n % 1000 != 0 ? numberToWords(n % 1000) : '');
    }

    /**
     * MASTER HANDLER FOR DYNAMIC FIELDS
     * Controls Visibility and Required status based on selected classes
     */
    function handleStudentFormFields() {
        // Core Elements
        const currentClassSel = document.getElementById('current_class');
        const admissionClassSel = document.getElementById('admission_class');
        const prevSchoolSection = document.getElementById('previousSchoolSection');
        const slcImgInput = document.getElementById('slc_img');
        const grNoInput = document.getElementById('gr_no_input');

        if (!currentClassSel || !admissionClassSel || !prevSchoolSection) return;

        // 1. PREVIOUS SCHOOL VISIBILITY LOGIC
        const admissionVal = admissionClassSel.value.toLowerCase().trim();
        const isEmpty = admissionVal === '';
        
        // Hide if: Empty OR contains "one" OR contains "nursery" OR contains "01"
        const isExcluded = admissionVal.includes('one') || admissionVal.includes('nursery') || admissionVal.includes('01');

        if (!isEmpty && !isExcluded) {
            // SHOW SECTION
            prevSchoolSection.classList.remove('hidden');
            prevSchoolSection.style.display = 'grid'; // Force grid if hidden class fails
            if (slcImgInput) {
                slcImgInput.required = false; // Changed to false
                const label = slcImgInput.previousElementSibling;
                if (label) label.innerHTML = 'SLC Image'; // Removed asterisk
            }
        } else {
            // HIDE SECTION
            prevSchoolSection.classList.add('hidden');
            prevSchoolSection.style.display = 'none';
            if (slcImgInput) {
                slcImgInput.required = false;
                const label = slcImgInput.previousElementSibling;
                if (label) label.innerHTML = 'SLC Image';
            }
        }

        // 2. GR NO & DYNAMIC REQUIREMENTS LOGIC
        const classList = <?php echo json_encode($db->getClasses()); ?> || [];
        const currentVal = currentClassSel.value.trim();
        const foundClass = classList.find(c => c.class_name === currentVal);
        
        // If class not found, default to GR Required: true
        const isGrRequired = (foundClass) ? (parseInt(foundClass.is_gr_required) === 1) : true;

        // Handle GR Number Input
        if (grNoInput) {
            const grLabel = grNoInput.previousElementSibling;
            if (!isGrRequired) {
                grNoInput.required = false;
                grNoInput.disabled = true;
                grNoInput.value = '';
                if (grLabel) grLabel.innerHTML = 'GR No <span class="text-gray-400 text-xs">(Not Required for ' + currentVal + ')</span>';
            } else {
                grNoInput.disabled = false;
                grNoInput.required = false; // Changed to false
                if (grNoInput.value === '') {
                    grNoInput.value = <?php echo json_encode($defaultGr); ?> || '';
                }
                if (grLabel) grLabel.innerHTML = 'GR No'; // Removed asterisk
            }
        }

        // Handle Mandatory Fields based on GR Requirement
        const fieldsToValidate = [
            { id: 'father_cnic', name: "Father's CNIC" },
            { id: 'gender', name: "Gender" },
            { id: 'date_of_birth', name: "Date of Birth" },
            { id: 'admission_date', name: "Admission Date" },
            { id: 'admission_class', name: "CLASS INTO WHICH ADMITTED" },
            { id: 'father_contact', name: "Father's Contact" },
            { id: 'b_form_no', name: "B-Form No" },
            { id: 'district', name: "District" },
            { id: 'taluka', name: "Taluka" },
            { id: 'school_name', name: "School Name" }
        ];

        fieldsToValidate.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                element.required = false; // Always false as per request
                const label = element.previousElementSibling;
                if (label && label.tagName === 'LABEL') {
                    // label.innerHTML = `${field.name} ${isGrRequired ? '<span class="text-red-500">*</span>' : ''}`;
                    label.innerHTML = `${field.name}`;
                }
            }
        });
    }

    // Global toggle mapping
    window.toggleFields = handleStudentFormFields;

    document.addEventListener('DOMContentLoaded', () => {
        // Initial setup
        handleStudentFormFields();

        // 3. PARENT AUTOFILL LOGIC
        const cnicInput = document.getElementById('father_cnic');
        const fatherNameInput = document.getElementById('father_name');
        const fatherContactInput = document.getElementById('father_contact');
        const cnicFrontInput = document.getElementById('father_cnic_front');
        const cnicBackInput = document.getElementById('father_cnic_back');
        const cnicFrontPreview = document.getElementById('father_cnic_front_preview');
        const cnicBackPreview = document.getElementById('father_cnic_back_preview');
        const cnicStatus = document.getElementById('cnic_status');
        const fatherSuggestions = document.getElementById('father_suggestions');

        let debounceTimer;
        let lastRequestTime = 0;

        if (fatherNameInput && fatherSuggestions) {
            let nameSearchTimer;
            fatherNameInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 2) {
                    fatherSuggestions.classList.add('hidden');
                    return;
                }

                clearTimeout(nameSearchTimer);
                nameSearchTimer = setTimeout(() => {
                    fetch(`../api/search_parents.php?q=${encodeURIComponent(query)}`)
                        .then(response => {
                            if (!response.ok) throw new Error('API Error');
                            return response.json();
                        })
                        .then(data => {
                            if (data && data.length > 0) {
                                fatherSuggestions.innerHTML = '';
                                data.forEach(p => {
                                    const div = document.createElement('div');
                                    div.className = 'px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors duration-150';
                                    div.innerHTML = `
                                        <div class="font-bold text-gray-800 dark:text-gray-200">${p.father_name}</div>
                                        <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest">${p.father_cnic || 'No CNIC'}</div>
                                    `;
                                    div.onclick = () => {
                                        fatherNameInput.value = p.father_name;
                                        if (cnicInput) cnicInput.value = formatCnicJS(p.father_cnic);
                                        if (fatherContactInput) fatherContactInput.value = formatContactJS(p.father_contact);
                                        
                                        // Autofill Images
                                        if (p.father_cnic_front && cnicFrontInput && cnicFrontPreview) {
                                            cnicFrontInput.removeAttribute('required');
                                            cnicFrontPreview.innerHTML = `<small class="text-green-600 font-bold">✓ Existing Front: <a href="${p.father_cnic_front}" target="_blank" class="underline">View</a></small>
                                            <input type="hidden" name="existing_father_cnic_front" value="${p.father_cnic_front}">`;
                                        }
                                        if (p.father_cnic_back && cnicBackInput && cnicBackPreview) {
                                            cnicBackInput.removeAttribute('required');
                                            cnicBackPreview.innerHTML = `<small class="text-green-600 font-bold">✓ Existing Back: <a href="${p.father_cnic_back}" target="_blank" class="underline">View</a></small>
                                            <input type="hidden" name="existing_father_cnic_back" value="${p.father_cnic_back}">`;
                                        }

                                        fatherSuggestions.classList.add('hidden');
                                        if (cnicStatus) {
                                            cnicStatus.textContent = '✓ Existing Parent - Details autofilled';
                                            cnicStatus.className = 'text-[10px] text-green-600 font-bold uppercase';
                                        }
                                    };
                                    fatherSuggestions.appendChild(div);
                                });
                                fatherSuggestions.classList.remove('hidden');
                            } else {
                                fatherSuggestions.classList.add('hidden');
                            }
                        })
                        .catch(err => {
                            console.error('Name search error:', err);
                            fatherSuggestions.classList.add('hidden');
                        });
                }, 300);
            });

            document.addEventListener('click', (e) => {
                if (!fatherNameInput.contains(e.target) && !fatherSuggestions.contains(e.target)) {
                    fatherSuggestions.classList.add('hidden');
                }
            });
        }

        function formatCnicJS(value) {
            let val = value.replace(/\D/g, '');
            if (val.length > 13) val = val.substring(0, 13);
            if (val.length > 12) {
                return val.substring(0, 5) + '-' + val.substring(5, 12) + '-' + val.substring(12, 13);
            } else if (val.length > 5) {
                return val.substring(0, 5) + '-' + val.substring(5);
            }
            return val;
        }

        function formatContactJS(value) {
            let val = value.replace(/\D/g, '');
            if (val.length > 11) val = val.substring(0, 11);
            if (val.length > 4) {
                return val.substring(0, 4) + '-' + val.substring(4);
            }
            return val;
        }

        function lookupParent(type, value) {
            if (value.length < (type === 'cnic' ? 5 : 3)) {
                cnicStatus.textContent = '';
                return;
            }

            cnicStatus.textContent = 'Checking...';
            cnicStatus.className = 'text-xs text-gray-500';
            
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const currentRequestTime = Date.now();
                lastRequestTime = currentRequestTime;

                fetch(`../api/get_parent.php?${type}=${encodeURIComponent(value)}`)
                    .then(response => {
                        if (lastRequestTime !== currentRequestTime) return null;
                        if (response.ok) return response.json();
                        throw new Error('Parent not found');
                    })
                    .then(data => {
                        if (!data) return;
                        cnicStatus.textContent = '✓ Existing Parent - Details autofilled';
                        cnicStatus.className = 'text-[10px] text-green-600 font-bold uppercase';
                        
                        // Autofill fields
                        if (data.father_name && type === 'cnic') fatherNameInput.value = data.father_name;
                        if (data.father_cnic && type === 'name') cnicInput.value = formatCnicJS(data.father_cnic);
                        if (data.father_contact) fatherContactInput.value = formatContactJS(data.father_contact);
                        
                        // Handle Images
                        if (data.father_cnic_front) {
                            cnicFrontInput.removeAttribute('required');
                            cnicFrontPreview.innerHTML = `<small class="text-green-600 font-bold">✓ Existing Front: <a href="${data.father_cnic_front}" target="_blank" class="underline">View</a></small>
                            <input type="hidden" name="existing_father_cnic_front" value="${data.father_cnic_front}">`;
                        }
                        
                        if (data.father_cnic_back) {
                            cnicBackInput.removeAttribute('required');
                            cnicBackPreview.innerHTML = `<small class="text-green-600 font-bold">✓ Existing Back: <a href="${data.father_cnic_back}" target="_blank" class="underline">View</a></small>
                            <input type="hidden" name="existing_father_cnic_back" value="${data.father_cnic_back}">`;
                        }
                    })
                    .catch(error => {
                        if (lastRequestTime !== currentRequestTime) return;
                        cnicStatus.textContent = '';
                        <?php if (!$id): ?>
                        cnicFrontPreview.innerHTML = '';
                        cnicBackPreview.innerHTML = '';
                        <?php endif; ?>
                    });
            }, 500);
        }

        cnicInput.addEventListener('input', function() {
            lookupParent('cnic', this.value.trim());
        });

        // Blurred lookup removed for name as suggestions handle it now, but we can keep it as backup
        // Or just let suggestions handle it. Let's keep the cnic one.

        // CNIC Formatting Logic
        const cnicInputs = document.querySelectorAll('.cnic-input');
        cnicInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                
                if (value.length > 13) {
                    value = value.substring(0, 13);
                }

                if (value.length > 12) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 12) + '-' + value.substring(12, 13);
                } else if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5);
                }
                
                e.target.value = value;
            });
        });

        const contactInputs = document.querySelectorAll('.contact-input');
        contactInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }

                if (value.length > 4) {
                    value = value.substring(0, 4) + '-' + value.substring(4);
                }
                
                e.target.value = value;
            });
        });

        // Date of Birth to Words Logic
        const dobInput = document.getElementById('date_of_birth');
        const dobWordsInput = document.getElementById('dob_words');

        function numberToWords(n) {
            const string = n.toString();
            const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
            const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            if (n < 20) return units[n];
            
            const digit = n % 10;
            if (n < 100) return tens[Math.floor(n / 10)] + (digit ? ' ' + units[digit] : '');
            
            if (n < 1000) return units[Math.floor(n / 100)] + ' Hundred' + (n % 100 == 0 ? '' : ' and ' + numberToWords(n % 100));
            
            return numberToWords(Math.floor(n / 1000)) + ' Thousand ' + (n % 1000 != 0 ? numberToWords(n % 1000) : '');
        }

        function getDayWord(d) {
            const days = [
                '', 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth',
                'Eleventh', 'Twelfth', 'Thirteenth', 'Fourteenth', 'Fifteenth', 'Sixteenth', 'Seventeenth', 'Eighteenth', 'Nineteenth', 'Twentieth',
                'Twenty-First', 'Twenty-Second', 'Twenty-Third', 'Twenty-Fourth', 'Twenty-Fifth', 'Twenty-Sixth', 'Twenty-Seventh', 'Twenty-Eighth', 'Twenty-Ninth', 'Thirtieth', 'Thirty-First'
            ];
            return days[d] || '';
        }

        function getMonthWord(m) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            return months[m] || '';
        }

        function convertDobToWords() {
            const dateVal = dobInput.value;
            if (!dateVal) {
                dobWordsInput.value = '';
                return;
            }

            const date = new Date(dateVal);
            const day = date.getDate();
            const month = date.getMonth(); // 0-indexed
            const year = date.getFullYear();

            const dayStr = getDayWord(day);
            const monthStr = getMonthWord(month);
            
            // Year: 2025 -> Two Thousand Twenty Five
            // Usually simple numberToWords works, but let's be specific for years like 1999 (Nineteen Ninety Nine) vs 2000+ (Two Thousand...)
            // Users often prefer "Two Thousand Twenty". numberToWords(2020) -> "Two Thousand Twenty". Correct.
            const yearStr = numberToWords(year);

            dobWordsInput.value = `${dayStr} of ${monthStr} ${yearStr}`;
        }

        dobInput.addEventListener('change', convertDobToWords);
        // Run on load if value exists
        if (dobInput.value) convertDobToWords();
    });

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile_preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
