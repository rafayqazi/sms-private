<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('student_form.php')) {
    header("Location: students.php");
    exit;
}

$db = new Database();

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
        $uploadDir = '../uploads/';
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
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    // Create unique filename: GR-123-field_name.jpg
                    $newFileName = 'GR-' . $grNo . '-' . $field . '.' . $fileExtension;
                    $dest_path = $uploadDir . $newFileName;

                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $uploadedFiles[$field] = $dest_path;
                    }
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
            'current_class' => $_POST['current_class'],
            'b_form_no' => str_replace('-', '', $_POST['b_form_no']),
            'father_cnic' => str_replace('-', '', $_POST['father_cnic']),
            'father_contact' => str_replace('-', '', $_POST['father_contact']),
            'district' => $_POST['district'],
            'taluka' => $_POST['taluka'],
            'school_name' => $_POST['school_name'],
            'semis_code' => $_POST['semis_code'],
            'semis_code' => $_POST['semis_code'],
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Section: Personal Details -->
            <div class="col-span-full border-b pb-2 mb-2">
                <h3 class="text-xl font-semibold text-gray-800">Personal Information</h3>
            </div>

            <!-- Profile Image Upload (Centered) -->
            <div class="col-span-full flex flex-col items-center justify-center mb-6">
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
                <p class="text-sm text-gray-500 mt-2">Upload Student Photo</p>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">GR No <span class="text-red-500">*</span></label>
                <?php 
                $defaultGr = '';
                if ($student) {
                    $defaultGr = htmlspecialchars($student['gr_no']);
                } else {
                    // Start of autofill logic
                    $defaultGr = $db->getNextGrNo();
                }
                ?>
                <input type="text" name="gr_no" id="gr_no_input" value="<?php echo $defaultGr; ?>" placeholder="e.g. 573" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>
            
            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Student Name <span class="text-red-500">*</span></label>
                <input type="text" name="student_name" required value="<?php echo $student ? htmlspecialchars($student['student_name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father's CNIC <span class="text-red-500">*</span></label>
                <input type="text" name="father_cnic" id="father_cnic" required value="<?php echo $student ? formatCnic($student['father_cnic']) : ''; ?>" placeholder="xxxxx-xxxxxxx-x" maxlength="15" class="cnic-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                <small id="cnic_status" class="text-xs text-gray-500"></small>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father's Name <span class="text-red-500">*</span></label>
                <input type="text" name="father_name" id="father_name" required value="<?php echo $student ? htmlspecialchars($student['father_name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                <select name="gender" id="gender" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($student && $student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($student && $student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo $student ? date('Y-m-d', strtotime($student['date_of_birth'])) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Date of Birth (in words)</label>
                <input type="text" id="dob_words" readonly disabled class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 italic font-medium cursor-not-allowed">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Admission Date <span class="text-red-500">*</span></label>
                <input type="date" name="admission_date" id="admission_date" required value="<?php echo $student ? date('Y-m-d', strtotime($student['admission_date'])) : date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Current Class <span class="text-red-500">*</span></label>
                <select name="current_class" id="current_class" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
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

            <!-- Dynamic Section: Previous School Info -->
            <div id="previousSchoolSection" class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-4 mt-2 hidden">
                <div class="col-span-full">
                    <h3 class="text-lg font-semibold text-gray-800">Previous School Information</h3>
                    <p class="text-sm text-gray-500">Required for classes other than Kachi and One</p>
                </div>
                
                <div class="flex flex-col space-y-2">
                    <label class="text-sm font-medium text-gray-700">Previous School Name</label>
                    <input type="text" name="previous_school" id="previous_school" value="<?php echo $student ? htmlspecialchars($student['previous_school'] ?? '') : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                </div>

                <div class="flex flex-col space-y-2">
                    <label class="text-sm font-medium text-gray-700">School Leaving Certificate (SLC)</label>
                    <input type="file" name="slc_img" id="slc_img" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <?php if ($student && !empty($student['slc_img'])): ?>
                        <small class="text-gray-500">Current: <a href="<?php echo $student['slc_img']; ?>" target="_blank" class="text-indigo-600 hover:underline">View</a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">B-Form No <span class="text-red-500">*</span></label>
                <input type="text" name="b_form_no" id="b_form_no" value="<?php echo $student ? formatCnic($student['b_form_no']) : ''; ?>" placeholder="xxxxx-xxxxxxx-x" maxlength="15" class="cnic-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>


            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father's Contact</label>
                <input type="text" name="father_contact" id="father_contact" value="<?php echo $student ? formatContact($student['father_contact']) : ''; ?>" placeholder="xxxx-xxxxxxx" maxlength="12" class="contact-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">District <span class="text-red-500">*</span></label>
                <input type="text" name="district" id="district" value="<?php echo $student ? htmlspecialchars($student['district']) : 'Tando Allahyar'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Taluka <span class="text-red-500">*</span></label>
                <input type="text" name="taluka" id="taluka" value="<?php echo $student ? htmlspecialchars($student['taluka']) : 'Tando Allahyar'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2 lg:col-span-2">
                <label class="text-sm font-medium text-gray-700">School Name <span class="text-red-500">*</span></label>
                <input type="text" name="school_name" id="school_name" value="<?php echo $student ? htmlspecialchars($student['school_name']) : 'GBPS Ali Bux Jarwar'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">SEMIS Code <span class="text-red-500">*</span></label>
                <input type="text" name="semis_code" id="semis_code" value="<?php echo $student ? htmlspecialchars($student['semis_code']) : '424010147'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <?php 
            // Show Status Field if Student is Alumni or we are restoring
            $isAlumni = ($student && isset($student['student_status']) && $student['student_status'] === 'Alumni');
            $isRestore = isset($_GET['restore']);
            
            if ($isAlumni || $isRestore): 
            ?>
            <div class="col-span-full bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h4 class="font-bold text-yellow-800 mb-2"> <i class="fas fa-exclamation-triangle"></i> Account Status</h4>
                <div class="flex flex-col space-y-2">
                    <label class="text-sm font-medium text-gray-700">Student Status</label>
                    <select name="student_status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Active" <?php echo ($isRestore) ? 'selected' : ''; ?>>Active (Restore Student)</option>
                        <option value="Alumni" <?php echo (!$isRestore && $isAlumni) ? 'selected' : ''; ?>>Alumni (Graduated/Left)</option>
                    </select>
                    <p class="text-xs text-gray-500">Select "Active" to restore this student to the main list.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section: Documents -->
            <div class="col-span-full border-b pb-2 mb-2 mt-6">
                <h3 class="text-xl font-semibold text-gray-800">Documents Upload</h3>
            </div>



            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father CNIC (Front)</label>
                <input type="file" name="father_cnic_front" id="father_cnic_front" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="father_cnic_front_preview" class="mt-2">
                    <?php if ($student && !empty($student['father_cnic_front'])): ?>
                        <small class="text-gray-500">Current: <a href="<?php echo $student['father_cnic_front']; ?>" target="_blank" class="text-indigo-600 hover:underline">View</a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father CNIC (Back)</label>
                <input type="file" name="father_cnic_back" id="father_cnic_back" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="father_cnic_back_preview" class="mt-2">
                    <?php if ($student && !empty($student['father_cnic_back'])): ?>
                        <small class="text-gray-500">Current: <a href="<?php echo $student['father_cnic_back']; ?>" target="_blank" class="text-indigo-600 hover:underline">View</a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">B-Form / CRC Image</label>
                <input type="file" name="b_form_img" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <?php if ($student && !empty($student['b_form_img'])): ?>
                    <small class="text-gray-500">Current: <a href="<?php echo $student['b_form_img']; ?>" target="_blank" class="text-indigo-600 hover:underline">View</a></small>
                <?php endif; ?>
            </div>

        </div>

        <div class="mt-8">
            <button type="submit" class="w-full bg-gradient-to-r from-primary to-green-800 text-white font-bold py-3 px-4 rounded-md hover:from-green-800 hover:to-green-900 transition duration-300 shadow-lg text-lg transform hover:-translate-y-0.5">
                <?php echo $id ? 'Update Student Record' : 'Submit Admission Form'; ?>
            </button>
        </div>
    </form>
