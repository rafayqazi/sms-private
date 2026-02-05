<?php
class License {
    private static $license_file = __DIR__ . '/../data/license.php';
    private static $secret_salt = 'AR_S0ftware_S0lution_@2026_Secure_Salt!#'; // Secret salt for hashing

    /**
     * Get all MAC addresses of the current machine
     * Works on Windows
     */
    public static function getAllMacAddresses() {
        $macs = [];
        try {
            // Use getmac command for Windows
            $output = shell_exec("getmac");
            if ($output) {
                if (preg_match_all('/([0-9A-F]{2}[:-]){5}([0-9A-F]{2})/i', $output, $matches)) {
                    $macs = array_map('strtoupper', $matches[0]);
                }
            }
            
            // Backup method if getmac fails
            if (empty($macs)) {
                $output = shell_exec("ipconfig /all");
                if (preg_match_all('/Physical Address[ .:]+([0-9A-F]{2}[:-]){5}([0-9A-F]{2})/i', $output, $matches)) {
                    foreach ($matches[0] as $match) {
                        if (preg_match('/([0-9A-F]{2}[:-]){5}([0-9A-F]{2})/i', $match, $macMatches)) {
                            $macs[] = strtoupper($macMatches[0]);
                        }
                    }
                }
            }
        } catch (Exception $e) {}
        return array_unique($macs);
    }

    /**
     * Get the primary MAC address (first one found)
     */
    public static function getMacAddress() {
        $macs = self::getAllMacAddresses();
        return !empty($macs) ? $macs[0] : "Unknown";
    }

    /**
     * Generate a license key for a given MAC address
     */
    public static function generateLicenseKey($mac) {
        // Standardize MAC format
        $mac = str_replace('-', ':', strtoupper(trim($mac)));
        // Create a secure hash using the MAC and our secret salt
        return hash('sha256', $mac . self::$secret_salt);
    }

    /**
     * Check if the software is licensed for this machine
     */
    public static function isLicensed() {
        if (!file_exists(self::$license_file)) {
            return false;
        }

        $license_data = include self::$license_file;
        if (!isset($license_data['license_key'])) {
            // Handle legacy plain-text MAC if it exists (for migration)
            if (isset($license_data['allowed_mac'])) {
                $current_macs = self::getAllMacAddresses();
                $allowed_mac = str_replace('-', ':', strtoupper($license_data['allowed_mac']));
                foreach ($current_macs as $mac) {
                    if (str_replace('-', ':', $mac) === $allowed_mac) return true;
                }
            }
            return false;
        }

        $current_macs = self::getAllMacAddresses();
        if (empty($current_macs)) return false;

        $authorized_key = $license_data['license_key'];

        foreach ($current_macs as $mac) {
            // Generate hash for the current machine's MAC
            $test_key = self::generateLicenseKey($mac);
            if ($test_key === $authorized_key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the allowed MAC address (saves as a hashed key)
     */
    public static function activate($mac) {
        $license_key = self::generateLicenseKey($mac);
        
        $data = [
            'license_key' => $license_key,
            'activation_date' => date('Y-m-d H:i:s')
        ];
        
        $content = "<?php\nreturn " . var_export($data, true) . ";\n?>";
        return file_put_contents(self::$license_file, $content) !== false;
    }

    /**
     * Check license and redirect if invalid
     */
    public static function checkAndRedirect() {
        // Skip check for license error page itself and missing files page
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page === 'license_error.php' || $current_page === 'missing_files.php') {
            return;
        }

        // 1. Check for .git folder - Software Integrity Protection
        if (!is_dir(__DIR__ . '/../.git')) {
            // Determine redirect path based on where we are
            $prefix = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false || strpos($_SERVER['PHP_SELF'], '/api/') !== false) ? '../' : '';
            header("Location: " . $prefix . "pages/missing_files.php");
            exit();
        }

        if (!self::isLicensed()) {
            // Allow access to settings for admin even if license fails (to allow activation/deactivation)
            // But only if they are already logged in as superadmin
            if ($current_page === 'settings.php' || $current_page === 'login.php') {
                return;
            }

            // Determine redirect path based on where we are
            $prefix = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false || strpos($_SERVER['PHP_SELF'], '/api/') !== false) ? '../' : '';
            header("Location: " . $prefix . "pages/license_error.php");
            exit();
        }
    }
}
?>
