<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $id = $_POST['item_id'];
    if ($db->deleteInventory($id)) {
        $successMsg = "Record deleted permanently.";
    } else {
        $errorMsg = "Failed to delete record.";
    }
}

$deadStock = $db->getInventory(['status' => 'Dead Stock']);
?>

<div class="container mx-auto px-4 py-8 print:p-0">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 print:hidden">
        <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <div class="p-3 bg-red-100 rounded-lg text-red-600">
                <i class="fas fa-book-dead"></i>
            </div>
            Dead Stock Register
        </h2>
        
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-print"></i> Print Register
            </button>
            <a href="inventory.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (isset($successMsg)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2 print:hidden">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $successMsg; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMsg)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2 print:hidden">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $errorMsg; ?></span>
        </div>
    <?php endif; ?>

    <!-- Print Header with Watermark -->
    <div class="hidden print:block relative mb-8">
        <!-- Watermark -->
        <div class="absolute inset-0 z-0 flex items-center justify-center opacity-10 pointer-events-none overflow-hidden">
            <img src="../GBPS_LOGO.png" alt="Watermark" class="w-[500px] h-[500px] object-contain grayscale">
        </div>
        
        <!-- Header Content -->
        <div class="relative z-10 text-center border-b-2 border-black pb-4 mb-4">
            <div class="flex items-center justify-center gap-4 mb-2">
                <img src="../GBPS_LOGO.png" alt="Logo" class="w-20 h-20 object-contain">
                <div>
                    <h1 class="text-3xl font-bold uppercase tracking-wide text-black"><?php echo htmlspecialchars($headerSettings['school_name'] ?? 'School Name'); ?></h1>
                    <p class="text-sm font-semibold text-gray-800 mt-1 uppercase tracking-wider">
                        SEMIS CODE: <?php echo htmlspecialchars($headerSettings['semis_code'] ?? 'N/A'); ?> <span class="mx-2">|</span> <?php echo htmlspecialchars($headerSettings['address_tagline'] ?? ''); ?>
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <h2 class="text-2xl font-bold uppercase underline decoration-2 underline-offset-4">Dead Stock Register</h2>
                <p class="text-xs text-gray-500 mt-1">Generated on: <?php echo date('d F, Y'); ?></p>
            </div>
        </div>
    </div>

    <!-- Register Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden print:shadow-none print:border-none print:bg-transparent relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse border border-gray-200 print:border-black">
                <thead>
                    <tr class="bg-red-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-700 font-bold print:bg-gray-100 print:text-black print:border-black">
                        <th class="p-4 border border-gray-200 print:border-black text-center w-16">ID</th>
                        <th class="p-4 border border-gray-200 print:border-black w-48">Item Name</th>
                        <th class="p-4 border border-gray-200 print:border-black w-24">Purchase Date</th>
                        <th class="p-4 border border-gray-200 print:border-black w-24 text-right">Cost</th>
                        <th class="p-4 border border-gray-200 print:border-black w-32">Disposal Reason</th>
                        <th class="p-4 border border-gray-200 print:border-black w-24">Disposal Date</th>
                        <th class="p-4 border border-gray-200 print:border-black">Remarks / Cause</th>
                        <th class="p-4 border border-gray-200 print:hidden w-16 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 print:divide-black">
                    <?php if (empty($deadStock)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500 italic print:text-black border border-gray-200 print:border-black">
                                No records found in dead stock register.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deadStock as $item): ?>
                        <tr class="hover:bg-red-50 transition-colors print:hover:bg-transparent text-sm">
                            <td class="p-3 font-bold text-gray-800 border border-gray-200 print:border-black text-center">#<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="p-3 font-semibold text-gray-800 border border-gray-200 print:border-black"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td class="p-3 text-gray-600 border border-gray-200 print:border-black"><?php echo date('d-m-Y', strtotime($item['purchase_date'])); ?></td>
                            <td class="p-3 font-mono text-gray-700 border border-gray-200 print:border-black text-right"><?php echo $item['cost'] ? number_format($item['cost']) : '-'; ?></td>
                            <td class="p-3 font-bold text-red-700 print:text-black border border-gray-200 print:border-black"><?php echo $item['disposal_reason']; ?></td>
                            <td class="p-3 text-gray-800 border border-gray-200 print:border-black"><?php echo date('d-m-Y', strtotime($item['disposal_date'])); ?></td>
                            <td class="p-3 text-gray-600 italic border border-gray-200 print:border-black"><?php echo htmlspecialchars($item['remarks']); ?></td>
                            <td class="p-3 border border-gray-200 print:hidden text-center">
                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this record?');">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_item" class="text-red-500 hover:text-red-700 rounded-full p-2 hover:bg-red-50 transition-colors" title="Delete Permanently">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Print Footer (Signatures) -->
    <div class="hidden print:flex justify-between items-end mt-24 px-8 relative z-10">
        <div class="text-center">
            <div class="w-48 border-t border-black mb-2"></div>
            <p class="font-bold text-sm uppercase">Incharge Signature</p>
        </div>
        <div class="text-center">
            <div class="w-48 border-t border-black mb-2"></div>
            <p class="font-bold text-sm uppercase">Headmaster Signature</p>
        </div>
    </div>
</div>

<style>
    @media print {
        @page {
            margin: 0.5cm;
            size: A4 landscape; /* Recommend landscape for wide tables */
        }
        body {
            -webkit-print-color-adjust: exact;
            background-color: white !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