</div>




<script>
    const errorMessage = <?php echo json_encode($error); ?>;

    if (errorMessage) {
        document.addEventListener('DOMContentLoaded', function() {
            showModal('error', 'Error', errorMessage);
        });
    }

    // Dynamic Fields Logic
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('current_class');
        const prevSchoolSection = document.getElementById('previousSchoolSection');
        const prevSchoolInput = document.getElementById('previous_school');
        // Note: File inputs cannot be set to required dynamically in a simple way that works across all browsers/validation styles, 
        // but we can enforce it server-side or just visually show it. For now, we'll just toggle visibility.

        const classes = <?php echo json_encode($db->getClasses()); ?>;
        const lowClasses = classes.slice(0, 2).map(c => c.class_name); // First two classes for previous school logic

        const grNoInput = document.getElementById('gr_no_input');
        const grNoLabel = grNoInput.previousElementSibling;
        const initialGrNo = <?php echo json_encode($defaultGr); ?>;

        function toggleFields() {
            const selectedClass = classSelect.value;
            if (selectedClass && !lowClasses.includes(selectedClass)) {
                prevSchoolSection.classList.remove('hidden');
            } else {
                prevSchoolSection.classList.add('hidden');
            }

            // GR No Requirement Logic
            const selectedClassData = classes.find(c => c.class_name === selectedClass);
            const isGrRequired = selectedClassData ? parseInt(selectedClassData.is_gr_required) === 1 : true;

            // Fields that depend on GR requirement
            const dynamicFields = [
                { id: 'father_cnic', name: "Father's CNIC" },
                { id: 'gender', name: "Gender" },
                { id: 'date_of_birth', name: "Date of Birth" },
                { id: 'admission_date', name: "Admission Date" },
                { id: 'father_contact', name: "Father's Contact" },
                { id: 'b_form_no', name: "B-Form No" },
                { id: 'district', name: "District" },
                { id: 'taluka', name: "Taluka" },
                { id: 'school_name', name: "School Name" },
                { id: 'semis_code', name: "SEMIS Code" }
            ];

            // 1. Handle GR No Field specifically
            if (!isGrRequired) {
                grNoInput.required = false;
                grNoInput.disabled = true;
                grNoInput.value = '';
                grNoLabel.innerHTML = 'GR No <span class="text-gray-400 text-xs">(Not Required for ' + selectedClass + ')</span>';
            } else {
                grNoInput.disabled = false;
                grNoInput.required = true;
                if (grNoInput.value === '') grNoInput.value = initialGrNo;
                grNoLabel.innerHTML = 'GR No <span class="text-red-500">*</span>';
            }

            // 2. Handle all other Dynamic Fields
            dynamicFields.forEach(field => {
                const el = document.getElementById(field.id);
                if (el) {
                    el.required = isGrRequired;
                    const label = el.previousElementSibling;
                    if (label && label.tagName === 'LABEL') {
                        label.innerHTML = `${field.name} ${isGrRequired ? '<span class="text-red-500">*</span>' : ''}`;
                    }
                }
            });
        }

        classSelect.addEventListener('change', toggleFields);
        
        // Run on load to set initial state (e.g. for edit mode)
        toggleFields();

        // Parent Autofill Logic
        const cnicInput = document.getElementById('father_cnic');
        const fatherNameInput = document.getElementById('father_name');
        const fatherContactInput = document.getElementById('father_contact');
        const cnicFrontInput = document.getElementById('father_cnic_front');
        const cnicBackInput = document.getElementById('father_cnic_back');
        const cnicFrontPreview = document.getElementById('father_cnic_front_preview');
        const cnicBackPreview = document.getElementById('father_cnic_back_preview');
        const cnicStatus = document.getElementById('cnic_status');

        let debounceTimer;
        let lastRequestTime = 0;

        cnicInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const cnic = this.value.trim();
            
            if (cnic.length < 5) {
                cnicStatus.textContent = '';
                return;
            }

            cnicStatus.textContent = 'Checking...';
            cnicStatus.className = 'text-xs text-gray-500'; // Reset color
            
            debounceTimer = setTimeout(() => {
                const currentRequestTime = Date.now();
                lastRequestTime = currentRequestTime;

                fetch(`../api/get_parent.php?cnic=${encodeURIComponent(cnic)}`)
                    .then(response => {
                        // If this is not the latest request, ignore the response completely
                        if (lastRequestTime !== currentRequestTime) return null;

                        if (response.ok) {
                            return response.json();
                        }
                        throw new Error('Parent not found');
                    })
                    .then(data => {
                        if (!data) return; // Request was outdated

                        cnicStatus.textContent = '✓ Existing Parent - Details autofilled';
                        cnicStatus.className = 'text-xs text-green-600 font-semibold';
                        
                        // Autofill fields
                        if (data.father_name) fatherNameInput.value = data.father_name;
                        if (data.father_contact) fatherContactInput.value = formatContact(data.father_contact);
                        
                        // Handle Images
                        if (data.father_cnic_front) {
                            cnicFrontInput.removeAttribute('required');
                            cnicFrontPreview.innerHTML = `<small class="text-green-600">Existing image found: <a href="${data.father_cnic_front}" target="_blank" class="underline">View</a></small>
                            <input type="hidden" name="existing_father_cnic_front" value="${data.father_cnic_front}">`;
                        }
                        
                        if (data.father_cnic_back) {
                            cnicBackInput.removeAttribute('required');
                            cnicBackPreview.innerHTML = `<small class="text-green-600">Existing image found: <a href="${data.father_cnic_back}" target="_blank" class="underline">View</a></small>
                            <input type="hidden" name="existing_father_cnic_back" value="${data.father_cnic_back}">`;
                        }
                    })
                    .catch(error => {
                        // If this is not the latest request, ignore the error
                        if (lastRequestTime !== currentRequestTime) return;

                        cnicStatus.textContent = 'New Parent (Not found in database)';
                        cnicStatus.className = 'text-xs text-gray-500';
                        
                        <?php if (!$id): ?>
                        cnicFrontPreview.innerHTML = '';
                        cnicBackPreview.innerHTML = '';
                        <?php endif; ?>
                    });
            }, 500); // 500ms debounce
        });

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
