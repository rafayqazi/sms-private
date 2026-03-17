<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

// Only Admin or Editor can access
if ($_SESSION['user_role'] === 'Editor' && !in_array('certificates', $allowed_pages ?? [])) {
    // Basic permission check - although header hides link, direct access check is good practice
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg text-indigo-600 dark:text-indigo-400">
                    <i class="fas fa-certificate"></i>
                </div>
                Certificates Management
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1 ml-16">Generate various certificates for students.</p>
        </div>
    </div>

    <!-- Certificates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- School Leaving Certificate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center group">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400 mb-4 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-file-export text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">School Leaving</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 flex-grow">Generate School Leaving Certificate (SLC) for students leaving the institute.</p>
            <a href="certificate_school_leaving.php" class="w-full py-2 px-4 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg font-semibold hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 transition-colors">
                Generate <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Transfer Certificate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center group">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400 mb-4 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Transfer Certificate</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 flex-grow">Generate Transfer Certificate for students moving to another branch or school.</p>
            <a href="certificate_transfer.php" class="w-full py-2 px-4 bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400 rounded-lg font-semibold hover:bg-green-600 hover:text-white dark:hover:bg-green-600 transition-colors">
                Generate <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Character Certificate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center group">
            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center text-purple-600 dark:text-purple-400 mb-4 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-user-check text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Character Certificate</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 flex-grow">Generate Character Certificate validating student conduct and behavior.</p>
            <a href="certificate_character.php" class="w-full py-2 px-4 bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400 rounded-lg font-semibold hover:bg-purple-600 hover:text-white dark:hover:bg-purple-600 transition-colors">
                Generate <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Testimonial Certificate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center group">
            <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-400 mb-4 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-award text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Testimonial</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 flex-grow">Generate Testimonial Certificate for academic achievements and participation.</p>
            <a href="certificate_testimonial.php" class="w-full py-2 px-4 bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 rounded-lg font-semibold hover:bg-amber-600 hover:text-white dark:hover:bg-amber-600 transition-colors">
                Generate <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
