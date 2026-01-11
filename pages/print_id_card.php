<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 max-w-md mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-id-badge text-indigo-600"></i> Print Identity Card
        </h2>

        <form action="generate_id_card.php" method="GET" target="_blank" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Student GR Number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                        <i class="fas fa-hashtag"></i>
                    </span>
                    <input type="number" name="gr_no" placeholder="Enter GR No" required 
                        class="w-full pl-10 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <p class="text-xs text-gray-500 mt-1">Enter the unique GR Number of the student.</p>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                <p class="text-sm text-blue-600">
                    This will generate a printable PDF-style Identity Card for the specified student.
                </p>
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-print"></i> Generate Card
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
