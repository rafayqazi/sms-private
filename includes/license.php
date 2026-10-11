<?php
class License {
    private static $license_file = __DIR__ . '/../data/license.php';
    private static $secret_salt = 'AR_S0ftware_S0lution_@2026_Secure_Salt!#'; // Secret salt for hashing
    private static $license_machine_keys_cache = null;
    private static $license_machine_keys_cache_ttl = 300;

    private static function getMachineKeyCacheFile() {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sms-private-license-machine-keys-' . hash('sha256', __DIR__) . '.json';
    }

    private static function clearMachineKeyCache() {
        $cacheFile = self::getMachineKeyCacheFile();
        if (file_exists($cacheFile) && !unlink($cacheFile)) {
            error_log('Unable to clear the license machine-key cache.');
        }
        self::$license_machine_keys_cache = null;
    }

    private static function getLicensedMachineKeys() {
        if (self::$license_machine_keys_cache !== null) {
            return self::$license_machine_keys_cache;
        }

        $cacheFile = self::getMachineKeyCacheFile();
        if (is_readable($cacheFile)) {
            $cacheContents = file_get_contents($cacheFile);
            $cache = $cacheContents !== false ? json_decode($cacheContents, true) : null;
            $cachedAt = is_array($cache) ? (int)($cache['cached_at'] ?? 0) : 0;
            $cachedKeys = is_array($cache) ? ($cache['machine_keys'] ?? null) : null;

            if (
                $cachedAt > 0 &&
                time() >= $cachedAt &&
                time() - $cachedAt < self::$license_machine_keys_cache_ttl &&
                is_array($cachedKeys)
            ) {
                $validKeys = array_values(array_unique(array_filter($cachedKeys, function ($key) {
                    return is_string($key) && preg_match('/^[a-f0-9]{64}$/i', $key);
                })));

                if (!empty($validKeys)) {
                    self::$license_machine_keys_cache = $validKeys;
                    return self::$license_machine_keys_cache;
                }
            }
        }

        $machineKeys = [];
        foreach (self::getAllMacAddresses() as $mac) {
            $machineKeys[] = self::generateLicenseKey($mac);
        }
        $machineKeys = array_values(array_unique($machineKeys));
        self::$license_machine_keys_cache = $machineKeys;

        if (!empty($machineKeys)) {
            $cacheContents = json_encode([
                'cached_at' => time(),
                'machine_keys' => $machineKeys
            ]);
            if ($cacheContents === false || file_put_contents($cacheFile, $cacheContents, LOCK_EX) === false) {
                error_log('Unable to write the license machine-key cache.');
            }
        }

        return $machineKeys;
    }

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

        return array_values(array_unique($macs));
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
        if (!isset($license_data['license_key']) && !isset($license_data['allowed_mac'])) {
            return false;
        }

        $machineKeys = self::getLicensedMachineKeys();
        if (empty($machineKeys)) return false;

        if (!isset($license_data['license_key'])) {
            // Handle legacy plain-text MAC if it exists (for migration)
            if (isset($license_data['allowed_mac'])) {
                $allowed_key = self::generateLicenseKey($license_data['allowed_mac']);
                foreach ($machineKeys as $machineKey) {
                    if ($machineKey === $allowed_key) return true;
                }
            }
            return false;
        }

        $authorized_key = $license_data['license_key'];

        foreach ($machineKeys as $test_key) {
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
        
        // Ensure data directory exists
        $dir = dirname(self::$license_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'license_key' => $license_key,
            'activation_date' => date('Y-m-d H:i:s')
        ];
        
        $content = "<?php\nreturn " . var_export($data, true) . ";\n?>";
        $saved = file_put_contents(self::$license_file, $content) !== false;
        if ($saved) {
            self::clearMachineKeyCache();
        }
        return $saved;
    }

    /**
     * Check license and redirect if invalid
     */
    public static function checkAndRedirect() {
        // Skip check for installation, license error, and integrity pages
    $current_page = basename($_SERVER['PHP_SELF']);
    $installer_pages = ['install.php', 'installer_actions.php', 'restore_data.php', 'get_machine_info.php'];
    if (in_array($current_page, $installer_pages) || $current_page === 'license_error.php' || $current_page === 'missing_files.php') {
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
