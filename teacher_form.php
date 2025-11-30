<?php
require_once 'includes/auth_session.php';
require_once 'includes/db.php';
$db = new Database();

$editMode = false;
$teacher = null;
if (isset($_GET['edit'])) {
    $editMode = true;
    $teacher = $db->getTeacher($_GET['edit']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profileImage = '';
    // If editing and no new image, keep old one (handled in db update)
    // If new image uploaded, process it
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $uploadDir = 'uploads/teachers/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (in_array($fileExt, $allowed)) {
            $fileName = uniqid() . '.' . $fileExt;
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                $profileImage = $targetFile;
            }
        }
    }

    $data = [
        'name' => $_POST['name'],
        'father_name' => $_POST['father_name'],
        'gender' => $_POST['gender'],
        'cnic' => str_replace('-', '', $_POST['cnic']),
        'dob' => $_POST['dob'],
        'age' => $_POST['age'],
        'email' => $_POST['email'],
        'disability' => $_POST['disability'],
        'payment_type' => $_POST['payment_type'],
        'payment_no' => $_POST['payment_no'],
        'iban' => $_POST['iban'],
        'contact' => str_replace('-', '', $_POST['contact']),
        'retirement_date' => $_POST['retirement_date'],
        'designation' => '', // Removed field
        'department' => $_POST['department'],
        'posting' => $_POST['posting'],
        'basic_scale' => $_POST['basic_scale'],
        'address' => $_POST['address'],
        'district' => $_POST['district'],
        'tahsil' => $_POST['tahsil']
    ];

    if ($profileImage) {
        $data['profile_image'] = $profileImage;
    }

    if ($editMode) {
        $db->updateTeacher($_GET['edit'], $data);
        header("Location: teacher_profile.php");
    } else {
        $data['profile_image'] = $profileImage; // Ensure it's set for new records
        $db->addTeacher($data);
        header("Location: teacher_profile.php");
    }
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-6xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden my-10">
    <div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold"><?php echo $editMode ? 'Edit Teacher Record' : 'Add New Teacher'; ?></h1>
            <p class="text-green-100 text-sm mt-1">Please fill in the details carefully</p>
        </div>
        <a href="teacher_profile.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center gap-2 text-sm font-medium">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="p-8" id="teacherForm">
        
        <!-- Personal Information -->
        <div class="border-b pb-2 mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Personal Information</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            
            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Profile Image</label>
                <input type="file" name="profile_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="<?php echo $editMode ? htmlspecialchars($teacher['name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Father Name <span class="text-red-500">*</span></label>
                <input type="text" name="father_name" required value="<?php echo $editMode ? htmlspecialchars($teacher['father_name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                <select name="gender" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($editMode && $teacher['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($editMode && $teacher['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">CNIC <span class="text-red-500">*</span></label>
                <input type="text" name="cnic" id="cnic" required placeholder="xxxxx-xxxxxxx-x" value="<?php echo $editMode ? formatCnic($teacher['cnic']) : ''; ?>" maxlength="15" class="cnic-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                <small id="cnic_error" class="text-red-500 text-xs hidden"></small>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                <input type="date" name="dob" id="dob" required value="<?php echo $editMode ? htmlspecialchars($teacher['dob']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Age (Auto-calculated)</label>
                <input type="text" name="age" id="age" readonly class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed" value="<?php echo $editMode ? htmlspecialchars($teacher['age']) : ''; ?>">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required value="<?php echo $editMode ? htmlspecialchars($teacher['email']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Disability</label>
                <select name="disability" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="No" <?php echo ($editMode && $teacher['disability'] == 'No') ? 'selected' : ''; ?>>No</option>
                    <option value="Yes" <?php echo ($editMode && $teacher['disability'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Contact No <span class="text-red-500">*</span></label>
                <input type="text" name="contact" required value="<?php echo $editMode ? formatContact($teacher['contact']) : ''; ?>" placeholder="xxxx-xxxxxxx" maxlength="12" class="contact-input w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2 lg:col-span-2">
                <label class="text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                <input type="text" name="address" required value="<?php echo $editMode ? htmlspecialchars($teacher['address']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">District <span class="text-red-500">*</span></label>
                <input type="text" name="district" required value="<?php echo $editMode ? htmlspecialchars($teacher['district']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Tahsil <span class="text-red-500">*</span></label>
                <input type="text" name="tahsil" required value="<?php echo $editMode ? htmlspecialchars($teacher['tahsil']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>
        </div>

        <!-- Professional & Financial Information -->
        <div class="border-b pb-2 mb-6 mt-8">
            <h3 class="text-xl font-semibold text-gray-800">Professional & Financial Information</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            
            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Digital Payment / Bank Account number</label>
                <select name="payment_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select Type</option>
                    <option value="Bank" <?php echo ($editMode && $teacher['payment_type'] == 'Bank') ? 'selected' : ''; ?>>Bank</option>
                    <option value="EasyPaisa" <?php echo ($editMode && $teacher['payment_type'] == 'EasyPaisa') ? 'selected' : ''; ?>>EasyPaisa</option>
                    <option value="JazzCash" <?php echo ($editMode && $teacher['payment_type'] == 'JazzCash') ? 'selected' : ''; ?>>JazzCash</option>
                </select>
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Account / Mobile No</label>
                <input type="text" name="payment_no" value="<?php echo $editMode ? htmlspecialchars($teacher['payment_no']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">IBAN</label>
                <input type="text" name="iban" value="<?php echo $editMode ? htmlspecialchars($teacher['iban']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Date of Retirement (Auto-calculated)</label>
                <input type="date" name="retirement_date" id="retirement_date" readonly class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed" value="<?php echo $editMode ? htmlspecialchars($teacher['retirement_date']) : ''; ?>">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Department</label>
                <input type="text" name="department" value="<?php echo $editMode ? htmlspecialchars($teacher['department']) : 'Sindh Education and Literacy Department'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Place of Posting</label>
                <input type="text" name="posting" value="<?php echo $editMode ? htmlspecialchars($teacher['posting']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
            </div>

            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">BPS (Pay Scale)</label>
                <select name="basic_scale" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                    <option value="">Select BPS</option>
                    <?php for($i = 1; $i <= 22; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($editMode && $teacher['basic_scale'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="mt-8">
            <button type="submit" id="submitBtn" class="w-full bg-gradient-to-r from-primary to-green-800 text-white font-bold py-3 px-4 rounded-md hover:from-green-800 hover:to-green-900 transition duration-300 shadow-lg text-lg transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                <?php echo $editMode ? 'Update Teacher Record' : 'Add New Teacher'; ?>
            </button>
        </div>
    </form>
</div>

<script>
    const dobInput = document.getElementById('dob');
    const ageInput = document.getElementById('age');
    const retirementInput = document.getElementById('retirement_date');

    dobInput.addEventListener('change', function() {
        const dob = new Date(this.value);
        if (isNaN(dob.getTime())) return;

        const today = new Date();
        
        // Calculate Age
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        ageInput.value = age;

        // Calculate Retirement Date (DOB + 60 years)
        const retirementDate = new Date(dob);
        retirementDate.setFullYear(dob.getFullYear() + 60);
        
        // Format to YYYY-MM-DD
        const yyyy = retirementDate.getFullYear();
        const mm = String(retirementDate.getMonth() + 1).padStart(2, '0');
        const dd = String(retirementDate.getDate()).padStart(2, '0');
        
        retirementInput.value = `${yyyy}-${mm}-${dd}`;
    });

    // CNIC Formatting and Real-time Validation
    const cnicInput = document.getElementById('cnic');
    const cnicError = document.getElementById('cnic_error');
    const submitBtn = document.getElementById('submitBtn');
    let cnicDebounceTimer;

    cnicInput.addEventListener('input', function(e) {
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

        // Real-time Validation
        clearTimeout(cnicDebounceTimer);
        const cnic = value;
        const editId = <?php echo $editMode ? $teacher['id'] : 'null'; ?>;

        if (cnic.length >= 15) { // Full CNIC entered
            cnicDebounceTimer = setTimeout(() => {
                fetch(`api/check_teacher_cnic.php?cnic=${encodeURIComponent(cnic)}&exclude_id=${editId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            cnicError.textContent = 'Error: This CNIC is already registered to another teacher.';
                            cnicError.classList.remove('hidden');
                            submitBtn.disabled = true;
                            cnicInput.classList.add('border-red-500');
                        } else {
                            cnicError.classList.add('hidden');
                            submitBtn.disabled = false;
                            cnicInput.classList.remove('border-red-500');
                        }
                    })
                    .catch(err => console.error('Error checking CNIC:', err));
            }, 500);
        } else {
            cnicError.classList.add('hidden');
            submitBtn.disabled = false;
            cnicInput.classList.remove('border-red-500');
        }
    });

    // Contact Formatting Logic
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
</script>

<?php include 'includes/footer.php'; ?>
