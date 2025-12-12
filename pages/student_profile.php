<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
$db = new Database();

$id = isset($_GET['id']) ? $_GET['id'] : null;
$student = null;

if ($id) {
    $student = $db->getStudent($id);
}

if (!$student) {
    header("Location: students.php");
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
    <div class="text-center md:text-left">
        <h1 class="text-2xl font-bold">Student Profile</h1>
        <p class="text-green-100 mt-1">Detailed student information</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <button onclick="window.print()" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium">
            <i class="fas fa-print"></i> Print Profile
        </button>
        <a href="students.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="bg-white shadow-lg rounded-lg p-6 max-w-7xl mx-auto print:hidden">
    <div class="flex flex-col md:flex-row gap-6">
        <!-- Profile Image & Basic Info -->
        <div class="w-full md:w-1/4 text-center">
            <div class="profile-image-container mb-4">
                <?php if (!empty($student['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Image" class="rounded-lg shadow-md w-full h-auto max-h-[300px] object-cover mx-auto">
                <?php else: ?>
                    <div class="bg-gray-200 rounded-lg flex items-center justify-center h-[200px] w-full mx-auto">
                        <i class="fas fa-user fa-4x text-gray-400"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h2 class="text-xl font-bold"><?php echo htmlspecialchars($student['student_name']); ?></h2>
            <p class="text-gray-500">GR No: <?php echo htmlspecialchars($student['gr_no']); ?></p>
            <p class="text-blue-600 font-semibold">Class: <?php echo htmlspecialchars($student['current_class']); ?></p>
            
            <div class="mt-4">
                <a href="student_form.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm w-full mb-2">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="w-full md:w-3/4">
            <h3 class="mb-4 border-b pb-2 text-lg font-semibold">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div><strong>Father Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></div>
                <div><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></div>
                <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($student['date_of_birth']); ?></div>
                <div><strong>Age:</strong> <?php echo htmlspecialchars($student['age']); ?> years</div>
                <div><strong>Admission Date:</strong> <?php echo htmlspecialchars($student['admission_date']); ?></div>
                <div><strong>B-Form No:</strong> <?php echo formatCnic($student['b_form_no']); ?></div>
                <div><strong>Father CNIC:</strong> <?php echo formatCnic($student['father_cnic']); ?></div>
                <div><strong>Father Contact:</strong> <?php echo formatContact($student['father_contact']); ?></div>
                <div><strong>District:</strong> <?php echo htmlspecialchars($student['district']); ?></div>
                <div><strong>Taluka:</strong> <?php echo htmlspecialchars($student['taluka']); ?></div>
                <div><strong>School:</strong> <?php echo htmlspecialchars($student['school_name']); ?></div>
                <div><strong>SEMIS Code:</strong> <?php echo htmlspecialchars($student['semis_code']); ?></div>
                <?php if (!empty($student['previous_school'])): ?>
                    <div class="col-span-full mt-2 p-3 bg-gray-50 rounded border border-gray-200">
                        <strong>Previous School:</strong> <?php echo htmlspecialchars($student['previous_school']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <h3 class="mb-4 border-b pb-2 text-lg font-semibold mt-8">Documents</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Father CNIC Front -->
                <div class="document-card">
                    <p class="font-semibold mb-2 text-center">Father CNIC (Front)</p>
                    <?php if (!empty($student['father_cnic_front']) && file_exists($student['father_cnic_front'])): ?>
                        <a href="<?php echo htmlspecialchars($student['father_cnic_front']); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($student['father_cnic_front']); ?>" class="rounded shadow-sm border hover:shadow-md transition-shadow w-full h-[150px] object-cover">
                        </a>
                    <?php else: ?>
                        <div class="bg-gray-100 rounded border flex items-center justify-center text-gray-400 h-[150px]">
                            No Image
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Father CNIC Back -->
                <div class="document-card">
                    <p class="font-semibold mb-2 text-center">Father CNIC (Back)</p>
                    <?php if (!empty($student['father_cnic_back']) && file_exists($student['father_cnic_back'])): ?>
                        <a href="<?php echo htmlspecialchars($student['father_cnic_back']); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($student['father_cnic_back']); ?>" class="rounded shadow-sm border hover:shadow-md transition-shadow w-full h-[150px] object-cover">
                        </a>
                    <?php else: ?>
                        <div class="bg-gray-100 rounded border flex items-center justify-center text-gray-400 h-[150px]">
                            No Image
                        </div>
                    <?php endif; ?>
                </div>

                <!-- B-Form -->
                <div class="document-card">
                    <p class="font-semibold mb-2 text-center">B-Form / CRC</p>
                    <?php if (!empty($student['b_form_img']) && file_exists($student['b_form_img'])): ?>
                        <a href="<?php echo htmlspecialchars($student['b_form_img']); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($student['b_form_img']); ?>" class="rounded shadow-sm border hover:shadow-md transition-shadow w-full h-[150px] object-cover">
                        </a>
                    <?php else: ?>
                        <div class="bg-gray-100 rounded border flex items-center justify-center text-gray-400 h-[150px]">
                            No Image
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SLC Image -->
                <?php if (!empty($student['slc_img'])): ?>
                <div class="document-card">
                    <p class="font-semibold mb-2 text-center">School Leaving Certificate</p>
                    <?php if (!empty($student['slc_img'])): ?>
                        <a href="<?php echo htmlspecialchars($student['slc_img']); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($student['slc_img']); ?>" class="rounded shadow-sm border hover:shadow-md transition-shadow w-full h-[150px] object-cover">
                        </a>
                    <?php else: ?>
                        <div class="bg-gray-100 rounded border flex items-center justify-center text-gray-400 h-[150px]">
                            File Missing
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

<style>
    @media print {
        @page {
            margin: 0;
            size: A4;
        }
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<!-- Print visibility control -->
<div class="print:hidden">
    <?php include '../includes/footer.php'; ?>
</div>

<!-- Certificate Container (Visible only in print) -->
<div class="hidden print:block print:fixed print:inset-0 print:w-full print:h-full print:bg-white print:z-[99999] print:p-[0.5cm] font-sans">
    <div class="border-4 border-black p-8 h-full relative">
        <!-- Header -->
        <div class="flex justify-between items-start mb-12">
            <div class="flex items-center gap-6">
                <img src="../GBPS_LOGO.png" alt="Logo" class="w-28 h-28 object-contain">
                <div>
                    <h1 class="text-3xl font-extrabold uppercase tracking-wide text-black leading-tight">Government Boys Primary<br>School</h1>
                    <h2 class="text-2xl font-bold mt-2 text-black">Ali Bux Jarwar</h2>
                    <p class="text-lg mt-1 text-gray-800 font-medium">Taluka & District Tando Allahyar</p>
                </div>
            </div>
            <!-- Photo Box -->
            <div class="w-40 h-48 border-2 border-black flex items-center justify-center overflow-hidden bg-gray-50">
                <?php if (!empty($student['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-sm text-gray-400 font-medium">Paste Photo</span>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="text-center text-4xl font-bold underline mb-12 uppercase tracking-wider">Student Profile</h2>

        <!-- Fields -->
        <div class="space-y-8 text-xl">
            <!-- Row 1 -->
            <div class="flex gap-8">
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-24 text-black">Name:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium capitalize text-black"><?php echo htmlspecialchars($student['student_name']); ?></div>
                </div>
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-36 text-black">Father Name:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium capitalize text-black"><?php echo htmlspecialchars($student['father_name']); ?></div>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="flex gap-8">
                <div class="w-1/3 flex items-end">
                    <span class="font-bold w-20 text-black">Class:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium capitalize text-black"><?php echo htmlspecialchars($student['current_class']); ?></div>
                </div>
                <div class="w-1/3 flex items-end">
                    <span class="font-bold w-20 text-black">GR No:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo htmlspecialchars($student['gr_no']); ?></div>
                </div>
                <div class="w-1/3 flex items-end">
                    <span class="font-bold w-16 text-black">Age:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo htmlspecialchars($student['age']); ?> Years</div>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="flex gap-8">
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-36 text-black">Date of Birth:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo htmlspecialchars($student['date_of_birth']); ?></div>
                </div>
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-40 text-black">Admission Date:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo htmlspecialchars($student['admission_date']); ?></div>
                </div>
            </div>

            <!-- Row 4 -->
            <div class="flex gap-8">
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-32 text-black">B-Form No:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo formatCnic($student['b_form_no']); ?></div>
                </div>
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-32 text-black">Father CNIC:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo formatCnic($student['father_cnic']); ?></div>
                </div>
            </div>

            <!-- Row 5 -->
            <div class="flex gap-8">
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-24 text-black">Contact:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium text-black"><?php echo formatContact($student['father_contact']); ?></div>
                </div>
                <div class="flex-1 flex items-end">
                    <span class="font-bold w-24 text-black">Address:</span>
                    <div class="flex-1 border-b-2 border-black px-2 pb-1 font-medium capitalize truncate text-black"><?php echo htmlspecialchars($student['district']) . ', ' . htmlspecialchars($student['taluka']); ?></div>
                </div>
            </div>
        </div>

        <!-- Documents (Compact) -->
        <div class="mt-12">
            <p class="font-bold mb-4 text-xl text-black">Attached Documents:</p>
            <div class="flex gap-6 h-32">
                <?php if (!empty($student['father_cnic_front'])): ?>
                    <img src="<?php echo htmlspecialchars($student['father_cnic_front']); ?>" class="h-full border-2 border-gray-300 rounded-sm">
                <?php endif; ?>
                <?php if (!empty($student['father_cnic_back'])): ?>
                    <img src="<?php echo htmlspecialchars($student['father_cnic_back']); ?>" class="h-full border-2 border-gray-300 rounded-sm">
                <?php endif; ?>
                <?php if (!empty($student['b_form_img'])): ?>
                    <img src="<?php echo htmlspecialchars($student['b_form_img']); ?>" class="h-full border-2 border-gray-300 rounded-sm">
                <?php endif; ?>
            </div>
        </div>

        <!-- Signature -->
        <div class="absolute bottom-12 right-12">
            <div class="border-t-2 border-black w-72 pt-2 text-center">
                <p class="font-bold text-xl text-black">Headmaster Signature</p>
            </div>
        </div>
    </div>
</div>
